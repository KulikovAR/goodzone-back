<?php

namespace App\Http\Resources;

use App\Traits\DateFormats;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    use DateFormats;

    public function toArray(Request $request): array
    {
        return [
            'phone_prefix' => trim($this->phone_prefix),
            'phone' => $this->phone,
            'country' => $this->country,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'birthday' => $this->formatDateForOutput($this->birthday),
            'gender' => $this->gender,
            'document_number' => $this->document_number,
            'document_expires' => $this->formatDateForOutput($this->document_expires),
        ];
    }
}
