<?php

namespace App\Models\Dimensions;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'project';
}
