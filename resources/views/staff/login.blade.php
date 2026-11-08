@extends('layouts.app', ['title' => 'Login Petugas & Admin — Lapor Tuntas'])

@section('content')
<div class="max-w-[460px] w-full mx-auto px-4 py-10 sm:py-16">
    <div class="bg-surface rounded-2xl border border-border shadow-[0_8px_24px_rgba(23,50,77,0.08)] p-6 sm:p-8">
        <div class="text-center">
            <img src="{{ asset('images/logo-laportuntas.png') }}?v=v2"
                 alt="LaporTuntas — Lacak sampai tuntas"
                 class="h-10 w-auto mx-auto mb-4 object-contain">
            <span class="inline-block px-3 py-0.5 rounded-full text-xs font-extrabold bg-primary-soft text-primary-hover">
                Portal Internal Dinas
            </span>
            <h1 class="mt-2 text-2xl font-extrabold text-navy">Masuk Petugas atau Admin</h1>
            <p class="mt-1 text-xs sm:text-sm text-text-muted font-semibold">
                Masuk menggunakan akun dinas atau administrator untuk mengelola dan menindaklanjuti laporan warga.
            </p>
        </div>

        @if($errors->any())
            <div class="mt-4 p-3.5 rounded-xl bg-danger/15 border border-danger text-danger text-xs font-bold">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('staff.login.submit') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="staff-email" class="block text-xs font-extrabold text-navy mb-1">Email akun internal</label>
                <input id="staff-email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="nama@instansi.go.id"
                       autocomplete="username"
                       required
                       class="w-full min-h-[48px] px-4 py-2 rounded-xl border border-border text-sm font-bold text-navy placeholder:font-normal placeholder:text-text-muted focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
            </div>

            <div>
                <label for="staff-password" class="block text-xs font-extrabold text-navy mb-1">Kata Sandi</label>
                <input id="staff-password"
                       type="password"
                       name="password"
                       placeholder="Masukkan kata sandi"
                       autocomplete="current-password"
                       required
                       class="w-full min-h-[48px] px-4 py-2 rounded-xl border border-border text-sm font-bold text-navy placeholder:font-normal placeholder:text-text-muted focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
            </div>

            <button type="submit"
                    class="w-full min-h-[48px] px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] transition cursor-pointer active:scale-[0.99]">
                Masuk ke Panel Operasional
            </button>
        </form>
    </div>
</div>
@endsection
