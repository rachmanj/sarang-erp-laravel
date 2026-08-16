<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'direction',
        'gateway_message_id',
        'to_number',
        'from_number',
        'body',
        'message_type',
        'status',
        'related_entity_type',
        'related_entity_id',
        'error',
    ];
}
