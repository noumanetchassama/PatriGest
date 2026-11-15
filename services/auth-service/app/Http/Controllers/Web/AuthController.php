<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];

        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        $passwordOk = $user && Hash::check(
            $credentials['password'],
            $user->password
        );

        if (! $user || ! $passwordOk) {
            LoginLog::create([
                'login' => $login,
                'success' => false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'login' => ['Identifiants incorrects.'],
            ]);
        }

        if (! $user->is_active) {
            LoginLog::create([
                'user_id' => $user->id,
                'login' => $login,
                'success' => false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'login' => ['Ce compte est désactivé. Contactez un administrateur.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $assetToken = $user->createToken('web-dashboard');
        $request->session()->put('asset_api_token', $assetToken->plainTextToken);
        $request->session()->put('asset_api_token_id', $assetToken->accessToken->getKey());

        $user->forceFill(['last_login_at' => now()])->save();

        LoginLog::create([
            'user_id' => $user->id,
            'login' => $login,
            'success' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        AuditLogger::log('auth.login', $user, null, ['login' => $login, 'channel' => 'web']);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditLogger::log('auth.logout', $user, null, ['channel' => 'web']);

        $tokenId = $request->session()->pull('asset_api_token_id');
        if ($tokenId) {
            $user->tokens()->whereKey($tokenId)->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
