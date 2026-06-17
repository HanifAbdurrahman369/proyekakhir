<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CaptchaService
{
    public function verify($token)
    {
        $response = Http::asForm()->post(
            env('CAPTCHA_VERIFY_URL'),
            [
                'secret' => env('CAPTCHA_SECRET_KEY'),
                'response' => $token
            ]
        );

        $result = $response->json();

        return isset($result['score']) && $result['score'] >= 0.5;
    }
}