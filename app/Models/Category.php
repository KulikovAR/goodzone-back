<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PriorityTrait;

class Category extends Model
{
    use PriorityTrait;

    protected $fillable = ['name', 'hidden', 'priority'];
}
