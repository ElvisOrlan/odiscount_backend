<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::post('/login', static function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'device_name' => ['sometimes', 'string', 'max:255'],
    ]);

    $user = User::query()->where('email', $credentials['email'])->first();

    if (! $user || ! $user->isActive() || ! Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['Adresse e-mail ou mot de passe incorrect, ou compte désactivé.'],
        ]);
    }

    $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

    return response()->json([
        'token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
        ],
    ]);
})->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function (): void {
    Route::get('/user', static function (Request $request) {
        return $request->user()->load('roles');
    });

    Route::post('/logout', static function (Request $request) {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    });

    Route::patch('/users/{user}/status', static function (Request $request, User $user) {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $user->is_active = $validated['active'];
        $user->save();

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'id' => $user->id,
            'is_active' => $user->is_active,
        ]);
    })->middleware('role:Admin|Master');
});
