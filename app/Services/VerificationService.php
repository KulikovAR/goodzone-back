<?php

namespace App\Services;

use App\Models\VerificationCode;
use Carbon\Carbon;

class VerificationService
{
    public function generateCode(string $phone): string
    {
        // In real app, generate random code
        $code = '1234';

        VerificationCode::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        return $code;
    }

    public function verifyCode(string $phone, string $code): bool
    {
        $verificationCode = VerificationCode::where('phone', $phone)
            ->where('code', $code)
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$verificationCode) {
            return false;
        }

        $verificationCode->update(['verified_at' => Carbon::now()]);
        return true;
    }
}