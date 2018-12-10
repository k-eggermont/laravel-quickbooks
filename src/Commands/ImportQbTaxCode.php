<?php

namespace Keggermont\LaravelQuickbooks\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksTaxCode;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksTaxRate;
use Keggermont\LaravelQuickbooks\Helpers\Quickbooks;

class ImportQbTaxCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:import-taxcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Tax Codes';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $qb = Quickbooks::auth();

        $taxes = $qb->query("SELECT * FROM TaxRate");
        if($taxes) {
            DB::select("TRUNCATE TABLE quickbooks_tax_rates");
            foreach($taxes as $tax) {
                $o = new QuickbooksTaxRate();
                $o->Name = $tax->Name;
                $o->Description = $tax->Description;
                $o->AgencyRef = $tax->AgencyRef;
                $o->RateValue = $tax->EffectiveTaxRate->RateValue;
                $o->TaxReturnLineRef = $tax->TaxReturnLineRef;

                if(strtolower($tax->Active) == "true") {
                    $o->Active = true;
                } else {
                    $o->Active = false;
                }

                $o->qb_Id = $tax->Id;
                $o->save();

            }
        }

        $taxes = $qb->query("SELECT * FROM TaxCode");
        if($taxes) {
            DB::select("TRUNCATE TABLE quickbooks_tax_codes");
            foreach($taxes as $tax) {
                $o = new QuickbooksTaxCode();
                $o->Name = $tax->Name;
                $o->Description = $tax->Description;

                if(strtolower($tax->Active) == "true") {
                    $o->Active = true;
                } else {
                    $o->Active = false;
                }

                $o->qb_Id  = $tax->Id;
                $o->save();
            }
        }
    }
}
