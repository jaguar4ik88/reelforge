<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    private function frontendUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/');
    }

    public function redirect(Request $request, string $provider): RedirectResponse|SymfonyRedirectResponse
    {
        if (! in_array($provider, ['google', 'apple'], true)) {
            abort(404);
        }

        if (! $this->driverConfigured($provider)) {
            return redirect()->away($this->frontendUrl().'/login?oauth_error=not_configured');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Throwable $e) {
            Log::warning('OAuth redirect failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect()->away($this->frontendUrl().'/login?oauth_error=provider');
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'apple'], true)) {
            abort(404);
        }

        if (! $this->driverConfigured($provider)) {
            return redirect()->away($this->frontendUrl().'/login?oauth_error=not_configured');
        }

        try {
            $social = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            Log::warning('OAuth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect()->away($this->frontendUrl().'/login?oauth_error=provider');
        }

        $email = $social->getEmail();
        $name = $social->getName() ?: ($social->getNickname() ?: Str::before($email ?? '', '@') ?: 'User');
        $id = $social->getId();

        if ($email === null || $email === '') {
            return redirect()->away($this->frontendUrl().'/login?oauth_error=no_email');
        }

        $user = User::query()
            ->where('provider', $provider)
            ->where('provider_id', $id)
            ->first();

        if (! $user) {
            $existing = User::query()->where('email', $email)->first();
            if ($existing) {
                if ($existing->password !== null) {
                    return redirect()->away($this->frontendUrl().'/login?oauth_error=email_exists');
                }

                $user = $existing;
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $id,
                ]);
            } else {
                if (! config('platform.registration_enabled', true)) {
                    return redirect()->away($this->frontendUrl().'/login?oauth_error=registration_closed');
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => null,
                    'provider' => $provider,
                    'provider_id' => $id,
                    'plan' => 'free',
                    'email_verified_at' => now(),
                ]);

                Mail::to($user->email)->queue(new WelcomeMail($user));
            }
        }

        $user->tokens()->delete();
        $token = $user->createToken('api')->plainTextToken;

        return redirect()->away(
            $this->frontendUrl().'/auth/oauth-callback?'.http_build_query([
                'token' => $token,
            ])
        );
    }

    private function driverConfigured(string $provider): bool
    {
        $config = config('services.'.$provider);

        if (! is_array($config)) {
            return false;
        }

        if ($provider === 'google') {
            return filled($config['client_id'] ?? null) && filled($config['client_secret'] ?? null) && filled($config['redirect'] ?? null);
        }

        if ($provider === 'apple') {
            return filled($config['client_id'] ?? null)
                && filled($config['redirect'] ?? null)
                && filled($config['team_id'] ?? null)
                && filled($config['key_id'] ?? null)
                && filled($config['private_key'] ?? null);
        }

        return false;
    }
}
