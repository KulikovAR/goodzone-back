<?php

namespace App\Models;

use App\Traits\HasRefreshToken;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use NotificationChannels\Expo\ExpoPushToken;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRefreshToken, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'gender',
        'city',
        'email',
        'phone',
        'device_token',
        'phone_verified_at',
        'code_send_at',
        'bonus_amount',
        'purchase_amount',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'code_send_at'      => 'datetime',
        'deleted_at'        => 'datetime',
        'password'          => 'hashed',
        'bonus_amount'      => 'decimal:2',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(Bonus::class);
    }

    public function addPurchaseAmount($amount)
    {
        $this->purchase_amount += $amount;
    }

    public function routeNotificationForExpo(): ?ExpoPushToken
    {
        return $this->device_token ? new ExpoPushToken($this->device_token) : null;
    }
}
