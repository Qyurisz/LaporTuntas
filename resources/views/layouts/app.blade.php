<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Lapor Tuntas — Bukan Sekadar Lapor, Tapi Lacak Sampai Selesai' }}</title>

    <!-- Google Fonts: Nunito Sans & JetBrains Mono with graceful system fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@600;700;800&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-[#F3F7F8] text-[#17324D] antialiased selection:bg-[#DFF5FC] selection:text-[#17324D] flex flex-col">
    <!-- Top Navigation Header (Style Guide 9.1) -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-[#D7E1E5] shadow-[0_2px_10px_rgba(23,50,77,0.05)]">
        <div class="max-w-[1280px] mx-auto px-3 sm:px-6 h-16 flex items-center justify-between gap-2">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/40 rounded-xl p-1">
                <div class="w-10 h-10 rounded-2xl bg-[#16A6D8] flex items-center justify-center text-white shadow-[0_4px_12px_rgba(22,166,216,0.35)] group-hover:bg-[#0D8BB8] transition-colors">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4.5 8-11.8A8 8 0 0 0 4 10.2C4 17.5 12 22 12 22z"/>
                        <path d="m9 10 2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-extrabold text-lg sm:text-xl tracking-tight text-[#17324D] leading-none">Lapor Tuntas</span>
                        <span class="hidden sm:inline-block px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-[#C5E84A] text-[#17324D] rounded-full">Warga</span>
                    </div>
                    <p class="hidden md:block text-xs text-[#617482] font-semibold mt-0.5">Bukan sekadar lapor, tapi lacak sampai selesai</p>
                </div>
            </a>

            <!-- Primary Actions (Always visible: Peta, Lacak tiket, Buat laporan) -->
            <nav class="flex items-center gap-1.5 sm:gap-2.5" aria-label="Navigasi Utama">
                <a href="{{ route('home') }}"
                   class="min-h-[44px] px-3 sm:px-3.5 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 transition-colors {{ request()->routeIs('home') ? 'bg-[#DFF5FC] text-[#0D8BB8]' : 'text-[#17324D] hover:bg-[#F3F7F8]' }}">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                        <line x1="9" x2="9" y1="3" y2="18"/>
                        <line x1="15" x2="15" y1="6" y2="21"/>
                    </svg>
                    <span class="hidden sm:inline">Peta</span>
                </a>

                <a href="{{ route('tickets.index') }}"
                   class="min-h-[44px] px-3 sm:px-3.5 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5 border border-[#D7E1E5] transition-colors {{ request()->routeIs('tickets.*') ? 'bg-[#17324D] text-white border-[#17324D]' : 'bg-white text-[#17324D] hover:bg-[#F3F7F8]' }}">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span>Lacak tiket</span>
                </a>

                @if(request()->routeIs('home'))
                    <button type="button"
                            @click="$dispatch('open-report-modal')"
                            class="min-h-[44px] px-3.5 sm:px-4 py-2 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] active:scale-[0.99] text-white font-extrabold text-sm sm:text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center gap-1.5 transition cursor-pointer">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        <span>Buat laporan</span>
                    </button>
                @else
                    <a href="{{ route('home') }}#buat-laporan"
                       class="min-h-[44px] px-3.5 sm:px-4 py-2 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-sm sm:text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center gap-1.5 transition">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        <span>Buat laporan</span>
                    </a>
                @endif

                @auth
                    <a href="{{ route('staff.dashboard') }}"
                       class="min-h-[44px] px-3 py-2 rounded-xl bg-[#17324D] text-white text-xs font-extrabold flex items-center gap-1.5 hover:opacity-90 transition"
                       title="Panel Petugas & Admin">
                        <span class="w-2 h-2 rounded-full bg-[#C5E84A]"></span>
                        <span class="hidden lg:inline">Panel Petugas</span>
                    </a>
                @else
                    <a href="{{ route('staff.login') }}"
                       class="min-h-[44px] px-2.5 py-2 rounded-xl text-xs font-bold text-[#617482] hover:text-[#17324D] hover:bg-[#F3F7F8] flex items-center gap-1 transition"
                       title="Login Khusus Petugas & Admin">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span class="hidden xl:inline">Petugas</span>
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Global Flash Message Banner -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="bg-[#E1F5EA] border-b border-[#278653]/30 text-[#185B37] px-4 py-3 text-sm font-bold">
            <div class="max-w-[1200px] mx-auto flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#278653] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <path d="m9 11 3 3L22 4"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="p-1 rounded-lg hover:bg-black/5 cursor-pointer" aria-label="Tutup pesan">
                    ✕
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
</body>
</html>

