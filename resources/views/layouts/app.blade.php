<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Lapor Tuntas — Lacak sampai tuntas' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-laportuntas.png') }}?v=v2">

    <!-- PWA Manifest & Mobile Standalone Meta -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0A7B9D">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="LaporTuntas">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon-laportuntas.png') }}?v=v2">

    <!-- Google Fonts: Nunito Sans & JetBrains Mono with graceful system fallback -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@600;700;800&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-canvas text-navy antialiased selection:bg-primary-soft selection:text-navy flex flex-col">
    @php
        $isFullBleedMobile = $fullBleedMobile ?? false;
    @endphp

    <!-- Top Navigation Header (Hidden on Mobile Map Home for Full-Bleed Map Experience) -->
    <header class="{{ $isFullBleedMobile ? 'hidden lg:block' : 'block' }} sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-border shadow-[0_2px_10px_rgba(23,50,77,0.05)]">
        <div class="{{ request()->routeIs('home') ? 'w-full px-4 sm:px-6' : 'max-w-[1280px] mx-auto px-4 sm:px-6' }} h-16 flex items-center justify-between gap-2">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group outline-none focus:outline-none focus:ring-0 py-1 select-none">
                <img src="{{ asset('images/logo-laportuntas.png') }}?v=v2"
                     alt="LaporTuntas — Lacak sampai tuntas"
                     class="h-9 sm:h-10 w-auto object-contain">
                <span class="sr-only">Lapor Tuntas</span>
            </a>

            <!-- Desktop Primary Actions -->
            <nav class="hidden lg:flex items-center gap-2.5" aria-label="Navigasi Utama Desktop">
                <a href="{{ route('home') }}"
                   class="min-h-[44px] px-4 py-2 rounded-xl text-sm flex items-center gap-2 transition-colors {{ request()->routeIs('home') ? 'bg-primary-soft text-primary-hover font-extrabold' : 'text-navy hover:bg-canvas font-bold' }}">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                        <line x1="9" x2="9" y1="3" y2="18"/>
                        <line x1="15" x2="15" y1="6" y2="21"/>
                    </svg>
                    <span>Peta</span>
                </a>

                <a href="{{ route('tickets.index') }}"
                   class="min-h-[44px] px-4 py-2 rounded-xl text-sm flex items-center gap-2 border border-border transition-colors {{ request()->routeIs('tickets.*') ? 'bg-navy text-white border-navy font-extrabold' : 'bg-surface text-navy hover:bg-canvas font-bold' }}">
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span>Lacak tiket</span>
                </a>

                @if(request()->routeIs('home'))
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-report-modal'))"
                            class="min-h-[44px] px-5 py-2 rounded-xl bg-primary hover:bg-primary-hover active:scale-[0.99] text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center gap-2 transition cursor-pointer">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        <span>Buat laporan</span>
                    </button>
                @else
                    <a href="{{ route('home') }}#buat-laporan"
                       class="min-h-[44px] px-5 py-2 rounded-xl bg-primary hover:bg-primary-hover active:scale-[0.99] text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center gap-2 transition">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                        <span>Buat laporan</span>
                    </a>
                @endif

                @auth
                    <a href="{{ route('staff.dashboard') }}"
                       class="min-h-[44px] px-4 py-2 rounded-xl bg-navy text-white text-xs font-extrabold flex items-center gap-2 hover:opacity-90 transition"
                       title="Panel Petugas & Admin">
                        <span class="w-2 h-2 rounded-full bg-lime"></span>
                        <span>Panel Petugas</span>
                    </a>
                @else
                    <a href="{{ route('staff.login') }}"
                       class="min-h-[44px] px-4 py-2 rounded-xl text-xs font-bold text-text-muted hover:text-navy hover:bg-canvas flex items-center gap-2 transition"
                       title="Login Khusus Petugas & Admin">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <span>Petugas</span>
                    </a>
                @endauth
            </nav>

            <!-- Compact Mobile Secondary Header Badge (on non-map pages) -->
            <div class="flex lg:hidden items-center gap-2">
                <a href="{{ route('home') }}"
                   class="min-h-[40px] px-3.5 py-2 rounded-full bg-canvas text-xs font-extrabold text-navy flex items-center gap-1.5 border border-border/60">
                    <x-icon name="arrow-left" class="w-3.5 h-3.5" />
                    <span>Peta</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Global Flash Message Banner -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="relative z-40 bg-success-soft border-b border-success/30 text-success px-4 py-3 text-sm font-bold">
            <div class="max-w-[1200px] mx-auto flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <x-icon name="check-circle" class="w-5 h-5 text-success shrink-0" stroke-width="2.2" />
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="p-1 rounded-lg hover:bg-black/5 cursor-pointer flex items-center justify-center text-success" aria-label="Tutup pesan">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1 flex flex-col {{ $isFullBleedMobile ? '' : 'pb-24 lg:pb-0' }}">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar on Non-Map Pages (Matches Reference Layout) -->
    @if(!$isFullBleedMobile)
        <nav aria-label="Navigasi Bawah Mobile"
             class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-surface rounded-t-[28px] shadow-[0_-8px_28px_rgba(23,50,77,0.09)] border-t border-border-soft px-3 pb-[env(safe-area-inset-bottom)]">
            <div class="h-[74px] max-w-md mx-auto grid grid-cols-5 items-center relative">
                <!-- 1. Peta (Home) -->
                <a href="{{ route('home') }}"
                   class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-xl text-text-muted hover:text-navy transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span class="text-[11px] font-bold">Peta</span>
                </a>

                <!-- 2. Sekitar (Discover) -->
                <a href="{{ route('home') }}#sekitar"
                   class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-xl text-text-muted hover:text-navy transition">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span class="text-[11px] font-bold">Sekitar</span>
                </a>

                <!-- Center Raised "+" Action Button (Buat Laporan) -->
                <div class="flex items-center justify-center">
                    <a href="{{ route('home') }}#buat-laporan"
                       aria-label="Buat laporan"
                       class="-mt-9 w-14 h-14 rounded-full bg-navy hover:bg-primary text-white shadow-[0_10px_24px_rgba(23,50,77,0.32)] border-4 border-white flex items-center justify-center transition active:scale-95">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/>
                            <path d="M12 5v14"/>
                        </svg>
                    </a>
                </div>

                <!-- 3. Lacak Tiket -->
                <a href="{{ route('tickets.index') }}"
                   class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-xl transition {{ request()->routeIs('tickets.*', 'reports.success') ? 'bg-gradient-to-b from-primary-soft/40 to-primary-soft/90 text-primary-hover font-extrabold' : 'text-text-muted hover:text-navy' }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <path d="M8 10h.01"/>
                        <path d="M12 10h.01"/>
                        <path d="M16 10h.01"/>
                    </svg>
                    <span class="text-[11px] {{ request()->routeIs('tickets.*', 'reports.success') ? 'font-extrabold' : 'font-bold' }}">Lacak</span>
                </a>

                <!-- 4. Petugas / Admin -->
                <a href="{{ auth()->check() ? route('staff.dashboard') : route('staff.login') }}"
                   class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-xl transition {{ request()->routeIs('staff.*') ? 'bg-gradient-to-b from-primary-soft/40 to-primary-soft/90 text-primary-hover font-extrabold' : 'text-text-muted hover:text-navy' }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span class="text-[11px] {{ request()->routeIs('staff.*') ? 'font-extrabold' : 'font-bold' }}">Petugas</span>
                </a>
            </div>
        </nav>
    @endif
</body>
</html>
