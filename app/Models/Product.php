<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PriorityTrait;

class Product extends Model
{
    use PriorityTrait;

    protected $fillable = ['category_id', 'name', 'description', 'price', 'weight', 'calories', 'hidden', 'priority'];
}
