<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_entities', function (Blueprint $table) {
            $table->increments('id');
            $table->string("entity");
            $table->string("POStatus")->nullable();
            $table->string("APAccountRef")->nullable();
            $table->float("TotalAmt")->nullable();
            $table->float("Balance")->nullable();
            $table->float("TotalTax")->nullable();
            $table->integer("DocNumber")->nullable();
            $table->string("TxnDate")->nullable();
            $table->string("DueDate")->nullable();
            $table->integer("Qb_Id")->nullable();
            $table->integer("CustomerRef")->nullable();
            $table->integer("VendorRef")->nullable();
            $table->boolean("Paid")->default(false);
            $table->string("pdf")->nullable();
            $table->integer("SyncToken")->nullable();
            $table->text("Memo")->nullable();
            $table->boolean("is_cancelled")->nullable();
            $table->json("lines");
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
        Schema::dropIfExists('quickbooks_entities');
    }
}
