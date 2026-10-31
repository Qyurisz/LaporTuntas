@extends('layouts.app', ['title' => 'Login Petugas & Admin — Lapor Tuntas'])

@section('content')
<div class="max-w-[460px] w-full mx-auto px-4 py-10 sm:py-16">
    <div class="bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_8px_24px_rgba(23,50,77,0.08)] p-6 sm:p-8">
        <div class="text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#17324D] text-[#C5E84A] mx-auto flex items-center justify-center mb-3 shadow-sm">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8]">
                Khusus Internal Instansi (FR-21)
            </span>
            <h1 class="mt-2 text-2xl font-extrabold text-[#17324D]">Portal Petugas &amp; Moderator</h1>
            <p class="mt-1 text-xs sm:text-sm text-[#617482] font-semibold">
                Warga tidak memerlukan login untuk melapor atau melacak tiket. Halaman ini hanya untuk petugas dinas dan admin.
            </p>
        </div>

        @if($errors->any())
            <div class="mt-4 p-3.5 rounded-xl bg-[#FF6B5C]/15 border border-[#D94343] text-[#D94343] text-xs font-bold">
                {{ $errors->first() }}
            </div>
        @endif

        <form x-ref="loginForm" action="{{ route('staff.login.submit') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="staff-email" class="block text-xs font-extrabold text-[#17324D] mb-1">Email Dinas / Admin</label>
                <input id="staff-email"
                       x-ref="emailInput"
                       type="email"
                       name="email"
                       value="{{ old('email', 'admin@laportuntas.id') }}"
                       required
                       class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-[#D7E1E5] text-sm font-bold text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]">
            </div>

            <div>
                <label for="staff-password" class="block text-xs font-extrabold text-[#17324D] mb-1">Kata Sandi</label>
                <input id="staff-password"
                       x-ref="passwordInput"
                       type="password"
                       name="password"
                       value="password"
                       required
                       class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-[#D7E1E5] text-sm font-bold text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]">
            </div>

            <button type="submit"
                    class="w-full min-h-[48px] px-6 py-3 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] transition cursor-pointer">
                Masuk ke Panel Operasional
            </button>
        </form>

        <!-- One-Click Demo Accounts Helper -->
        <div x-data class="mt-6 pt-5 border-t border-[#D7E1E5] space-y-2.5">
            <p class="text-xs font-extrabold text-[#617482] uppercase tracking-wider">Akun Simulasi Demo (Klik untuk Masuk Cepat):</p>
            <div class="grid grid-cols-1 gap-2">
                <button type="button"
                        @click="$refs.emailInput.value = 'admin@laportuntas.id'; $refs.passwordInput.value = 'password'; $refs.loginForm.submit();"
                        class="w-full p-2.5 rounded-xl bg-[#F3F7F8] hover:bg-[#DFF5FC] border border-[#D7E1E5] text-left text-xs flex items-center justify-between cursor-pointer transition">
                    <div>
                        <p class="font-extrabold text-[#17324D]">Moderator / Admin Platform</p>
                        <p class="font-mono text-[#617482]">admin@laportuntas.id</p>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-[#17324D] text-white font-extrabold text-[10px]">Admin RBAC</span>
                </button>

                <button type="button"
                        @click="$refs.emailInput.value = 'petugas.pu@laportuntas.id'; $refs.passwordInput.value = 'password'; $refs.loginForm.submit();"
                        class="w-full p-2.5 rounded-xl bg-[#F3F7F8] hover:bg-[#DFF5FC] border border-[#D7E1E5] text-left text-xs flex items-center justify-between cursor-pointer transition">
                    <div>
                        <p class="font-extrabold text-[#17324D]">Pak Heri — Petugas Dinas PU</p>
                        <p class="font-mono text-[#617482]">petugas.pu@laportuntas.id</p>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-[#0D8BB8] text-white font-extrabold text-[10px]">Petugas PU</span>
                </button>

                <button type="button"
                        @click="$refs.emailInput.value = 'petugas.dlh@laportuntas.id'; $refs.passwordInput.value = 'password'; $refs.loginForm.submit();"
                        class="w-full p-2.5 rounded-xl bg-[#F3F7F8] hover:bg-[#DFF5FC] border border-[#D7E1E5] text-left text-xs flex items-center justify-between cursor-pointer transition">
                    <div>
                        <p class="font-extrabold text-[#17324D]">Bu Sari — Petugas DLH</p>
                        <p class="font-mono text-[#617482]">petugas.dlh@laportuntas.id</p>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-[#278653] text-white font-extrabold text-[10px]">Petugas DLH</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

