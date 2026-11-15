<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion par e-mail ou nom d'utilisateur.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $login = $credentials['login'];

        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        $passwordOk = $user && Hash::check($credentials['password'], $user->password);

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

        $user->forceFill(['last_login_at' => now()])->save();

        LoginLog::create([
            'user_id' => $user->id,
            'login' => $login,
            'success' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        AuditLogger::log('auth.login', $user, null, ['login' => $login]);

        return response()->json([
            'token' => $token,
            'user' => new \App\Http\Resources\UserResource(
                $user->load(['roles', 'structures'])
            ),
        ]);
    }

    /**
     * Déconnexion : révocation du token courant.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        AuditLogger::log('auth.logout', $request->user());

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    /**
     * Profil de l'utilisateur authentifié.
     */
    public function me(Request $request)
    {
        return response()->json([
            'data' => new \App\Http\Resources\UserResource(
                $request->user()->load(['roles', 'structures'])
            ),
        ]);
    }
}
