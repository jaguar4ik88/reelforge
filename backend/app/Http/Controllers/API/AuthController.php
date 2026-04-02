<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'plan' => 'free',
        ]);

        Mail::to($user->email)->queue(new WelcomeMail($user));

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('messages.auth.registered'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            $msg = __('messages.auth.invalid_credentials');

            return response()->json([
                'success' => false,
                'message' => $msg,
                'errors' => [
                    'email' => [$msg],
                ],
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('messages.auth.login_success'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.auth.logout_success'),
            'data' => [],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => new UserResource($request->user()),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'success' => false,
                'message' => __('messages.auth.reset_throttled'),
                'errors' => [
                    'email' => [__('messages.auth.reset_throttled')],
                ],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.auth.reset_link_sent_generic'),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => __('messages.auth.password_reset_success'),
            ]);
        }

        $message = match ($status) {
            Password::INVALID_TOKEN => __('messages.auth.invalid_reset_token'),
            Password::INVALID_USER => __('messages.auth.invalid_reset_user'),
            default => __('messages.auth.password_reset_failed'),
        };

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => [
                'email' => [$message],
            ],
        ], 422);
    }
}
