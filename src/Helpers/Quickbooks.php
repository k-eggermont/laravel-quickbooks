<?php
namespace Keggermont\LaravelQuickbooks\Helpers;

use Illuminate\Support\Facades\Storage;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksEntity;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksOauth2;
use QuickBooksOnline\API\DataService\DataService;

final class Quickbooks {

    private static $instance;
    /**
     * @var DataService
     */
    private $auth = false;


    /**
     * Singleton
     *
     * @return Quickbooks
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return DataService
     * @throws \QuickBooksOnline\API\Exception\SdkException
     * @throws \QuickBooksOnline\API\Exception\ServiceException
     */
    public function getDataService() {

        // Skeleton
        if($this->auth != false) {
            return $this->auth;
        }

        $config = array(
            'auth_mode' => config("quickbooks.Oauth.auth_mode"),
            'ClientID' => config("quickbooks.Oauth.ClientID"),
            'ClientSecret' => config("quickbooks.Oauth.ClientSecret"),
            'scope' => config("quickbooks.Oauth.scope"),
            'baseUrl' => config("quickbooks.Oauth.baseUrl"),
            'QBORealmID' => config("quickbooks.Oauth.RealmID")
        );

        $data = QuickbooksOauth2::orderBy("created_at","DESC")->first();
        if($data) {
            $config["accessTokenKey"] = $data->accessTokenKey;
            $config["refreshTokenKey"] = $data->refreshTokenKey;
        } else {
            $config["accessTokenKey"] = config("quickbooks.Oauth.accessTokenKey");
            $config["refreshTokenKey"] = config("quickbooks.Oauth.refreshTokenKey");
        }

        $dataService = DataService::Configure($config);
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $refreshedAccessTokenObj = $OAuth2LoginHelper->refreshToken();

        if(!$data) {
            $data = new QuickbooksOauth2();
        }
        $data->accessTokenKey = $refreshedAccessTokenObj->getAccessToken();
        $data->refreshTokenKey = $refreshedAccessTokenObj->getRefreshToken();
        $data->save();


        if(config("quickbooks.enable_log") == true) {
            $dataService->enableLog();
        } else {
            $dataService->disableLog();
        }
        if(config("quickbooks.minor_version") != null) {
            $dataService->setMinorVersion(config("quickbooks.minor_version"));
        }
        $dataService->setLogLocation(config("quickbooks.log_location"));
        $dataService->throwExceptionOnError(config("quickbooks.throw_exception_on_error"));

        $this->auth = $dataService;
        return $dataService;

    }

    public static function importObjects($minutes = 12) {
        $Qb = Quickbooks::getInstance();
        $dataService = $Qb->getDataService();

        // API Based on PST Timezone
        $carbon = \Carbon\Carbon::now()->timezone(new \DateTimeZone("PST"));
        if(intval($minutes) > 0) {
            $cdc = $dataService->CDC(config("quickbooks.autoPullData.items"), $carbon->subMinutes(12));
        } else {
            $cdc = $dataService->CDC(config("quickbooks.autoPullData.items"), $carbon->subYear(3));
        }


        if(isset($cdc->entities["CreditMemo"]) && $cdc->entities["CreditMemo"] != null) {
            foreach ($cdc->entities["CreditMemo"] as $credit) {

                $base = QuickbooksEntity::where("Qb_Id", $credit->Id)->where("entity", "CreditMemo")->first();
                if ($base == null) {
                    $qb = new QuickbooksEntity();
                } else {
                    $qb = clone $base;
                }
                $qb->entity = "CreditMemo";
                $qb->TotalAmt = $credit->TotalAmt;
                $qb->DocNumber = $credit->DocNumber;
                $qb->TxnDate = $credit->TxnDate;
                $qb->DueDate = $credit->DueDate;
                $qb->Qb_Id = $credit->Id;
                $qb->CustomerRef = $credit->CustomerRef;
                $qb->Memo = $credit->CustomerMemo;
                $qb->Balance = $credit->Balance;
                $qb->SyncToken = $credit->SyncToken;
                if ($qb->lines == null) {
                    $qb->lines = "[]";
                }

                // Encode/Decode $qb and $base for compare. We delete the attribute "lines" for having troubles.
                $compare1 = json_decode(json_encode($qb));
                unset($compare1->lines);
                $compare2 = json_decode(json_encode($base));
                unset($compare2->lines);

                // If we have some changes, we update and download the PDF
                if ($compare1 != $compare2) {
                    $qb->save();
                    echo "CreditNote saved \n";
                }
            }

        }

        if(isset($cdc->entities["Invoice"]) && $cdc->entities["Invoice"] != null) {
            foreach ($cdc->entities["Invoice"] as $invoice) {
                $base = QuickbooksEntity::where("Qb_Id", $invoice->Id)->where("entity", "Invoice")->first();
                if ($base == null) {
                    $qb = new QuickbooksEntity();
                } else {
                    // Clonning $qb from $base for compare.
                    $qb = clone $base;
                }

                // Set all datas
                $qb->entity = "Invoice";
                $qb->TotalAmt = floatval($invoice->TotalAmt);
                $qb->DocNumber = intval($invoice->DocNumber);
                $qb->TxnDate = $invoice->TxnDate;
                $qb->DueDate = $invoice->DueDate;
                $qb->TotalTax = floatval($invoice->TxnTaxDetail->TotalTax);
                $qb->Qb_Id = intval($invoice->Id);
                $qb->CustomerRef = intval($invoice->CustomerRef);
                $qb->Memo = $invoice->CustomerMemo;
                $qb->Balance = floatval($invoice->Balance);
                $qb->SyncToken = intval($invoice->SyncToken);
                if(floatval($invoice->Balance) <= 0) {
                    $qb->Paid = true;
                } else {
                    $qb->Paid = false;
                }

                // If we have something like "annulée" or "cancel" in the CustomerMemo, the invoice was cancelled
                if(preg_match("/annulée par l'avoir/iu",$invoice->CustomerMemo) || preg_match("/cancel/iu",$invoice->CustomerMemo)) {
                    $qb->is_cancelled = true;
                }

                /*
                 * Making LINES attribute:
                 * Description => Line description
                 * Amount => Amount without taxes
                 * Qty => Quantity
                 * Discount => Discount Amount
                 * TaxCode => Tax reference
                 */
                $lines = [];
                foreach($invoice->Line as $line) {
                    if($line->DetailType == "SalesItemLineDetail") {
                        if($line->Description == null) { $desc = ""; } else { $desc = $line->Description; }
                        $lines[] = array("Description" => $desc, "Amount" => floatval($line->Amount), "Discount" => floatval($line->SalesItemLineDetail->DiscountAmt), "UnitPrice" => floatval($line->SalesItemLineDetail->UnitPrice), "Qty" => floatval($line->SalesItemLineDetail->Qty), "TaxCode" =>  intval($line->SalesItemLineDetail->TaxCodeRef));
                    }
                }
                $qb->lines = json_encode($lines);


                // Encode/Decode $qb and $base for compare. We delete the attribute "lines" for having troubles.
                $compare1 = json_decode(json_encode($qb));
                unset($compare1->lines);
                $compare2 = json_decode(json_encode($base));
                unset($compare2->lines);

                // If we have some changes, we update and download the PDF
                if ($compare1 != $compare2) {

                    if(config("quickbooks.pdf.download") == true) {
                        //dd($dataService->getExportFileNameForPDF($invoice,"pdf"));

                        $name = $dataService->getExportFileNameForPDF($invoice,"pdf");
                        if(config("quickbooks.pdf.filename") == "md5") {
                            $name = md5($name).".pdf";
                        }
                        $completePath = config("quickbooks.pdf.folder").$name;
                        Storage::disk(config("quickbooks.pdf.disk"))->put($completePath, file_get_contents($dataService->DownloadPDF($invoice)));
                        //rename(, "/tmp/facture_" . $qb->DocNumber . ".pdf");
                        $qb->pdf = Storage::disk(config("quickbooks.pdf.disk"))->url($completePath);

                    }
                    $qb->save();
                    echo "Invoice saved \n";
                }
            }
        }
        if(isset($cdc->entities["PurchaseOrder"]) && $cdc->entities["PurchaseOrder"] != null) {
            foreach ($cdc->entities["PurchaseOrder"] as $po) {
                $base = QuickbooksEntity::where("Qb_Id", $po->Id)->where("entity", "PurchaseOrder")->first();
                if ($base == null) {
                    $qb = new QuickbooksEntity();
                } else {
                    $qb = clone $base;
                }
                $qb->entity = "PurchaseOrder";
                /*
                 * $qb->TotalAmt = floatval($invoice->TotalAmt);
                $qb->DocNumber = intval($invoice->DocNumber);
                $qb->TxnDate = $invoice->TxnDate;
                $qb->DueDate = $invoice->DueDate;
                $qb->TotalTax = floatval($invoice->TxnTaxDetail->TotalTax);
                $qb->Qb_Id = intval($invoice->Id);
                $qb->CustomerRef = intval($invoice->CustomerRef);
                $qb->Memo = $invoice->CustomerMemo;
                $qb->Balance = floatval($invoice->Balance);
                $qb->SyncToken = intval($invoice->SyncToken);
                 */
                $qb->VendorRef = intval($po->VendorRef);
                $qb->Memo = $po->Memo;
                $qb->TotalAmt = floatval($po->TotalAmt);
                $qb->POStatus = $po->POStatus;
                $qb->DocNumber = intval($po->DocNumber);
                $qb->TotalTax = floatval($po->TxnTaxDetail->TotalTax);
                $qb->TxnDate = $po->TxnDate;
                $qb->Qb_Id = intval($po->Id);
                $qb->SyncToken = intval($po->SyncToken);

                $lines = [];
                $i = 0;
                foreach($po->Line as $line) {
                    if($line->DetailType == "ItemBasedExpenseLineDetail") {

                        if($line->Description == null) { $desc = ""; } else { $desc = $line->Description; }
                        $lines[] = array("Description" => $desc, "Amount" => floatval($line->Amount), "UnitPrice" => floatval($line->ItemBasedExpenseLineDetail->UnitPrice), "Qty" => floatval($line->ItemBasedExpenseLineDetail->Qty), "TaxCode" =>  intval($line->ItemBasedExpenseLineDetail->TaxCodeRef));

                    }

                    $i++;
                }
                $qb->lines = json_encode($lines);

                // Encode/Decode $qb and $base for compare. We delete the attribute "lines" for having troubles.
                $compare1 = json_decode(json_encode($qb));
                unset($compare1->lines);
                $compare2 = json_decode(json_encode($base));
                unset($compare2->lines);

                // If we have some changes, we update and download the PDF
                if ($compare1 != $compare2) {
                    $qb->save();
                    echo "PurchaseOrder saved \n";
                }
            }
        }

        if(isset($cdc->entities["Bill"]) && $cdc->entities["Bill"] != null) {
            foreach ($cdc->entities["Bill"] as $bill) {
                $base = QuickbooksEntity::where("Qb_Id", $bill->Id)->where("entity", "Bill")->first();
                if ($base == null) {
                    $qb = new QuickbooksEntity();
                } else {
                    $qb = clone $base;
                }
                $qb->entity = "Bill";
                $qb->VendorRef = intval($bill->VendorRef);
                $qb->Memo = $bill->Memo;
                $qb->TotalAmt = floatval($bill->TotalAmt);
                $qb->Balance = floatval($bill->Balance);
                $qb->DocNumber = intval($bill->DocNumber);
                $qb->TotalTax = floatval($bill->TxnTaxDetail->TotalTax);
                $qb->TxnDate = $bill->TxnDate;
                $qb->DueDate = $bill->DueDate;
                $qb->Qb_Id = intval($bill->Id);
                $qb->SyncToken = intval($bill->SyncToken);

                $lines = [];
                $i = 0;
                foreach($bill->Line as $line) {
                    if(isset($line->DetailType) && $line->DetailType == "ItemBasedExpenseLineDetail") {

                        if($line->Description == null) { $desc = ""; } else { $desc = $line->Description; }
                        $lines[] = array("Description" => $desc, "Amount" => floatval($line->Amount), "UnitPrice" => floatval($line->ItemBasedExpenseLineDetail->UnitPrice), "Qty" => floatval($line->ItemBasedExpenseLineDetail->Qty), "TaxCode" =>  intval($line->ItemBasedExpenseLineDetail->TaxCodeRef));

                    }

                    $i++;
                }
                $qb->lines = json_encode($lines);

                // Encode/Decode $qb and $base for compare. We delete the attribute "lines" for having troubles.
                $compare1 = json_decode(json_encode($qb));
                unset($compare1->lines);
                $compare2 = json_decode(json_encode($base));
                unset($compare2->lines);

                // If we have some changes, we update and download the PDF
                if ($compare1 != $compare2) {
                    $qb->save();
                    echo "Bill saved \n";
                }
            }
        }
    }

}