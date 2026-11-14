<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengeluarkan pengguna yang akunnya dinonaktifkan admin, meskipun ia sedang login.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $pesan = 'Your account has been deactivated. Please contact your administrator.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 401);
            }

            return redirect()->route('login')->withErrors(['username' => $pesan]);
        }

        return $next($request);
    }
}
