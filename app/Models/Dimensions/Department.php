<?php

namespace App\Models\Dimensions;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'department';
}
