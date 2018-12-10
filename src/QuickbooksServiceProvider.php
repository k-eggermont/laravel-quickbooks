<?php

namespace Keggermont\LaravelQuickbooks;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Keggermont\LaravelQuickbooks\Commands\ImportQbAccounts;
use Keggermont\LaravelQuickbooks\Commands\ImportQbTaxCode;
use Keggermont\LaravelQuickbooks\Commands\RefreshQbObjects;

class QuickbooksServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        /*
         * Initialize Schedule if enabled
         */
        if(config("quickbooks.autoPullData.enable")) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('quickbooks:refresh-objects')->everyTenMinutes();
            });
        }

        /*
         * Initialize commands
         */
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportQbTaxCode::class,
                ImportQbAccounts::class,
                RefreshQbObjects::class,
            ]);
        }

        /**
         * Publish config & migration
         */
        //$this->loadMigrationsFrom(__DIR__.'/database/migrations/');
        $this->publishes([
            __DIR__.'/config/quickbooks.php' => config_path('quickbooks.php'),
            __DIR__.'/database/migrations/' => database_path('migrations/'),
        ], 'config');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
