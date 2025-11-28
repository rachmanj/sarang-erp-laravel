<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    protected $auditLogIgnore = ['updated_at', 'created_at'];
    protected $auditEntityType = 'journal_line';
}
