<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationHistory extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
    ];
}
