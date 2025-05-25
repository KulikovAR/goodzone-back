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
            'amount' => (int) $this->amount,
            'type' => $this->type,
            'purchase_amount' => $this->purchase_amount ? (int) $this->purchase_amount : null,
            'expires_at' => $this->expires_at?->format('Y-m-d\TH:i:s'),
            'created_at' => $this->created_at->format('Y-m-d\TH:i:s'),
        ];
    }
}