<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StaffAuthController extends Controller
{
    /**
     * Show the staff (Admin & Officer) login form.
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->isPetugas() && $user->email === User::SHARED_PETUGAS_EMAIL) {
                return redirect()->route('staff.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return view('staff.login');
    }

    /**
     * Authenticate a staff member.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            if (! $user->isAdmin() && (! $user->isPetugas() || $user->email !== User::SHARED_PETUGAS_EMAIL)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'Gunakan akun admin atau akun petugas bersama untuk masuk.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard');
        }

        return back()
            ->withErrors(['email' => 'Email atau kata sandi akun petugas tidak cocok.'])
            ->onlyInput('email');
    }

    /**
     * Log out the authenticated staff member.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}

