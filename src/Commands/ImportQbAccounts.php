<?php

namespace Keggermont\LaravelQuickbooks\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Keggermont\LaravelQuickbooks\Entities\QuickbooksAccount;
use Keggermont\LaravelQuickbooks\Helpers\Quickbooks;

class ImportQbAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:import-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Accounts from QB';

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
        $accounts = $qb->query("SELECT * FROM Account");
        if($accounts) {
            DB::select("TRUNCATE TABLE quickbooks_accounts");
            foreach($accounts as $account) {
                $o = new QuickbooksAccount();
                $o->Name = $account->Name;
                $o->FullyQualifiedName = $account->FullyQualifiedName;
                $o->AccountAlias  = $account->AccountAlias;
                $o->Classification  = $account->Classification;
                $o->AccountType  = $account->AccountType;

                if(strtolower($account->Active) == "true") {
                    $o->Active = true;
                } else {
                    $o->Active = false;
                }

                $o->AcctNum  = $account->AcctNum;
                $o->qb_Id  = $account->Id;
                $o->save();
            }
        }
    }
}
