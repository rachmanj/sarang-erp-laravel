<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['code', 'name', 'email', 'phone'];
}
