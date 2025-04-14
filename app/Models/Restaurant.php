<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PriorityTrait;

class Restaurant extends Model
{
    use PriorityTrait;

    protected $fillable = ['name', 'photo', 'priority'];
}
