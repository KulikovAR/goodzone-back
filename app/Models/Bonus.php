<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bonus extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'purchase_amount',
        'type',
        'expires_at',
        'used',
        'service',
        'status',
        'id_sell',
        'parent_id_sell'
    ];

    protected $casts = [
        'amount' => 'integer',
        'purchase_amount' => 'integer',
        'expires_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setUsed(): void
    {
        $this->used = true;
        $this->save();
    }
}
