<?php

namespace Keggermont\LaravelQuickbooks\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksAccount;
use Keggermont\LaravelQuickbooks\Helpers\Quickbooks;

class RefreshQbObjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:refresh-objects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh object sync from QB (Invoice, PurchaseOrder, CreditMemo)';
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
        Quickbooks::importObjects(12);
    }
}
