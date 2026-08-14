<?php

namespace App\Http\Controllers\Auth;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class FilamentLogoutController
{
    public function __invoke(): LogoutResponse
    {
        Filament::auth()->logout();

        // Conditional invalidate — same pattern as AuthController::logout()
        // Only destroy session if no other guard is still authenticated
        $otherGuards = array_diff(['web', 'kitchen', 'admin'], ['admin']);
        $stillActive = false;
        foreach ($otherGuards as $guard) {
            if (Auth::guard($guard)->check()) {
                $stillActive = true;
                break;
            }
        }

        if (! $stillActive) {
            session()->invalidate();
            session()->regenerateToken();
        }

        return app(LogoutResponse::class);
    }
}
