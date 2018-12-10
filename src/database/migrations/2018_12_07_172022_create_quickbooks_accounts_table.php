<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string("Name")->nullable();
            $table->string("FullyQualifiedName")->nullable();
            $table->string("AccountAlias")->nullable();
            $table->string("Classification")->nullable();
            $table->string("AccountType")->nullable();
            $table->boolean("Active")->nullable();
            $table->string("AcctNum")->nullable();
            $table->integer("qb_Id")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quickbooks_accounts');
    }
}
