# QuickBooks Laravel Wrapper

PHP wrapper for connecting to the QuickBooks Online V3 REST API.

## Installation
```bash
composer require XX
php artisan vendor:publish
php artisan migrate
```
Dont forget to add \Keggermont\LaravelQuickbooks\QuickbooksServiceProvider::class into your config/app.php file

---

### Configuration

You can edit the file config/quickbooks.php for setup your configuration. You can use the playground for generate your accessToken, refreshToken, RealmID etc .. ( https://developer.intuit.com/v2/ui#/playground )

---

### Usable Commands

You have access to few commands :
```bash
php artisan quickbooks:import-accounts        
php artisan quickbooks:import-taxcodes        
php artisan quickbooks:refresh-objects
```
#### Import-Accounts :
Import data from Quickbooks to the table quickbooks_accounts.
More information about Account : 
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/most-commonly-used/account

#### Import-Taxcodes :
Import data from Quickbooks to the table quickbooks_tax_rates and quickbooks_tax_codes.
More information about TaxRate and TaxCode : 
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/taxcode
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/taxrate 

#### Refresh-Objects :
Import objects data from Quickbooks (Bill, PurchaseOrder, Invoice, CreditMemo) to the table quickbooks_entities.
More information about objects : 
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/bill
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/purchaseorder
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/invoice
- https://developer.intuit.com/app/developer/qbo/docs/api/accounting/all-entities/creditmemo

If there are no changes, the database will not be updated
On invoice change, the pdf was downloaded to the server (configuration available on config/quickbooks.php)
---
### Entities
You have access to 4 new entities (with Eloquent) :
- Keggermont\LaravelQuickbooks\Entities\QuickbooksAccount
- Keggermont\LaravelQuickbooks\Entities\QuickbooksEntity
- Keggermont\LaravelQuickbooks\Entities\QuickbooksTaxCode
- Keggermont\LaravelQuickbooks\Entities\QuickbooksTaxRate

You can make some events with Eloquent for listening changes.


## Library used
https://github.com/intuit/QuickBooks-V3-PHP-SDK

You can access to the dataService with :
```php
$Qb = Keggermont\LaravelQuickbooks\Helpers\Quickbooks::getInstance();
$dataService = $Qb->getDataService(); 
```

Sample code for playing with the Api :
```php
/* Dump all Invoices */
$Qb = Keggermont\LaravelQuickbooks\Helpers\Quickbooks::getInstance();
$dataService = $Qb->getDataService();
dump($dataService->query("SELECT * FROM Invoice")

/* Dump an Customer Id */
dump($dataService->FindById("customer",1);

/* Dump all Customers */
dump($dataService->FindAll("customer");
```

You can have some code example of the library on : https://github.com/IntuitDeveloper/SampleApp-CRUD-PHP


## Advanced usage
Best practice for create or update data with the Quickbooks API is using Transformer.

At start, import the package fractal :
````bash
composer require league/fractal
````
Now you can create your Transformer like :
````php
# File: app/Transformers/QuickbooksCustomerTransformer.php
namespace App\Transformers;

use App\Customer;
use League\Fractal\TransformerAbstract;

class QuickbooksCustomerTransformer extends TransformerAbstract
{
    /**
     * @param Customer $customer
     * @return array
     */
    public function transform(Customer $customer)
    {
        // Append the display name for avoid Vendor/Customer duplication
        $displayName = $customer->name." (C)";
        $billing_address = $customer->billing_address; 

        $arr_methods = ["MONEY" => "1","CARD" => "3","WIRE" => "5"];
        $arr_conditions = ["NET30" => "3", "NET15" => "2", "NET60" => "4", "DEFAULT" => "1"];

        return [
            "BillAddr" => [
                "Line1" => $billing_address->address,
                "Line2" => $billing_address->address_line_2,
                "Line3" => null,
                "City" => $billing_address->city,
                "Country" => $billing_address->country,
                "CountrySubDivisionCode" => "FR",
                "PostalCode" => $billing_address->zip
            ],
            "GivenName" => $customer->owner->first_name,
            "FamilyName" => $customer->owner->name,
            "CompanyName" => $customer->name,
            "DisplayName" => $displayName,
            "SalesTermRef" => ["value" => $arr_conditions[strtoupper($customer->payment_condition)]],
            "PaymentMethodRef" => ["value" => $arr_methods[strtoupper($customer->payment_method)]],
            "Notes" => $customer->notes,
            "PrintOnCheckName" => $customer->name,
            "PrimaryPhone" => [
                "FreeFormNumber" => $customer->owner->phone
            ],
            "PrimaryEmailAddr" => [
                "Address" => $customer->owner->email
            ]
        ];
    }
}
````
Now you can create the customer with Quickbooks :
````php
use Keggermont\LaravelQuickbooks\Helpers\Quickbooks;
use App\Transformers\QuickbooksCustomerTransformer;
use QuickBooksOnline\API\Facades\Customer;

$Qb = Quickbooks::getInstance();
$dataService = $Qb->getDataService();

$customerObj = App\Customer::firstOrFail();
$customerQuickbooks = (new QuickbooksCustomerTransformer)->transform($customerObj);
$theResourceObj = Customer::create($customerQuickbooks);
$resultingObj = $dataService->Add($theResourceObj);
````