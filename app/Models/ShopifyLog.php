<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyLog extends Model
{
    protected $fillable = [
        'endpoint',
        'method',
        'request_payload',
        'response_payload',
        'status_code',
        'error',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}

