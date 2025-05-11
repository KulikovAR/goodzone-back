<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BonusCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'history' => $this->collection,
            'total_count' => $this->collection->count(),
        ];
    }
} 