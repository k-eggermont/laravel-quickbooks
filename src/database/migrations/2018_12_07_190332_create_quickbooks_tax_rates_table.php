<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksTaxRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_tax_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->string("Name")->nullable();
            $table->string("Description")->nullable();
            $table->integer("AgencyRef")->nullable();
            $table->string("RateValue")->nullable();
            $table->string("TaxReturnLineRef")->nullable();
            $table->boolean("Active")->nullable();
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
        Schema::dropIfExists('quickbooks_tax_rates');
    }
}
