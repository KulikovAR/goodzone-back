<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at,
            'is_partner' => $this->is_partner,
            'partner_takeout' => $this->partner_takeout,
            'profile' => new UserProfileResource($this->userProfile),
        ];
    }
}
