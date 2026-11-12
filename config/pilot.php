<?php

/*
 | Pilot deployment (PRD 11). Values come from the server's .env; .env.example lists the keys.
 */

return [

    // The real clinic trial runs with PILOT_MODE=true: noindex, the pilot banner (E9) and the go-live check (E10).
    'mode' => (bool) env('PILOT_MODE', false),

    // Who may register (E3): open, clinic_code (a valid clinic code is required) or closed (invitations only).
    'registration_mode' => env('REGISTRATION_MODE', 'clinic_code'),

    // Error alerts (E6) and the /health check token (E6).
    'alert_email' => env('ALERT_EMAIL') ?: null,
    'health_token' => env('HEALTH_TOKEN') ?: null,

    // Shown on the help page (E9).
    'support_email' => env('SUPPORT_EMAIL') ?: null,
    'support_whatsapp' => env('SUPPORT_WHATSAPP') ?: null,

    /*
     | Throttles (E2, DRAFT): [attempts, per seconds].
     */
    'throttle' => [
        'password_reset' => [5, 60],          // forgot-password requests per IP
        'verification_resend' => [3, 600],    // "kirim ulang email verifikasi" per user
        'consultation' => [5, 3600],          // consultation / referral requests per patient
    ],

];
