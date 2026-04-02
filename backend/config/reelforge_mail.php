<?php

/**
 * Map domain events to Mailable classes (optional registry for documentation and future use).
 * Blade templates: resources/views/emails/. Translations: lang/{locale}/emails.php
 *
 * Password reset uses Laravel's Illuminate\Auth\Notifications\ResetPassword (not a Mailable).
 * SPA reset URL and email copy are set in AppServiceProvider::boot() via ResetPassword::toMailUsing().
 */
return [
    'mailables' => [
        'user.registered' => \App\Mail\WelcomeMail::class,
        // 'payment.succeeded' => \App\Mail\PaymentReceiptMail::class,
    ],

    'notifications' => [
        'auth.password_reset' => \Illuminate\Auth\Notifications\ResetPassword::class,
    ],
];
