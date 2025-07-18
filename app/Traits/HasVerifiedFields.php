<?php

namespace App\Traits;

trait HasVerifiedFields
{
    /**
     * Проверяет заполнены ли обязательные поля профиля
     * @return bool
     */
    public function isVerified(): bool
    {
        $requiredFields = ['name', 'phone', 'birthday', 'gender', 'city'];
        
        foreach ($requiredFields as $field) {
            if (empty($this->{$field})) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Добавляет поле verified в модель при сериализации
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['verified'] = $this->isVerified();
        return $array;
    }
}