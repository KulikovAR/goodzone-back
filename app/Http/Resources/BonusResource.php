<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format($this->amount, 2, '.', ''),
            'type' => $this->type,
            'purchase_amount' => $this->purchase_amount ? number_format($this->purchase_amount, 2, '.', '') : null,
            'expires_at' => $this->expires_at?->format('Y-m-d\TH:i:s'),
            'created_at' => $this->created_at->format('Y-m-d\TH:i:s'),
        ];
    }
} 