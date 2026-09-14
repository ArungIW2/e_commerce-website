<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiteshipWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'biteship_order_id',
        'payload',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
