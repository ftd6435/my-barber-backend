<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Constants
    |--------------------------------------------------------------------------
    |
    | These constants are used throughout the application for various
    | configurations and business logic.
    |
    */

    'pagination' => [
        'per_page' => env('PAGINATION_PER_PAGE', 15),
        'max_per_page' => env('PAGINATION_MAX_PER_PAGE', 100),
    ],

    'otp' => [
        'length' => env('OTP_LENGTH', 6),
        'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 5),
        'rate_limit' => env('OTP_RATE_LIMIT', 3),
        'rate_limit_decay_minutes' => env('OTP_RATE_LIMIT_DECAY', 60),
    ],

    'sms' => [
        'max_length' => env('SMS_MAX_LENGTH', 160),
    ],

    'security' => [
        'password_min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'encrypt_otp' => env('ENCRYPT_OTP', true),
    ],

];
