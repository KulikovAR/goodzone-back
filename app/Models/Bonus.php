<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bonus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'purchase_amount',
        'type',
        'expires_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'expires_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}