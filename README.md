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
$dataService = Keggermont\LaravelQuickbooks\Helpers\Quickbooks::auth();
```

Sample code for playing with the Api :
```php
/* Dump all Invoices */
$dataService = Keggermont\LaravelQuickbooks\Helpers\Quickbooks::auth();
dump($dataService->query("SELECT * FROM Invoice")

/* Dump an Customer Id */
dump($dataService->FindById("customer",1);

/* Dump all Customers */
dump($dataService->FindAll("customer");
```

You can have some code example of the library on : https://github.com/IntuitDeveloper/SampleApp-CRUD-PHP