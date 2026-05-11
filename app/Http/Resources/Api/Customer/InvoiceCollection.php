<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InvoiceCollection extends ResourceCollection
{
    public $collects = InvoiceResource::class;
}
