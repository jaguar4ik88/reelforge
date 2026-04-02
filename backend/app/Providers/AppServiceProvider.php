<?php

namespace App\Providers;

use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::toMailUsing(function (CanResetPassword $notifiable, #[\SensitiveParameter] string $token): MailMessage {
            $broker = config('auth.defaults.passwords');
            $expire = (int) config("auth.passwords.{$broker}.expire", 60);
            $url = rtrim((string) config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject(__('emails.reset.subject', ['app' => config('app.name')]))
                ->line(__('emails.reset.line1'))
                ->action(__('emails.reset.action'), $url)
                ->line(__('emails.reset.line2', ['count' => $expire]))
                ->line(__('emails.reset.line3'));
        });

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });
        User::created(function (User $user): void {
            $bonus = (int) config('reelforge.credits.welcome_bonus', 50);

            $wallet = UserCredit::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => $bonus]
            );

            if ($wallet->wasRecentlyCreated && $bonus > 0) {
                CreditTransaction::query()->create([
                    'user_id'       => $user->id,
                    'delta'         => $bonus,
                    'balance_after' => $bonus,
                    'kind'          => 'welcome_bonus',
                    'description'   => 'Welcome bonus credits',
                ]);
            }
        });
    }
}
