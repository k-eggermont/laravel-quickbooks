<?php

namespace Keggermont\LaravelQuickbooks\Entities;

use Illuminate\Database\Eloquent\Model;

class QuickbooksEntity extends Model
{

    public $casts = ["Paid" => "Boolean"];

    public function getBalanceAttribute($balance) {
        return floatval($balance);
    }
    public function getTotalAmtAttribute($TotalAmt) {
        return floatval($TotalAmt);
    }
    public function getTotalTaxAttribute($TotalTax) {
        return floatval($TotalTax);
    }
}
