<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\StaffSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showKitchenLogin()
    {
        return Inertia::render('Dapur/Login');
    }

    public function showCustomerLogin()
    {
        return redirect()->route('customer.menu');
    }

    public function customerLogin(Request $request)
    {
        return redirect()->route('customer.menu');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $key = Str::lower($request->email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Role validation BEFORE authentication attempt
        $existingUser = \App\Models\User::where('email', $request->email)->first();
        if ($existingUser) {
            if ($request->is('dapur/*') && !in_array($existingUser->role, ['kitchen', 'admin'])) {
                return back()->withErrors([
                    'email' => 'Akun ini tidak memiliki akses ke Dapur.',
                ]);
            }
            if ($request->is('kasir/*') && !in_array($existingUser->role, ['cashier', 'admin'])) {
                return back()->withErrors([
                    'email' => 'Akun ini tidak memiliki akses ke Kasir.',
                ]);
            }
        }

        $guard = $request->is('dapur/*') ? 'kitchen' : 'web';

        if (! Auth::guard($guard)->attempt($request->only('email', 'password'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'Email atau kata sandi salah']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::guard($guard)->user();

        app(StaffSessionService::class)->startSession($user);

        // Redirect based on WHERE they logged in from (priority: URL > role)
        if ($request->is('kasir/*')) {
            return Inertia::location(route('kasir.pesanan-baru'));
        }

        if ($request->is('dapur/*')) {
            return Inertia::location(route('dapur.beranda'));
        }

        // Fallback: role-based redirect
        if ($user->role === 'admin') {
            return redirect()->to('/admin');
        }

        if ($user->role === 'kitchen') {
            return Inertia::location(route('dapur.beranda'));
        }

        return Inertia::location(route('kasir.pesanan-baru'));
    }

    public function logout(Request $request)
    {
        $guard = Auth::guard('kitchen')->check() ? 'kitchen' : 'web';
        $user = Auth::guard($guard)->user();
        $role = $user->role ?? 'cashier';

        if ($user && in_array($user->role, ['cashier', 'kitchen'])) {
            $activeSession = app(StaffSessionService::class)->getActiveSession($user);
            if ($activeSession) {
                app(StaffSessionService::class)->endSession($activeSession);
            }
        }

        Auth::guard($guard)->logout();

        // Check if ANY other guard is still authenticated
        $otherGuards = array_diff(['web', 'kitchen', 'admin'], [$guard]);
        $stillActive = false;
        foreach ($otherGuards as $g) {
            if (Auth::guard($g)->check()) {
                $stillActive = true;
                break;
            }
        }

        if (!$stillActive) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($role === 'kitchen') {
            return redirect()->route('dapur.login');
        }

        return redirect()->route('kasir.login');
    }
}
