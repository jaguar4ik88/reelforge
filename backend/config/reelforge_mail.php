<?php

use App\Mail\WelcomeMail;
use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Map domain events to Mailable classes (optional registry for documentation and future use).
 * Blade templates: resources/views/emails/. Translations: lang/{locale}/emails.php
 *
 * Password reset uses Laravel's ResetPassword notification (not a Mailable).
 * SPA reset URL and email copy are set in AppServiceProvider::boot() via ResetPassword::toMailUsing().

 */
return [
    'mailables' => [
        'user.registered' => WelcomeMail::class,
        // 'payment.succeeded' => PaymentReceiptMail::class,
    ],

    'notifications' => [
        'auth.password_reset' => ResetPassword::class,
    ],
];
