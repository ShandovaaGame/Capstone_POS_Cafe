<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (! Auth::check()) {
            if ($request->is('dapur') || $request->is('dapur/*')) {
                return redirect()->route('dapur.login');
            }

            return redirect()->route('kasir.login');
        }

        if (! in_array(Auth::user()->role, $roles)) {
            if ($request->is('dapur') || $request->is('dapur/*')) {
                return redirect()->route('dapur.login');
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return redirect()->to('/admin/login');
            }

            return redirect()->route('kasir.login');
        }

        return $next($request);
    }
}
