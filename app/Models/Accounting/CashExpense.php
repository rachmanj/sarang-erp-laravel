<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class CashExpense extends Model
{
    protected $fillable = ['date', 'description', 'account_id', 'amount', 'status'];
}
