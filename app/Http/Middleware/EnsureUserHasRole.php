<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Ensure the authenticated user has one of the required roles.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! in_array($user?->role, $roles, true)) {
            if ($request->isMethod('GET') && $user instanceof User && ($user->isAdmin() || $user->isPetugas())) {
                return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard');
            }

            abort(403);
        }

        if ($user instanceof User && $user->isPetugas()) {
            if ($user->email !== User::SHARED_PETUGAS_EMAIL) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('staff.login')
                    ->withErrors(['email' => 'Masuk kembali menggunakan akun petugas bersama.']);
            }
        }

        return $next($request);
    }
}
