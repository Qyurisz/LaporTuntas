@extends('layouts.app', [
    'title' => 'Lapor Tuntas — Peta Masalah Publik di Sekitarmu',
    'fullBleedMobile' => true,
])

@section('content')
<div x-data="laporTuntasMapApp()"
     x-init="initApp()"
     @open-report-modal.window="openCreateReportModal()"
     class="relative w-full h-[100dvh] lg:h-[calc(100dvh-4rem)] min-h-[540px] shrink-0 flex flex-col lg:flex-row overflow-hidden bg-[#DCE8EE]">

    <!-- ===================================================================== -->
    <!-- MANDATORY GPS LOCATION PERMISSION GATE (PRD FR-02 & FR-15)             -->
    <!-- ===================================================================== -->
    <div x-show="gpsState !== 'granted'"
         x-transition.opacity
         class="absolute inset-0 z-40 bg-[#17324D]/30 backdrop-blur-[3px] flex items-center justify-center p-4 sm:p-6 pb-24 lg:pb-6 overflow-y-auto">
        <div class="max-w-md w-full bg-white rounded-[28px] border border-[#E6EEF2] shadow-[0_14px_36px_rgba(23,50,77,0.12)] p-6 sm:p-8 text-center">
            <!-- Brand Logo -->
            <img src="{{ asset('images/logo-laportuntas.png') }}?v=hd"
                 alt="LaporTuntas — Lacak sampai tuntas"
                 class="h-10 w-auto mx-auto mb-5 object-contain">

            <!-- Vector Illustration -->
            <div class="mx-auto w-20 h-20 rounded-full bg-[#DFF5FC] border-4 border-white shadow-md flex items-center justify-center mb-4 relative">
                <svg class="w-10 h-10 text-[#16A6D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-[#C5E84A] text-[#17324D] font-extrabold flex items-center justify-center text-[10px] border-2 border-white">
                    GPS
                </span>
            </div>

            <template x-if="gpsState === 'prompt' || gpsState === 'loading'">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8] mb-2.5">
                        Lapor Tuntas • Peta Warga
                    </span>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-[#17324D] leading-snug">
                        Aktifkan lokasi untuk membuka peta laporan di sekitarmu
                    </h1>
                    <p class="mt-2.5 text-xs sm:text-sm text-[#617482] leading-relaxed">
                        Kami menggunakan titik GPS perangkatmu untuk menampilkan masalah publik terdekat dan memastikan lokasi laporan akurat.
                    </p>

                    <div class="mt-4 p-3.5 rounded-2xl bg-[#F3F7F8] text-left text-xs text-[#17324D] space-y-1.5">
                        <div class="flex items-start gap-2">
                            <span class="text-[#278653] font-extrabold">✓</span>
                            <span><strong>Privasi aman:</strong> Posisi kamu tidak disimpan saat menjelajahi peta.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#278653] font-extrabold">✓</span>
                            <span><strong>Tanpa login:</strong> Langsung lapor dan simpan tiket ber-QR.</span>
                        </div>
                    </div>

                    <button type="button"
                            @click="requestGpsLocation()"
                            :disabled="gpsState === 'loading'"
                            class="mt-5 w-full min-h-[48px] px-6 py-3 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] disabled:opacity-60 text-white font-extrabold text-sm sm:text-base shadow-[0_8px_20px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <svg x-show="gpsState !== 'loading'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        <span x-text="gpsState === 'loading' ? 'Mendeteksi titik GPS...' : 'Aktifkan Izin Lokasi'"></span>
                    </button>
                </div>
            </template>

            <!-- State when GPS permission is denied (PRD FR-15) -->
            <template x-if="gpsState === 'denied'">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#FF6B5C]/15 text-[#D94343] mb-2.5">
                        Izin Lokasi Belum Aktif
                    </span>
                    <h1 class="text-xl font-extrabold text-[#17324D]">
                        Peta &amp; form lapor memerlukan akses lokasi
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-[#617482] leading-relaxed" x-text="gpsErrorMessage || 'Izin lokasi ditolak oleh browser perangkat kamu.'"></p>

                    <div class="mt-4 p-3.5 rounded-2xl bg-[#F3F7F8] text-left text-xs text-[#17324D] space-y-1.5">
                        <p class="font-extrabold">Cara mengaktifkan kembali:</p>
                        <ol class="list-decimal list-inside space-y-1 text-[#617482] font-semibold">
                            <li>Ketuk ikon <strong>Pengaturan Situs / Gembok</strong> di bilah alamat browser.</li>
                            <li>Ubah izin <strong>Lokasi (Location)</strong> menjadi <strong>Izinkan (Allow)</strong>.</li>
                            <li>Tekan tombol <strong>Coba Lagi</strong> di bawah ini.</li>
                        </ol>
                    </div>

                    <button type="button"
                            @click="requestGpsLocation()"
                            class="mt-5 w-full min-h-[48px] px-6 py-3 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-sm shadow-[0_8px_20px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <span>Coba Aktifkan Lagi</span>
                    </button>
                </div>
            </template>

            <div class="mt-5 pt-4 border-t border-[#E6EEF2] flex items-center justify-between gap-2 text-xs">
                <span class="text-[#617482] font-semibold">Sudah punya ID tiket?</span>
                <a href="{{ route('tickets.index') }}" class="font-extrabold text-[#0D8BB8] hover:underline">
                    Lacak tiket →
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MAIN FULL-BLEED MAP CANVAS (100% Mobile, 64% Desktop)                  -->
    <!-- ===================================================================== -->
    <div x-ref="mapStage" class="relative w-full h-full flex-1 lg:flex-none lg:w-[64%] min-h-[420px] overflow-hidden isolate">
        <div id="main-map" aria-label="Peta laporan masalah publik di sekitar Anda"></div>

        <!-- TOP FLOATING CONTROLS BAR (Matches Reference Screenshot) -->
        <div class="absolute top-4 inset-x-4 z-20 pointer-events-none flex items-start justify-between gap-2.5">
            <!-- Left Location & Radius Pill Dropdown -->
            <div class="pointer-events-auto relative min-w-0" @click.outside="radiusMenuOpen = false">
                <button type="button"
                        @click="radiusMenuOpen = !radiusMenuOpen; filterPopoverOpen = false"
                        class="min-h-[46px] max-w-full pl-3.5 pr-4 py-2 rounded-full bg-white shadow-[0_8px_24px_rgba(23,50,77,0.14)] hover:bg-[#F3F7F8]/95 flex items-center gap-2 text-[#17324D] transition cursor-pointer">
                    <svg class="w-5 h-5 text-[#16A6D8] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span class="font-extrabold text-sm tracking-tight flex items-center gap-1 min-w-0">
                        <span class="truncate max-w-[130px] sm:max-w-[210px]" x-text="userAreaName || 'Sekitarmu'"></span>
                        <span class="text-[#0D8BB8] font-extrabold tabular-nums shrink-0"
                              x-text="'• ' + (filters.radius < 1000 ? filters.radius + ' m' : (filters.radius / 1000) + ' km')"></span>
                    </span>
                    <svg class="w-4 h-4 text-[#617482] shrink-0 transition-transform duration-150"
                         :class="radiusMenuOpen ? 'rotate-180' : ''"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                <!-- Radius & Map Style Dropdown Card -->
                <div x-show="radiusMenuOpen"
                     x-transition
                     class="mt-2.5 w-72 bg-white rounded-[24px] p-4 shadow-[0_14px_36px_rgba(23,50,77,0.16)] border border-[#E6EEF2] space-y-3.5">
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#617482] mb-2">Mode Tampilan Peta</p>
                        <div class="grid grid-cols-3 gap-1.5 bg-[#F3F7F8] p-1 rounded-2xl">
                            <button type="button"
                                    @click="setMapLayer('jalan')"
                                    :class="activeMapLayer === 'jalan' ? 'bg-[#17324D] text-white shadow-xs' : 'text-[#617482] hover:text-[#17324D]'"
                                    class="min-h-[36px] rounded-xl text-xs font-extrabold transition cursor-pointer">
                                Jalan
                            </button>
                            <button type="button"
                                    @click="setMapLayer('satelit')"
                                    :class="activeMapLayer === 'satelit' ? 'bg-[#17324D] text-white shadow-xs' : 'text-[#617482] hover:text-[#17324D]'"
                                    class="min-h-[36px] rounded-xl text-xs font-extrabold transition cursor-pointer">
                                Satelit
                            </button>
                            <button type="button"
                                    @click="setMapLayer('terang')"
                                    :class="activeMapLayer === 'terang' ? 'bg-[#17324D] text-white shadow-xs' : 'text-[#617482] hover:text-[#17324D]'"
                                    class="min-h-[36px] rounded-xl text-xs font-extrabold transition cursor-pointer">
                                Terang
                            </button>
                        </div>
                    </div>

                    <div class="pt-2.5 border-t border-[#E6EEF2]">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#617482] mb-2">Jangkauan Radius Peta</p>
                        <div class="grid grid-cols-3 gap-1.5 bg-[#F3F7F8] p-1 rounded-2xl">
                            <template x-for="r in [1000, 5000, 15000]" :key="r">
                                <button type="button"
                                        @click="setRadius(r); radiusMenuOpen = false"
                                        :class="filters.radius === r ? 'bg-[#16A6D8] text-white shadow-xs' : 'text-[#617482] hover:text-[#17324D]'"
                                        class="min-h-[36px] rounded-xl text-xs font-extrabold transition cursor-pointer tabular-nums"
                                        x-text="r < 1000 ? r + ' m' : (r / 1000) + ' km'"></button>
                            </template>
                        </div>
                    </div>

                    <div class="pt-2.5 border-t border-[#E6EEF2] space-y-1.5 text-xs font-bold text-[#17324D]">
                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#617482]">Keterangan Warna Pin</p>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 pt-0.5">
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#16A6D8]"></span>Belum diproses</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#7966D9]"></span>Ditugaskan</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#F5B82E]"></span>Diproses</span>
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#278653]"></span>Selesai</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Circular Action Buttons: Layer Toggle, Crosshair GPS & Filter Sliders -->
            <div class="pointer-events-auto flex items-center gap-2 shrink-0">
                <!-- 1. Satellite / Street Map Quick Toggle Button -->
                <button type="button"
                        @click="toggleSatelliteLayer()"
                        :title="activeMapLayer === 'satelit' ? 'Ganti ke Peta Jalan' : 'Ganti ke Citra Satelit'"
                        :aria-label="activeMapLayer === 'satelit' ? 'Ganti ke Peta Jalan' : 'Ganti ke Citra Satelit'"
                        :class="activeMapLayer === 'satelit' ? 'bg-[#17324D] text-white' : 'bg-white text-[#17324D] hover:bg-[#F3F7F8] hover:text-[#16A6D8]'"
                        class="w-[46px] h-[46px] rounded-full shadow-[0_8px_24px_rgba(23,50,77,0.14)] flex items-center justify-center transition cursor-pointer active:scale-95">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                        <polyline points="2 17 12 22 22 17"/>
                        <polyline points="2 12 12 17 22 12"/>
                    </svg>
                </button>

                <!-- 2. GPS Target / Crosshair Button -->
                <button type="button"
                        @click="recenterToUser()"
                        title="Pusatkan ke posisi GPS saya"
                        aria-label="Pusatkan ke posisi GPS saya"
                        class="w-[46px] h-[46px] rounded-full bg-white hover:bg-[#F3F7F8] shadow-[0_8px_24px_rgba(23,50,77,0.14)] flex items-center justify-center text-[#17324D] hover:text-[#16A6D8] transition cursor-pointer active:scale-95">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="7"/>
                        <circle cx="12" cy="12" r="2"/>
                        <line x1="12" x2="12" y1="2" y2="5"/>
                        <line x1="12" x2="12" y1="19" y2="22"/>
                        <line x1="2" x2="5" y1="12" y2="12"/>
                        <line x1="19" x2="22" y1="12" y2="12"/>
                    </svg>
                </button>

                <!-- 3. Filter Sliders Button -->
                <button type="button"
                        @click="filterPopoverOpen = !filterPopoverOpen; radiusMenuOpen = false"
                        title="Filter laporan sekitar"
                        aria-label="Filter laporan sekitar"
                        class="relative w-[46px] h-[46px] rounded-full bg-white hover:bg-[#F3F7F8] shadow-[0_8px_24px_rgba(23,50,77,0.14)] flex items-center justify-center text-[#17324D] hover:text-[#16A6D8] transition cursor-pointer active:scale-95">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" x2="10" y1="8" y2="8"/>
                        <line x1="14" x2="20" y1="8" y2="8"/>
                        <circle cx="12" cy="8" r="2"/>
                        <line x1="4" x2="14" y1="16" y2="16"/>
                        <line x1="18" x2="20" y1="16" y2="16"/>
                        <circle cx="16" cy="16" r="2"/>
                    </svg>
                    <span x-show="filters.status !== 'all' || filters.category !== 'all'"
                          class="absolute top-2.5 right-2.5 w-2.5 h-2.5 rounded-full bg-[#16A6D8] ring-2 ring-white"></span>
                </button>
            </div>
        </div>

        <!-- FLOATING FILTER POPOVER (Opened via Top-Right Sliders Icon) -->
        <div x-show="filterPopoverOpen"
             x-transition
             @click.outside="filterPopoverOpen = false"
             class="absolute top-[70px] left-4 right-4 sm:left-auto sm:right-4 sm:w-[350px] z-30 bg-white rounded-[26px] p-4 sm:p-5 shadow-[0_16px_40px_rgba(23,50,77,0.18)] border border-[#E6EEF2] space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-[#17324D]">Filter Laporan Peta</h3>
                <button type="button"
                        x-show="filters.status !== 'all' || filters.category !== 'all'"
                        @click="setStatusFilter('all'); setCategoryFilter('all')"
                        class="text-xs font-extrabold text-[#0D8BB8] hover:underline cursor-pointer">
                    Reset Filter
                </button>
            </div>

            <!-- Status Chips -->
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#617482] mb-2">Status Laporan</p>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button"
                            @click="setStatusFilter('all')"
                            :class="filters.status === 'all' ? 'bg-[#17324D] text-white' : 'bg-[#F3F7F8] text-[#17324D]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Semua
                    </button>
                    <button type="button"
                            @click="setStatusFilter('baru')"
                            :class="filters.status === 'baru' ? 'bg-[#16A6D8] text-white' : 'bg-[#F3F7F8] text-[#17324D]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Belum diproses
                    </button>
                    <button type="button"
                            @click="setStatusFilter('ditugaskan')"
                            :class="filters.status === 'ditugaskan' ? 'bg-[#7966D9] text-white' : 'bg-[#F3F7F8] text-[#17324D]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Ditugaskan
                    </button>
                    <button type="button"
                            @click="setStatusFilter('diproses')"
                            :class="filters.status === 'diproses' ? 'bg-[#F5B82E] text-[#17324D]' : 'bg-[#F3F7F8] text-[#17324D]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Diproses
                    </button>
                    <button type="button"
                            @click="setStatusFilter('selesai')"
                            :class="filters.status === 'selesai' ? 'bg-[#278653] text-white' : 'bg-[#F3F7F8] text-[#17324D]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Selesai
                    </button>
                </div>
            </div>

            <!-- Category Chips -->
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-[#617482] mb-2">Kategori Masalah</p>
                <div class="flex flex-wrap gap-1.5 max-h-32 overflow-y-auto">
                    <button type="button"
                            @click="setCategoryFilter('all')"
                            :class="filters.category === 'all' ? 'bg-[#DFF5FC] text-[#0D8BB8] ring-1 ring-[#16A6D8]' : 'bg-[#F3F7F8] text-[#617482]'"
                            class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                        Semua Kategori
                    </button>
                    @foreach($categories as $category)
                        <button type="button"
                                @click="setCategoryFilter('{{ $category->slug }}')"
                                :class="filters.category === '{{ $category->slug }}' ? 'bg-[#DFF5FC] text-[#0D8BB8] ring-1 ring-[#16A6D8]' : 'bg-[#F3F7F8] text-[#617482]'"
                                class="px-3 py-1.5 rounded-full text-xs font-extrabold transition cursor-pointer">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <button type="button"
                    @click="filterPopoverOpen = false"
                    class="w-full min-h-[42px] rounded-full bg-[#16A6D8] text-white text-xs font-extrabold cursor-pointer">
                Terapkan Filter
            </button>
        </div>

        <!-- ================================================================= -->
        <!-- CENTER FLOATING CALLOUT POPOVER CARD (Matches Reference Image!)   -->
        <!-- ================================================================= -->
        <div x-show="selectedReport && activeMobileTab === 'peta'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             class="absolute top-[16%] sm:top-[18%] inset-x-4 max-w-[348px] mx-auto z-20 pointer-events-auto">
            <template x-if="selectedReport">
                <div class="bg-white rounded-[26px] p-4 shadow-[0_16px_40px_rgba(23,50,77,0.16)] border border-white relative lt-callout-card">
                    <!-- Top Content: Left Square Photo + Right Metadata -->
                    <div class="flex items-center gap-3.5">
                        <template x-if="selectedReport.photo_url">
                            <img :src="selectedReport.photo_url"
                                 x-on:error="selectedReport.photo_url = null"
                                 :alt="selectedReport.title"
                                 class="w-[72px] h-[72px] rounded-[18px] object-cover shrink-0 bg-[#F3F7F8]">
                        </template>
                        <template x-if="!selectedReport.photo_url">
                            <div class="w-[72px] h-[72px] rounded-[18px] flex items-center justify-center text-white font-extrabold text-lg shrink-0"
                                 :style="`background-color: ${selectedReport.status_color}`"
                                 x-text="selectedReport.category_name.slice(0, 2)"></div>
                        </template>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-[15px] font-extrabold text-[#17324D] truncate" x-text="selectedReport.title"></h3>

                            <!-- Timestamp + Status Pill Row -->
                            <div class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-[#617482]">
                                <svg class="w-3.5 h-3.5 shrink-0 text-[#617482]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                    <line x1="16" x2="16" y1="2" y2="6"/>
                                    <line x1="8" x2="8" y1="2" y2="6"/>
                                    <line x1="3" x2="21" y1="10" y2="10"/>
                                </svg>
                                <span class="truncate" x-text="selectedReport.created_human"></span>
                                <span>•</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold shrink-0"
                                      :style="`background:${selectedReport.status_bg}; color:${selectedReport.status_text}`"
                                      x-text="selectedReport.status_label"></span>
                            </div>

                            <!-- Location Pin Row -->
                            <div class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-[#617482]">
                                <svg class="w-3.5 h-3.5 shrink-0 text-[#617482]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span class="truncate" x-text="selectedReport.location_label"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Dual Pill Action Buttons (Matches "Next time" & "Going" in Reference) -->
                    <div class="mt-4 grid grid-cols-2 gap-2.5">
                        <button type="button"
                                @click="selectedReport = null"
                                class="min-h-[44px] px-4 py-2 rounded-full border border-[#D7E1E5] bg-white hover:bg-[#F3F7F8] text-[#17324D] text-xs sm:text-sm font-bold transition cursor-pointer">
                            Tutup
                        </button>
                        <a :href="selectedReport.track_url"
                           class="min-h-[44px] px-4 py-2 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-xs sm:text-sm font-extrabold shadow-[0_6px_16px_rgba(22,166,216,0.34)] flex items-center justify-center transition">
                            Lihat detail
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- ================================================================= -->
        <!-- MOBILE BOTTOM HORIZONTAL PEEKING CARDS CAROUSEL (Matches Ref!)    -->
        <!-- ================================================================= -->
        <div x-show="gpsState === 'granted' && activeMobileTab === 'peta'"
             class="lg:hidden absolute bottom-[96px] inset-x-0 z-20 pointer-events-none">

            <!-- Loading Skeleton Card -->
            <template x-if="isLoadingNearby">
                <div class="px-4 pointer-events-auto">
                    <div class="w-[85%] bg-white rounded-[22px] p-3 shadow-[0_10px_28px_rgba(23,50,77,0.12)] flex items-center gap-3.5 animate-pulse">
                        <div class="w-16 h-16 rounded-2xl bg-[#D7E1E5] shrink-0"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 w-3/4 bg-[#D7E1E5] rounded"></div>
                            <div class="h-3 w-1/2 bg-[#F3F7F8] rounded"></div>
                            <div class="h-3 w-1/3 bg-[#F3F7F8] rounded"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty State Compact Floating Card -->
            <template x-if="!isLoadingNearby && reports.length === 0">
                <div class="px-4 pointer-events-auto">
                    <div class="bg-white rounded-[22px] p-3.5 shadow-[0_10px_28px_rgba(23,50,77,0.12)] flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-extrabold text-[#17324D] truncate">Belum ada laporan di radius ini</p>
                            <p class="text-xs text-[#617482] font-semibold truncate">Perluas jangkauan radius atau buat laporan baru</p>
                        </div>
                        <button type="button"
                                @click="openCreateReportModal()"
                                class="min-h-[40px] px-3.5 py-2 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-xs font-extrabold shrink-0 cursor-pointer transition">
                            ＋ Lapor
                        </button>
                    </div>
                </div>
            </template>

            <!-- Horizontal Snap-Scroll Peeking Cards (Matches "Beachside Picnic" Card) -->
            <template x-if="!isLoadingNearby && reports.length > 0">
                <div class="flex overflow-x-auto snap-x snap-mandatory gap-3 px-4 pb-2 no-scrollbar pointer-events-auto">
                    <template x-for="report in reports" :key="'mob-card-' + report.id">
                        <article @click="focusReportOnMap(report)"
                                 :class="selectedReport && selectedReport.id === report.id ? 'ring-2 ring-[#16A6D8]' : ''"
                                 class="w-[84%] max-w-[330px] shrink-0 snap-start bg-white rounded-[22px] p-3 shadow-[0_10px_28px_rgba(23,50,77,0.12)] flex items-center gap-3.5 cursor-pointer transition active:scale-[0.99]">
                            <!-- Square Rounded Photo Thumbnail -->
                            <template x-if="report.photo_url">
                                <img :src="report.photo_url"
                                     x-on:error="report.photo_url = null"
                                     :alt="report.title"
                                     class="w-16 h-16 rounded-[16px] object-cover shrink-0 bg-[#F3F7F8]">
                            </template>
                            <template x-if="!report.photo_url">
                                <div class="w-16 h-16 rounded-[16px] flex items-center justify-center text-white font-extrabold shrink-0"
                                     :style="`background-color: ${report.status_color}`"
                                     x-text="report.category_name.slice(0, 2)"></div>
                            </template>

                            <!-- Right Text Block -->
                            <div class="min-w-0 flex-1">
                                <h3 class="text-[15px] font-extrabold text-[#17324D] truncate" x-text="report.title"></h3>

                                <p class="mt-0.5 flex items-center gap-1 text-xs text-[#617482] font-semibold truncate">
                                    <svg class="w-3.5 h-3.5 shrink-0 text-[#617482]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="truncate" x-text="report.location_label"></span>
                                </p>

                                <div class="mt-1 flex items-center gap-1.5 text-xs text-[#617482]">
                                    <span class="w-2 h-2 rounded-full shrink-0" :style="`background-color: ${report.status_color}`"></span>
                                    <span class="font-extrabold text-[#17324D]" x-text="report.status_label"></span>
                                    <span>•</span>
                                    <span class="font-extrabold text-[#0D8BB8] tabular-nums" x-text="report.distance_label"></span>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MOBILE "SEKITAR" FULL LIST DRAWER (Accessible List Alternative)       -->
    <!-- ===================================================================== -->
    <div x-show="activeMobileTab === 'sekitar'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="lg:hidden fixed inset-x-0 top-12 bottom-[74px] z-30 bg-white rounded-t-[28px] shadow-[0_-12px_36px_rgba(23,50,77,0.18)] border-t border-[#E6EEF2] flex flex-col overflow-hidden">
        <div class="p-4 border-b border-[#E6EEF2] flex items-center justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-[#17324D] flex items-center gap-2">
                    <span>Daftar Laporan Sekitar</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8] tabular-nums"
                          x-text="reports.length"></span>
                </h2>
                <p class="text-xs text-[#617482] font-semibold">
                    Radius <span class="font-extrabold text-[#17324D] tabular-nums" x-text="filters.radius < 1000 ? filters.radius + ' m' : (filters.radius / 1000) + ' km'"></span> • Ketuk kartu untuk lihat di peta
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button"
                        x-show="filters.status !== 'all' || filters.category !== 'all'"
                        @click="setStatusFilter('all'); setCategoryFilter('all')"
                        class="min-h-[38px] px-3 py-1.5 rounded-full bg-[#DFF5FC] text-xs font-extrabold text-[#0D8BB8] cursor-pointer">
                    Reset Filter
                </button>
                <button type="button"
                        @click="activeMobileTab = 'peta'"
                        class="min-h-[38px] px-3.5 py-1.5 rounded-full bg-[#F3F7F8] text-xs font-extrabold text-[#17324D] cursor-pointer">
                    Tutup ✕
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-if="reports.length === 0">
                <div class="p-6 text-center rounded-2xl bg-[#F3F7F8]">
                    <p class="text-sm font-extrabold text-[#17324D]">Belum ada laporan di radius ini</p>
                    <p class="mt-1 text-xs text-[#617482] font-semibold">Temukan masalah fasilitas umum? Kirim laporan pertamamu sekarang.</p>
                    <button type="button"
                            @click="activeMobileTab = 'peta'; openCreateReportModal()"
                            class="mt-3 min-h-[42px] px-4 py-2 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-xs font-extrabold cursor-pointer transition">
                        ＋ Buat Laporan Baru
                    </button>
                </div>
            </template>

            <template x-for="report in reports" :key="'drawer-item-' + report.id">
                <article @click="activeMobileTab = 'peta'; focusReportOnMap(report)"
                         class="p-3.5 rounded-[20px] border border-[#E6EEF2] bg-white shadow-2xs flex items-center gap-3.5 cursor-pointer">
                    <template x-if="report.photo_url">
                        <img :src="report.photo_url"
                             x-on:error="report.photo_url = null"
                             :alt="report.title"
                             class="w-16 h-16 rounded-2xl object-cover shrink-0 bg-[#F3F7F8]">
                    </template>
                    <template x-if="!report.photo_url">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 text-white font-extrabold"
                             :style="`background-color: ${report.status_color}`"
                             x-text="report.category_name.slice(0, 2)"></div>
                    </template>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-extrabold text-[#0D8BB8] truncate" x-text="report.category_name"></span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold shrink-0"
                                  :style="`background:${report.status_bg}; color:${report.status_text}`"
                                  x-text="report.status_label"></span>
                        </div>
                        <h3 class="text-sm font-extrabold text-[#17324D] truncate mt-0.5" x-text="report.title"></h3>
                        <p class="text-xs text-[#617482] font-semibold mt-0.5 truncate"
                           x-text="report.distance_label + ' • ' + report.location_label"></p>
                    </div>
                </article>
            </template>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MOBILE BOTTOM NAVIGATION BAR + RAISED "+" BUTTON (Matches Reference!) -->
    <!-- ===================================================================== -->
    <nav aria-label="Navigasi Bawah Mobile"
         class="lg:hidden fixed bottom-0 inset-x-0 z-30 bg-white rounded-t-[26px] shadow-[0_-8px_28px_rgba(23,50,77,0.09)] border-t border-[#E6EEF2] px-3 pb-[env(safe-area-inset-bottom)]">
        <div class="h-[74px] max-w-md mx-auto grid grid-cols-5 items-center relative">
            <!-- 1. Peta (Home) -->
            <button type="button"
                    @click="activeMobileTab = 'peta'"
                    :class="activeMobileTab === 'peta'
                        ? 'bg-gradient-to-b from-[#DFF5FC]/35 to-[#DFF5FC]/90 text-[#0D8BB8] font-extrabold'
                        : 'text-[#617482] hover:text-[#17324D] font-bold'"
                    class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-2xl transition cursor-pointer">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span class="text-[11px]">Peta</span>
            </button>

            <!-- 2. Sekitar (Discover) -->
            <button type="button"
                    @click="activeMobileTab = (activeMobileTab === 'sekitar' ? 'peta' : 'sekitar')"
                    :class="activeMobileTab === 'sekitar'
                        ? 'bg-gradient-to-b from-[#DFF5FC]/35 to-[#DFF5FC]/90 text-[#0D8BB8] font-extrabold'
                        : 'text-[#617482] hover:text-[#17324D] font-bold'"
                    class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-2xl transition cursor-pointer">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <span class="text-[11px]">Sekitar</span>
            </button>

            <!-- Center Raised Circular "+" Action Button (Buat Laporan) -->
            <div class="flex items-center justify-center">
                <button type="button"
                        @click="openCreateReportModal()"
                        aria-label="Buat laporan baru"
                        title="Buat laporan baru"
                        class="-mt-9 w-14 h-14 rounded-full bg-[#17324D] hover:bg-[#16A6D8] text-white shadow-[0_10px_24px_rgba(23,50,77,0.34)] border-4 border-white flex items-center justify-center transition active:scale-95 cursor-pointer">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="M12 5v14"/>
                    </svg>
                </button>
            </div>

            <!-- 3. Lacak Tiket -->
            <a href="{{ route('tickets.index') }}"
               class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-2xl text-[#617482] hover:text-[#17324D] font-bold transition">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    <path d="M8 10h.01"/>
                    <path d="M12 10h.01"/>
                    <path d="M16 10h.01"/>
                </svg>
                <span class="text-[11px]">Lacak</span>
            </a>

            <!-- 4. Petugas / Admin -->
            <a href="{{ auth()->check() ? route('staff.dashboard') : route('staff.login') }}"
               class="flex flex-col items-center justify-center gap-1 h-[62px] rounded-2xl text-[#617482] hover:text-[#17324D] font-bold transition">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span class="text-[11px]">Petugas</span>
            </a>
        </div>
    </nav>

    <!-- ===================================================================== -->
    <!-- DESKTOP RIGHT SIDEBAR PANEL (Clean Report List Only — No Redundancy!) -->
    <!-- ===================================================================== -->
    <aside class="hidden lg:flex lg:w-[36%] lg:h-full bg-white border-l border-[#E6EEF2] z-20 flex-col">
        <!-- Compact Single Header -->
        <div class="px-5 py-4 border-b border-[#E6EEF2] flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-[#17324D] flex items-center gap-2">
                    <span>Laporan di sekitarmu</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8] tabular-nums"
                          x-text="reports.length"></span>
                </h2>
                <p class="text-xs text-[#617482] font-semibold mt-0.5">
                    <span x-text="filters.status === 'all' && filters.category === 'all' ? 'Menampilkan semua laporan dalam radius' : 'Menampilkan laporan terfilter dalam radius'"></span>
                    <span class="font-extrabold text-[#17324D] tabular-nums"
                          x-text="filters.radius < 1000 ? filters.radius + ' m' : (filters.radius / 1000) + ' km'"></span>
                </p>
            </div>

            <button type="button"
                    x-show="filters.status !== 'all' || filters.category !== 'all'"
                    @click="setStatusFilter('all'); setCategoryFilter('all')"
                    class="min-h-[36px] px-3 py-1.5 rounded-full bg-[#DFF5FC] hover:bg-[#16A6D8] text-[#0D8BB8] hover:text-white text-xs font-extrabold transition cursor-pointer shrink-0">
                Reset Filter ✕
            </button>
        </div>

        <!-- Desktop Scrollable Report List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-if="isLoadingNearby">
                <div class="space-y-3">
                    <template x-for="i in [1, 2, 3]" :key="'desk-skel-' + i">
                        <div class="p-4 rounded-[20px] border border-[#D7E1E5] bg-white animate-pulse space-y-2.5">
                            <div class="flex justify-between">
                                <div class="h-4 w-28 bg-[#D7E1E5] rounded"></div>
                                <div class="h-5 w-20 bg-[#D7E1E5] rounded-full"></div>
                            </div>
                            <div class="h-4 w-full bg-[#F3F7F8] rounded"></div>
                            <div class="h-3 w-2/3 bg-[#F3F7F8] rounded"></div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!isLoadingNearby && reports.length === 0">
                <div class="p-6 rounded-[20px] border border-dashed border-[#D7E1E5] bg-[#F3F7F8]/60 text-center">
                    <h3 class="text-base font-extrabold text-[#17324D]">Belum ada laporan pada radius ini</h3>
                    <p class="text-xs text-[#617482] font-semibold mt-1 leading-relaxed">
                        Perluas jangkauan radius atau buat laporan baru jika kamu menemukan masalah fasilitas umum di sekitarmu.
                    </p>
                    <div class="mt-4">
                        <button type="button"
                                @click="openCreateReportModal()"
                                class="w-full min-h-[44px] px-4 py-2.5 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-sm font-extrabold transition cursor-pointer">
                            ＋ Buat laporan pertama di sini
                        </button>
                    </div>
                </div>
            </template>

            <template x-for="report in reports" :key="'desk-card-' + report.id">
                <article @click="focusReportOnMap(report)"
                         :class="selectedReport && selectedReport.id === report.id ? 'border-[#16A6D8] bg-[#DFF5FC]/25 ring-2 ring-[#16A6D8]/30' : 'border-[#E6EEF2] bg-white hover:border-[#16A6D8]/60'"
                         class="p-3.5 rounded-[22px] border shadow-[0_4px_14px_rgba(23,50,77,0.05)] transition cursor-pointer flex items-center gap-3.5">
                    <template x-if="report.photo_url">
                        <img :src="report.photo_url"
                             x-on:error="report.photo_url = null"
                             :alt="report.title"
                             class="w-16 h-16 rounded-[16px] object-cover shrink-0 bg-[#F3F7F8]">
                    </template>
                    <template x-if="!report.photo_url">
                        <div class="w-16 h-16 rounded-[16px] flex items-center justify-center shrink-0 text-white font-extrabold"
                             :style="`background-color: ${report.status_color}`"
                             x-text="report.category_name.slice(0, 2)"></div>
                    </template>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-extrabold text-[#0D8BB8] truncate" x-text="report.category_name"></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold shrink-0"
                                  :style="`background-color: ${report.status_bg}; color: ${report.status_text}`"
                                  x-text="report.status_label"></span>
                        </div>

                        <h3 class="text-sm font-extrabold text-[#17324D] line-clamp-1 mt-0.5" x-text="report.title"></h3>

                        <p class="text-xs text-[#617482] font-semibold mt-1 truncate">
                            <span class="font-extrabold text-[#17324D] tabular-nums" x-text="report.distance_label"></span>
                            <span> • </span>
                            <span x-text="report.location_label"></span>
                        </p>

                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-mono font-bold text-[#617482]" x-text="report.ticket_id"></span>
                            <a :href="report.track_url"
                               @click.stop
                               class="font-extrabold text-[#0D8BB8] hover:underline">
                                Detail →
                            </a>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </aside>

    <!-- ===================================================================== -->
    <!-- MODAL FORMULIR PELAPORAN TANPA AKUN (Style Guide 9.4 & PRD FR-01..03) -->
    <!-- ===================================================================== -->
    <div x-show="reportModalOpen"
         x-transition.opacity
         @keydown.escape.window="closeCreateReportModal()"
         class="fixed inset-0 z-50 bg-[#17324D]/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modal-report-title">

        <div @click.outside="closeCreateReportModal()"
             class="w-full max-w-2xl bg-white rounded-t-[28px] sm:rounded-[28px] border border-[#E6EEF2] shadow-[0_14px_40px_rgba(23,50,77,0.25)] max-h-[92dvh] flex flex-col overflow-hidden">

            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-[#E6EEF2] flex items-center justify-between bg-[#F3F7F8]">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#0D8BB8]">Tanpa Login • Dibawah 1 Menit</span>
                    <h2 id="modal-report-title" class="text-lg sm:text-xl font-extrabold text-[#17324D]">Buat Laporan Masalah Publik</h2>
                </div>
                <button type="button"
                        @click="closeCreateReportModal()"
                        class="min-w-[44px] min-h-[44px] rounded-full hover:bg-white text-[#617482] hover:text-[#17324D] font-extrabold flex items-center justify-center cursor-pointer"
                        aria-label="Tutup formulir laporan">
                    ✕
                </button>
            </div>

            <!-- Form Content -->
            <form action="{{ route('reports.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  @submit.prevent="submitReportForm($event)"
                  class="flex-1 overflow-y-auto p-5 space-y-5">
                @csrf

                @if($errors->any())
                    <div class="p-4 rounded-2xl bg-[#FF6B5C]/15 border border-[#D94343] text-[#D94343] text-xs sm:text-sm font-bold space-y-1">
                        <p class="font-extrabold">Periksa kembali isian formulir kamu:</p>
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- 1. Foto Bukti -->
                <div>
                    <label for="report-photos" class="block text-sm font-extrabold text-[#17324D] mb-1">
                        1. Foto bukti masalah <span class="text-[#D94343]">*</span>
                        <span class="text-xs font-semibold text-[#617482] ml-1">(1 sampai 5 foto, dikompresi otomatis)</span>
                    </label>

                    <input id="report-photos"
                           x-ref="photoInput"
                           type="file"
                           name="photos[]"
                           accept="image/jpeg,image/png,image/webp"
                           multiple
                           required
                           @change="handlePhotoSelection($event)"
                           class="w-full min-h-[48px] px-3.5 py-2.5 rounded-2xl border border-dashed border-[#16A6D8] bg-[#DFF5FC]/35 text-sm font-bold text-[#17324D] file:mr-3 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:bg-[#16A6D8] file:text-white file:font-extrabold hover:bg-[#DFF5FC]/60 cursor-pointer">

                    <p x-show="isCompressingPhotos" class="mt-2 text-xs font-bold text-[#0D8BB8]">
                        ⏳ Mengoptimalkan ukuran foto agar cepat diunggah...
                    </p>
                    <p x-show="photoError" x-text="photoError" class="mt-2 text-xs font-bold text-[#D94343]"></p>

                    <div x-show="photoPreviews.length > 0" class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-2.5">
                        <template x-for="(src, idx) in photoPreviews" :key="idx">
                            <div class="relative aspect-square rounded-2xl overflow-hidden border border-[#D7E1E5] bg-[#F3F7F8]">
                                <img :src="src" class="w-full h-full object-cover" alt="Pratinjau foto bukti">
                                <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-full bg-[#17324D]/80 text-white text-[10px] font-bold"
                                      x-text="'#' + (idx + 1)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 2. Kategori Masalah -->
                <div>
                    <label class="block text-sm font-extrabold text-[#17324D] mb-2">
                        2. Pilih kategori masalah <span class="text-[#D94343]">*</span>
                    </label>
                    <input type="hidden" name="category_id" :value="form.categoryId" required>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        @foreach($categories as $category)
                            <button type="button"
                                    @click="form.categoryId = {{ $category->id }}"
                                    :class="form.categoryId === {{ $category->id }}
                                        ? 'border-2 border-[#16A6D8] bg-[#DFF5FC]/60 text-[#17324D]'
                                        : 'border border-[#D7E1E5] bg-white text-[#17324D] hover:bg-[#F3F7F8]'"
                                    class="min-h-[54px] p-3 rounded-2xl text-left flex items-center gap-2.5 transition cursor-pointer">
                                <div class="w-9 h-9 rounded-xl bg-[#16A6D8]/15 text-[#0D8BB8] flex items-center justify-center shrink-0">
                                    <x-category-icon :icon="$category->icon" class="w-5 h-5" />
                                </div>
                                <span class="text-xs font-extrabold leading-snug">{{ $category->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Lokasi GPS & Peta Mini -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-extrabold text-[#17324D]">
                            3. Titik lokasi masalah (GPS) <span class="text-[#D94343]">*</span>
                        </label>
                        <span class="text-xs font-mono font-bold text-[#0D8BB8] tabular-nums"
                              x-text="form.latitude && form.longitude ? form.latitude.toFixed(5) + ', ' + form.longitude.toFixed(5) : ''"></span>
                    </div>

                    <input type="hidden" name="latitude" :value="form.latitude">
                    <input type="hidden" name="longitude" :value="form.longitude">

                    <div id="mini-picker-map" class="w-full h-52 rounded-2xl border border-[#D7E1E5] overflow-hidden bg-[#DCE8EE]"></div>
                    <p class="mt-1.5 text-xs text-[#617482] font-semibold">
                        💡 Geser penanda atau ketuk titik pada peta asli (bisa mode Satelit) jika masalah berada di seberang jalan.
                    </p>

                    <div x-show="detectedStreetAddress" class="mt-2 p-2.5 rounded-xl bg-[#DFF5FC]/45 border border-[#16A6D8]/30 flex items-center justify-between gap-2 text-xs">
                        <span class="font-bold text-[#17324D] truncate">
                            📍 <span x-text="detectedStreetAddress"></span>
                        </span>
                        <button type="button"
                                @click="form.locationLabel = detectedStreetAddress"
                                class="px-2.5 py-1 rounded-lg bg-[#16A6D8] text-white font-extrabold shrink-0 cursor-pointer">
                            Pakai alamat
                        </button>
                    </div>

                    <div class="mt-2.5">
                        <label for="location-label" class="block text-xs font-extrabold text-[#17324D] mb-1">
                            Patokan lokasi singkat (opsional)
                        </label>
                        <input id="location-label"
                               type="text"
                               name="location_label"
                               x-model="form.locationLabel"
                               placeholder="Contoh: Depan minimarket / dekat persimpangan jalan"
                               class="w-full min-h-[48px] px-4 py-2.5 rounded-2xl border border-[#D7E1E5] text-sm text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]">
                    </div>
                </div>

                <!-- 4. Deskripsi Singkat -->
                <div>
                    <label for="report-description" class="block text-sm font-extrabold text-[#17324D] mb-1">
                        4. Deskripsi singkat masalah <span class="text-[#D94343]">*</span>
                    </label>
                    <textarea id="report-description"
                              name="description"
                              rows="3"
                              required
                              minlength="10"
                              maxlength="1000"
                              x-model="form.description"
                              placeholder="Jelaskan kondisi masalah secara singkat (minimal 10 karakter)..."
                              class="w-full p-3.5 rounded-2xl border border-[#D7E1E5] text-sm text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]"></textarea>
                </div>

                <!-- 5. Pratinjau Laporan Sekitar -->
                <div x-show="reports.length > 0" class="p-3.5 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5]">
                    <p class="text-xs font-extrabold text-[#17324D]">
                        🔎 Ada <span x-text="reports.length"></span> laporan di sekitar titik kamu:
                    </p>
                    <div class="mt-2 flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                        <template x-for="nearbyItem in reports.slice(0, 3)" :key="'modal-near-' + nearbyItem.id">
                            <a :href="nearbyItem.track_url"
                               target="_blank"
                               class="p-2.5 rounded-xl bg-white border border-[#D7E1E5] min-w-[200px] max-w-[220px] shrink-0 hover:border-[#16A6D8]">
                                <p class="text-[11px] font-extrabold text-[#0D8BB8]" x-text="nearbyItem.category_name + ' • ' + nearbyItem.distance_label"></p>
                                <p class="text-xs font-bold text-[#17324D] truncate mt-0.5" x-text="nearbyItem.title"></p>
                            </a>
                        </template>
                    </div>
                </div>

                <!-- 6. Tombol Kirim Laporan -->
                <div class="pt-2 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                    <button type="button"
                            @click="closeCreateReportModal()"
                            class="w-full sm:w-auto min-h-[48px] px-6 py-2.5 rounded-full bg-white border border-[#D7E1E5] text-[#17324D] font-extrabold text-sm hover:bg-[#F3F7F8] cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmitting || isCompressingPhotos"
                            class="w-full sm:w-auto min-h-[48px] px-7 py-3 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] disabled:opacity-60 text-white font-extrabold text-[15px] shadow-[0_6px_18px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <span x-text="isSubmitting ? 'Sedang mengirim...' : 'Kirim laporan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function laporTuntasMapApp() {
        // Keep all Leaflet & MarkerCluster instances outside Alpine's reactive Proxy
        const urlParams = new URLSearchParams(window.location.search);
        const focusLatParam = parseFloat(urlParams.get('lat'));
        const focusLngParam = parseFloat(urlParams.get('lng'));

        const mapRefs = {
            mainMap: null,
            mapLayers: null,
            userMarker: null,
            radiusCircle: null,
            clusterGroup: null,
            miniMap: null,
            miniMarker: null,
            moveTimer: null,
            suppressNextMoveFetch: false,
            hasInitialGpsJump: false,
            lastSearchLat: null,
            lastSearchLng: null,
            pendingFocusTicket: (urlParams.get('focus') || '').trim().toUpperCase() || null,
            pendingFocusLat: Number.isFinite(focusLatParam) ? focusLatParam : null,
            pendingFocusLng: Number.isFinite(focusLngParam) ? focusLngParam : null,
        };

        const distanceBetweenMeters = (lat1, lng1, lat2, lng2) => {
            const R = 6371000;
            const toRad = (deg) => (deg * Math.PI) / 180;
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lng2 - lng1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };

        return {
            gpsState: 'prompt',
            gpsErrorMessage: '',
            userLat: null,
            userLng: null,
            userAreaName: '',
            detectedStreetAddress: '',
            activeMapLayer: 'jalan', // 'jalan' | 'satelit' | 'terang'
            reports: [],
            selectedReport: null,
            isLoadingNearby: false,
            activeMobileTab: 'peta', // 'peta' | 'sekitar'
            radiusMenuOpen: false,
            filterPopoverOpen: false,
            reportModalOpen: false,
            isCompressingPhotos: false,
            isSubmitting: false,
            photoError: '',
            photoPreviews: [],
            compressedFiles: [],
            filters: {
                radius: 5000,
                status: 'all',
                category: 'all',
            },
            form: {
                categoryId: {{ $categories->first()?->id ?? 1 }},
                latitude: null,
                longitude: null,
                locationLabel: '',
                description: '',
            },

            initApp() {
                if (window.location.hash === '#sekitar') {
                    this.activeMobileTab = 'sekitar';
                }

                // Render the real base map immediately so real street tiles are visible right away
                this.$nextTick(() => {
                    if (window.L) {
                        this.setupMainMap();
                    } else {
                        window.addEventListener('leaflet:ready', () => this.setupMainMap(), { once: true });
                    }
                });

                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'geolocation' }).then((result) => {
                        if (result.state === 'granted') {
                            this.requestGpsLocation();
                        } else if (result.state === 'denied') {
                            this.gpsState = 'denied';
                        }
                    }).catch(() => {});
                }

                @if($errors->any())
                    this.reportModalOpen = true;
                @endif
            },

            requestGpsLocation() {
                if (!navigator.geolocation) {
                    this.gpsState = 'denied';
                    this.gpsErrorMessage = 'Perangkat atau browser kamu tidak mendukung fitur Geolocation GPS.';
                    return;
                }

                this.gpsState = 'loading';
                this.gpsErrorMessage = '';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.userLat = position.coords.latitude;
                        this.userLng = position.coords.longitude;
                        this.form.latitude = this.userLat;
                        this.form.longitude = this.userLng;
                        this.gpsState = 'granted';

                        this.$nextTick(() => {
                            this.activateUserLocationOnMap();
                            const initialSearchLat = mapRefs.pendingFocusLat ?? this.userLat;
                            const initialSearchLng = mapRefs.pendingFocusLng ?? this.userLng;
                            this.fetchNearbyReports(initialSearchLat, initialSearchLng);
                            this.resolveUserAreaName(this.userLat, this.userLng);
                            if (window.location.hash === '#buat-laporan') {
                                this.openCreateReportModal();
                            }
                        });
                    },
                    (error) => {
                        this.gpsState = 'denied';
                        if (!window.isSecureContext) {
                            this.gpsErrorMessage = 'Browser memblokir akses GPS pada koneksi HTTP biasa. Buka melalui https:// atau http://localhost:8000 agar GPS perangkat aktif.';
                        } else if (error.code === error.PERMISSION_DENIED) {
                            this.gpsErrorMessage = 'Izin lokasi ditolak. Aktifkan izin lokasi di browser agar peta sekitar dan formulir laporan dapat digunakan.';
                        } else {
                            this.gpsErrorMessage = 'Sinyal GPS belum berhasil ditemukan. Pastikan layanan lokasi perangkat kamu aktif lalu coba lagi.';
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 12000,
                        maximumAge: 30000,
                    }
                );
            },

            setupMainMap() {
                const L = window.L;
                if (!L || mapRefs.mainMap) {
                    return;
                }

                const initialLat = mapRefs.pendingFocusLat ?? this.userLat ?? -2.5489;
                const initialLng = mapRefs.pendingFocusLng ?? this.userLng ?? 118.0149;
                const initialZoom = (mapRefs.pendingFocusLat || this.userLat) ? 16 : 5;

                mapRefs.mapLayers = window.createRealMapLayerSet();

                mapRefs.mainMap = L.map('main-map', {
                    zoomControl: false,
                    layers: [mapRefs.mapLayers.jalan],
                }).setView([initialLat, initialLng], initialZoom);

                if (window.innerWidth >= 1024) {
                    L.control.zoom({ position: 'bottomright' }).addTo(mapRefs.mainMap);
                }

                // Reload active pins around the visible map center when user pans across town
                mapRefs.mainMap.on('moveend', () => {
                    if (this.gpsState !== 'granted' || !mapRefs.mainMap) {
                        return;
                    }
                    if (mapRefs.suppressNextMoveFetch) {
                        mapRefs.suppressNextMoveFetch = false;
                        return;
                    }
                    const center = mapRefs.mainMap.getCenter();
                    if (
                        mapRefs.lastSearchLat !== null &&
                        mapRefs.lastSearchLng !== null &&
                        distanceBetweenMeters(mapRefs.lastSearchLat, mapRefs.lastSearchLng, center.lat, center.lng) < 400
                    ) {
                        return;
                    }
                    clearTimeout(mapRefs.moveTimer);
                    mapRefs.moveTimer = setTimeout(() => {
                        this.fetchNearbyReports(center.lat, center.lng);
                    }, 320);
                });

                const refreshMapSize = () => {
                    if (mapRefs.mainMap) {
                        mapRefs.mainMap.invalidateSize();
                    }
                };

                setTimeout(refreshMapSize, 60);
                setTimeout(refreshMapSize, 250);
                window.addEventListener('resize', refreshMapSize);

                if (typeof ResizeObserver !== 'undefined' && this.$refs.mapStage) {
                    const observer = new ResizeObserver(refreshMapSize);
                    observer.observe(this.$refs.mapStage);
                }

                if (this.userLat && this.userLng) {
                    this.activateUserLocationOnMap();
                }

                if (this.reports.length > 0) {
                    this.renderMapMarkers();
                }
            },

            activateUserLocationOnMap() {
                if (!mapRefs.mainMap) {
                    this.setupMainMap();
                }
                const L = window.L;
                if (!L || !mapRefs.mainMap || !this.userLat || !this.userLng) {
                    return;
                }

                mapRefs.mainMap.invalidateSize();

                const targetLat = mapRefs.pendingFocusLat ?? this.userLat;
                const targetLng = mapRefs.pendingFocusLng ?? this.userLng;
                const targetZoom = mapRefs.pendingFocusLat ? 16 : 15;

                mapRefs.suppressNextMoveFetch = true;
                // Jump synchronously on first activation so zoom/bounds are settled before markers render
                mapRefs.mainMap.setView([targetLat, targetLng], targetZoom, {
                    animate: mapRefs.hasInitialGpsJump,
                });
                mapRefs.hasInitialGpsJump = true;

                const userIcon = L.divIcon({
                    className: '',
                    html: '<div class="lt-user-radar"><div class="lt-user-dot"></div></div>',
                    iconSize: [68, 68],
                    iconAnchor: [34, 34],
                });

                if (mapRefs.userMarker) {
                    mapRefs.userMarker.setLatLng([this.userLat, this.userLng]);
                } else {
                    mapRefs.userMarker = L.marker([this.userLat, this.userLng], {
                        icon: userIcon,
                        interactive: false,
                        zIndexOffset: 100,
                    }).addTo(mapRefs.mainMap);
                }

                if (mapRefs.radiusCircle) {
                    mapRefs.radiusCircle.setLatLng([this.userLat, this.userLng]);
                    mapRefs.radiusCircle.setRadius(this.filters.radius);
                } else {
                    mapRefs.radiusCircle = L.circle([this.userLat, this.userLng], {
                        radius: this.filters.radius,
                        color: '#16A6D8',
                        weight: 1.5,
                        dashArray: '5, 6',
                        fillColor: '#16A6D8',
                        fillOpacity: 0.05,
                    }).addTo(mapRefs.mainMap);
                }
            },

            setMapLayer(layerKey) {
                if (!mapRefs.mainMap || !mapRefs.mapLayers || !mapRefs.mapLayers[layerKey]) {
                    return;
                }

                Object.values(mapRefs.mapLayers).forEach((layer) => {
                    if (mapRefs.mainMap.hasLayer(layer)) {
                        mapRefs.mainMap.removeLayer(layer);
                    }
                });

                mapRefs.mainMap.addLayer(mapRefs.mapLayers[layerKey]);
                this.activeMapLayer = layerKey;
            },

            toggleSatelliteLayer() {
                this.setMapLayer(this.activeMapLayer === 'satelit' ? 'jalan' : 'satelit');
            },

            async resolveUserAreaName(lat, lng) {
                if (typeof window.reverseGeocodeLocation !== 'function') {
                    return;
                }
                const info = await window.reverseGeocodeLocation(lat, lng);
                if (info) {
                    if (info.shortArea) {
                        this.userAreaName = info.shortArea;
                    }
                    if (info.detailAddress) {
                        this.detectedStreetAddress = info.detailAddress;
                    }
                }
            },

            async resolveMiniPinAddress(lat, lng) {
                if (typeof window.reverseGeocodeLocation !== 'function') {
                    return;
                }
                const info = await window.reverseGeocodeLocation(lat, lng);
                if (info && info.detailAddress) {
                    this.detectedStreetAddress = info.detailAddress;
                }
            },

            async fetchNearbyReports(customLat = null, customLng = null) {
                if (!this.userLat || !this.userLng) {
                    return;
                }

                const searchLat = customLat ?? (mapRefs.mainMap ? mapRefs.mainMap.getCenter().lat : this.userLat);
                const searchLng = customLng ?? (mapRefs.mainMap ? mapRefs.mainMap.getCenter().lng : this.userLng);
                mapRefs.lastSearchLat = searchLat;
                mapRefs.lastSearchLng = searchLng;

                this.isLoadingNearby = true;
                try {
                    const params = new URLSearchParams({
                        lat: searchLat,
                        lng: searchLng,
                        user_lat: this.userLat,
                        user_lng: this.userLng,
                        radius: this.filters.radius,
                        status: this.filters.status,
                        category: this.filters.category,
                    });

                    if (mapRefs.pendingFocusTicket) {
                        params.set('focus_ticket', mapRefs.pendingFocusTicket);
                    }

                    const response = await fetch(`/api/reports/nearby?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.reports = data.reports || [];

                        if (mapRefs.pendingFocusTicket) {
                            const matched = this.reports.find((r) => r.ticket_id === mapRefs.pendingFocusTicket);
                            mapRefs.pendingFocusTicket = null;
                            mapRefs.pendingFocusLat = null;
                            mapRefs.pendingFocusLng = null;

                            if (matched) {
                                this.selectedReport = matched;
                                this.renderMapMarkers();
                                this.focusReportOnMap(matched);
                                if (window.history && window.history.replaceState) {
                                    window.history.replaceState({}, '', window.location.pathname);
                                }
                                return;
                            }
                        }

                        if (this.reports.length > 0) {
                            if (this.selectedReport) {
                                const updatedSelection = this.reports.find((r) => r.id === this.selectedReport.id);
                                this.selectedReport = updatedSelection || null;
                            }
                        } else {
                            this.selectedReport = null;
                        }

                        this.renderMapMarkers();
                    } else {
                        console.error('Gagal memuat laporan sekitar: HTTP', response.status);
                    }
                } catch (e) {
                    console.error('Gagal memuat laporan sekitar:', e);
                } finally {
                    this.isLoadingNearby = false;
                }
            },

            renderMapMarkers() {
                const L = window.L;
                if (!L) {
                    return;
                }
                if (!mapRefs.mainMap) {
                    this.setupMainMap();
                }
                if (!mapRefs.mainMap) {
                    return;
                }

                mapRefs.mainMap.invalidateSize({ pan: false });

                if (mapRefs.clusterGroup) {
                    mapRefs.mainMap.removeLayer(mapRefs.clusterGroup);
                    mapRefs.clusterGroup = null;
                }

                const group = typeof L.markerClusterGroup === 'function'
                    ? L.markerClusterGroup({
                        animate: false,
                        animateAddingMarkers: false,
                        showCoverageOnHover: false,
                        maxClusterRadius: 36,
                        spiderfyOnMaxZoom: true,
                        removeOutsideVisibleBounds: false,
                    })
                    : L.layerGroup();

                const markers = [];
                this.reports.forEach((report) => {
                    const lat = Number(report.latitude);
                    const lng = Number(report.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return;
                    }

                    const isSelected = this.selectedReport && this.selectedReport.id === report.id;
                    const iconSvg = this.getCategorySvgMarkup(report.category_icon);
                    const pinHtml = `
                        <div data-report-pin="${report.id}" class="lt-map-pin ${isSelected ? 'is-active' : ''}" style="background-color: ${report.status_color};">
                            ${iconSvg}
                        </div>
                    `;

                    const markerIcon = L.divIcon({
                        className: 'lt-div-icon',
                        html: pinHtml,
                        iconSize: [46, 46],
                        iconAnchor: [23, 23],
                    });

                    const marker = L.marker([lat, lng], {
                        icon: markerIcon,
                        zIndexOffset: isSelected ? 900 : 700,
                    });
                    marker.on('click', () => {
                        this.focusReportOnMap(report);
                    });

                    markers.push(marker);
                });

                if (markers.length > 0) {
                    if (typeof group.addLayers === 'function') {
                        group.addLayers(markers);
                    } else {
                        markers.forEach((m) => group.addLayer(m));
                    }
                }

                group.addTo(mapRefs.mainMap);
                mapRefs.clusterGroup = group;

                requestAnimationFrame(() => {
                    mapRefs.mainMap?.invalidateSize({ pan: false });
                });
            },

            getCategorySvgMarkup(iconName) {
                const svgs = {
                    'construction': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="8" rx="1"/><path d="M17 14v7"/><path d="M7 14v7"/><path d="M17 3v3"/><path d="M7 3v3"/></svg>',
                    'lightbulb': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
                    'trash-2': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
                    'cloud-rain': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/></svg>',
                    'traffic-cone': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9.3 6.2 4.55 2.27"/><path d="m7.2 11.7 7.6 3.8"/><path d="M5 19h14"/><path d="M3 22h18"/><path d="M10.8 3.1a1.35 1.35 0 0 1 2.4 0l5.8 15.9H5Z"/></svg>',
                    'wrench': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
                };
                return svgs[iconName] || '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>';
            },

            focusReportOnMap(report) {
                this.selectedReport = report;
                document.querySelectorAll('#main-map .lt-map-pin.is-active').forEach((el) => {
                    el.classList.remove('is-active');
                });
                const activePin = document.querySelector(`#main-map [data-report-pin="${report.id}"]`);
                if (activePin) {
                    activePin.classList.add('is-active');
                }

                if (!mapRefs.mainMap) {
                    return;
                }

                const targetZoom = Math.max(mapRefs.mainMap.getZoom(), 16);
                // Offset upward slightly so the marker sits right below the centered callout card
                const point = mapRefs.mainMap.project([Number(report.latitude), Number(report.longitude)], targetZoom);
                point.y -= window.innerWidth < 1024 ? 70 : 60;
                const offsetLatLng = mapRefs.mainMap.unproject(point, targetZoom);

                mapRefs.suppressNextMoveFetch = true;
                mapRefs.mainMap.setView(offsetLatLng, targetZoom, { animate: true });
            },

            recenterToUser() {
                if (mapRefs.mainMap && this.userLat && this.userLng) {
                    this.selectedReport = null;
                    mapRefs.suppressNextMoveFetch = true;
                    mapRefs.mainMap.setView([this.userLat, this.userLng], 16, { animate: true });
                    this.fetchNearbyReports(this.userLat, this.userLng);
                }
            },

            setRadius(meters) {
                this.filters.radius = meters;
                if (mapRefs.radiusCircle) {
                    mapRefs.radiusCircle.setRadius(meters);
                }
                if (mapRefs.mainMap) {
                    const zoomMap = { 1000: 15, 5000: 14, 15000: 12 };
                    mapRefs.suppressNextMoveFetch = true;
                    mapRefs.mainMap.setZoom(zoomMap[meters] || 14);
                }
                this.fetchNearbyReports();
            },

            setStatusFilter(status) {
                this.filters.status = status;
                this.fetchNearbyReports();
            },

            setCategoryFilter(slug) {
                this.filters.category = slug;
                this.fetchNearbyReports();
            },

            openCreateReportModal() {
                if (this.gpsState !== 'granted') {
                    this.requestGpsLocation();
                    return;
                }
                this.reportModalOpen = true;
                this.form.latitude = this.userLat;
                this.form.longitude = this.userLng;

                this.$nextTick(() => {
                    this.initMiniPickerMap();
                });
            },

            closeCreateReportModal() {
                this.reportModalOpen = false;
            },

            initMiniPickerMap() {
                const L = window.L;
                if (!L) {
                    return;
                }

                if (mapRefs.miniMap) {
                    mapRefs.miniMap.invalidateSize();
                    mapRefs.miniMap.setView([this.form.latitude, this.form.longitude], 17);
                    mapRefs.miniMarker.setLatLng([this.form.latitude, this.form.longitude]);
                    setTimeout(() => mapRefs.miniMap?.invalidateSize(), 220);
                    return;
                }

                const miniLayers = window.createRealMapLayerSet();

                mapRefs.miniMap = L.map('mini-picker-map', {
                    layers: [miniLayers.jalan],
                }).setView([this.form.latitude, this.form.longitude], 17);

                L.control.layers(
                    {
                        'Peta Jalan (OSM)': miniLayers.jalan,
                        'Citra Satelit': miniLayers.satelit,
                    },
                    {},
                    { position: 'topright', collapsed: true }
                ).addTo(mapRefs.miniMap);

                mapRefs.miniMarker = L.marker([this.form.latitude, this.form.longitude], {
                    draggable: true,
                }).addTo(mapRefs.miniMap);

                mapRefs.miniMarker.on('dragend', (evt) => {
                    const pos = evt.target.getLatLng();
                    this.form.latitude = pos.lat;
                    this.form.longitude = pos.lng;
                    this.resolveMiniPinAddress(pos.lat, pos.lng);
                });

                mapRefs.miniMap.on('click', (evt) => {
                    mapRefs.miniMarker.setLatLng(evt.latlng);
                    this.form.latitude = evt.latlng.lat;
                    this.form.longitude = evt.latlng.lng;
                    this.resolveMiniPinAddress(evt.latlng.lat, evt.latlng.lng);
                });

                setTimeout(() => mapRefs.miniMap?.invalidateSize(), 220);
            },

            async handlePhotoSelection(event) {
                this.photoError = '';
                const files = Array.from(event.target.files || []);
                if (files.length === 0) {
                    this.photoPreviews = [];
                    this.compressedFiles = [];
                    return;
                }

                if (files.length > 5) {
                    this.photoError = 'Maksimal 5 foto yang dapat diunggah untuk satu laporan.';
                    event.target.value = '';
                    this.photoPreviews = [];
                    return;
                }

                this.isCompressingPhotos = true;
                this.photoPreviews = [];
                this.compressedFiles = [];

                try {
                    const dataTransfer = new DataTransfer();
                    for (const rawFile of files) {
                        const compressed = await window.compressImageFile(rawFile, 1280, 0.82);
                        this.compressedFiles.push(compressed);
                        dataTransfer.items.add(compressed);
                        this.photoPreviews.push(URL.createObjectURL(compressed));
                    }
                    this.$refs.photoInput.files = dataTransfer.files;
                } finally {
                    this.isCompressingPhotos = false;
                }
            },

            submitReportForm(event) {
                if (!this.form.latitude || !this.form.longitude) {
                    alert('Titik lokasi GPS wajib aktif.');
                    return;
                }
                this.isSubmitting = true;
                event.target.submit();
            },
        };
    }
</script>
@endsection
