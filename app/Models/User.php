<?php

namespace App\Models;

use App\Traits\HasRefreshToken;
use App\Traits\HasVerifiedFields;
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
use App\Enums\UserRole;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRefreshToken, HasRoles, Notifiable, SoftDeletes, HasVerifiedFields;

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
        'phone_verified_at',
        'code_send_at',
        'bonus_amount',
        'purchase_amount',
        'profile_completed_bonus_given',
        'password',
        'role',
        'birthday',
        'children',
        'marital_status',
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
        'role'              => UserRole::class,
        'birthday'          => 'datetime',
    ];

    public function is1c(): bool
    {
        return $this->role->value === UserRole::ONE_C->value;
    }

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
        $this->purchase_amount += (int) $amount;
        $this->save();
    }

    public function subtractPurchaseAmount($amount)
    {
        $this->purchase_amount = max(0, $this->purchase_amount - (int) $amount);
        $this->save();
    }

    /**
     * Проверяет заполнены ли ВСЕ поля профиля для получения бонуса
     * Включает ВСЕ поля анкеты, включая children и marital_status
     */
    public function isProfileCompleted(): bool
    {
        $allProfileFields = ['name', 'gender', 'city', 'email', 'birthday', 'children', 'marital_status'];
        
        foreach ($allProfileFields as $field) {
            if (empty($this->{$field})) {
                return false;
            }
        }
        
        return true;
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    public function routeNotificationForExpo(): array
    {
        return $this->deviceTokens->pluck('device_token')->map(function ($token) {
            return ExpoPushToken::make($token);
        })->toArray();
    }
}
