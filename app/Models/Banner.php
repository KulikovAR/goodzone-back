<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PriorityTrait;

class Banner extends Model
{
    use PriorityTrait;

    protected $fillable = ['name', 'image_path', 'priority'];
}
