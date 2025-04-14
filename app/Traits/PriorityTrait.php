<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait PriorityTrait
{
    public static function bootPriorityTrait()
    {
        static::saving(function (Model $model) {
            if ($model->isDirty('priority')) {
                $oldPriority = $model->getOriginal('priority');
                $newPriority = $model->priority;

                if ($oldPriority !== null && $newPriority !== null) {
                    $query = static::query()->where('id', '!=', $model->id);

                    if ($oldPriority < $newPriority) {
                        $query->whereBetween('priority', [$oldPriority + 1, $newPriority])
                            ->decrement('priority');
                    } elseif ($oldPriority > $newPriority) {
                        $query->whereBetween('priority', [$newPriority, $oldPriority - 1])
                            ->increment('priority');
                    }
                }
            }
        });
    }
}