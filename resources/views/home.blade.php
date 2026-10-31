@extends('layouts.app', ['title' => 'Lapor Tuntas — Peta Masalah Publik di Sekitarmu'])

@section('content')
<div x-data="laporTuntasMapApp()"
     x-init="initApp()"
     @open-report-modal.window="openCreateReportModal()"
     class="relative flex-1 flex flex-col lg:flex-row h-[calc(100vh-4rem)] overflow-hidden bg-[#EAF1E8]">

    <!-- ===================================================================== -->
    <!-- MANDATORY GPS LOCATION PERMISSION GATE (PRD FR-02 & FR-15)             -->
    <!-- ===================================================================== -->
    <div x-show="gpsState !== 'granted'"
         x-transition.opacity
         class="absolute inset-0 z-30 bg-[#F3F7F8]/95 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <div class="max-w-lg w-full bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_8px_24px_rgba(23,50,77,0.14)] p-6 sm:p-8 text-center">
            <!-- Vector Illustration -->
            <div class="mx-auto w-24 h-24 rounded-full bg-[#DFF5FC] border-4 border-white shadow-md flex items-center justify-center mb-5 relative">
                <svg class="w-12 h-12 text-[#16A6D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <span class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-[#C5E84A] text-[#17324D] font-extrabold flex items-center justify-center text-xs border-2 border-white">
                    GPS
                </span>
            </div>

            <template x-if="gpsState === 'prompt' || gpsState === 'loading'">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8] mb-3">
                        Wajib Aktifkan Lokasi (FR-02)
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#17324D] leading-tight">
                        Aktifkan lokasi untuk melihat &amp; melaporkan masalah di sekitarmu
                    </h1>
                    <p class="mt-3 text-sm sm:text-base text-[#617482] leading-relaxed">
                        Lapor Tuntas menampilkan peta laporan yang sedang berjalan tepat di sekitar posisi kamu dan memastikan titik masalah yang dilaporkan akurat bagi petugas lapangan.
                    </p>

                    <div class="mt-5 p-3.5 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5] text-left text-xs sm:text-sm text-[#17324D] space-y-1.5">
                        <div class="flex items-start gap-2">
                            <span class="text-[#278653] font-extrabold">✓</span>
                            <span><strong>Privasi terjaga:</strong> Koordinat posisi kamu tidak disimpan saat hanya melihat peta.</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="text-[#278653] font-extrabold">✓</span>
                            <span><strong>Tanpa akun:</strong> Langsung lapor dan terima ID tiket unik.</span>
                        </div>
                    </div>

                    <button type="button"
                            @click="requestGpsLocation()"
                            :disabled="gpsState === 'loading'"
                            class="mt-6 w-full min-h-[48px] px-6 py-3.5 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] disabled:opacity-60 text-white font-extrabold text-base shadow-[0_6px_18px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <svg x-show="gpsState !== 'loading'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                        </svg>
                        <span x-text="gpsState === 'loading' ? 'Sedang mendeteksi titik GPS...' : 'Aktifkan Izin Lokasi Sekarang'"></span>
                    </button>
                </div>
            </template>

            <!-- State when GPS permission is denied (PRD FR-15) -->
            <template x-if="gpsState === 'denied'">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#FF6B5C]/15 text-[#D94343] mb-3">
                        Izin Lokasi Belum Aktif
                    </span>
                    <h1 class="text-2xl font-extrabold text-[#17324D]">
                        Peta dan formulir lapor membutuhkan akses lokasi
                    </h1>
                    <p class="mt-2.5 text-sm text-[#617482] leading-relaxed" x-text="gpsErrorMessage || 'Izin lokasi ditolak oleh browser perangkat kamu. Ikuti petunjuk singkat di bawah ini untuk mengaktifkannya kembali.'"></p>

                    <div class="mt-5 p-4 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5] text-left text-xs sm:text-sm text-[#17324D] space-y-2">
                        <p class="font-extrabold text-[#17324D]">Cara mengaktifkan izin lokasi di browser:</p>
                        <ol class="list-decimal list-inside space-y-1.5 text-[#617482] font-semibold">
                            <li>Ketuk ikon <strong>Gembok / Pengaturan Situs</strong> di sebelah kiri alamat website (URL bar).</li>
                            <li>Pilih menu <strong>Izin (Permissions)</strong> lalu ubah <strong>Lokasi (Location)</strong> menjadi <strong>Izinkan (Allow)</strong>.</li>
                            <li>Tekan tombol <strong>Coba Aktifkan Lagi</strong> di bawah ini.</li>
                        </ol>
                    </div>

                    <button type="button"
                            @click="requestGpsLocation()"
                            class="mt-6 w-full min-h-[48px] px-6 py-3.5 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-base shadow-[0_6px_18px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/>
                            <path d="M16 16h5v5"/>
                        </svg>
                        <span>Coba Aktifkan Lagi</span>
                    </button>
                </div>
            </template>

            <!-- Ticket Tracking Shortcut (Still accessible even without GPS) -->
            <div class="mt-6 pt-5 border-t border-[#D7E1E5] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs sm:text-sm">
                <span class="text-[#617482] font-semibold">Sudah punya ID tiket laporan?</span>
                <a href="{{ route('tickets.index') }}" class="font-extrabold text-[#0D8BB8] hover:underline inline-flex items-center gap-1">
                    <span>Lacak tiket dengan ID</span>
                    <span>→</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- LEFT / MAIN AREA: INTERACTIVE MAP (65% on Desktop, Full on Mobile)     -->
    <!-- ===================================================================== -->
    <div class="relative flex-1 h-full w-full lg:w-[64%]">
        <div id="main-map" class="w-full h-full z-10" aria-label="Peta laporan masalah publik di sekitar Anda"></div>

        <!-- Floating Map Legend & Recenter Control -->
        <div class="absolute top-3 left-3 right-3 z-20 pointer-events-none flex items-start justify-between gap-2">
            <!-- Status Legend Pill -->
            <div class="pointer-events-auto bg-white/95 backdrop-blur border border-[#D7E1E5] rounded-2xl px-3.5 py-2 shadow-[0_4px_14px_rgba(23,50,77,0.1)] flex flex-wrap items-center gap-3 text-xs font-bold text-[#17324D]">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#16A6D8] inline-block"></span>
                    <span>Baru</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#7966D9] inline-block"></span>
                    <span>Ditugaskan</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-[#F5B82E] inline-block"></span>
                    <span>Diproses</span>
                </span>
                <span class="hidden sm:inline text-[#617482] font-semibold border-l border-[#D7E1E5] pl-2.5">
                    Penanda hilang saat laporan Selesai
                </span>
            </div>

            <!-- Recenter GPS Button -->
            <button type="button"
                    @click="recenterToUser()"
                    title="Pusatkan ke lokasi GPS saya"
                    class="pointer-events-auto min-w-[44px] min-h-[44px] px-3.5 py-2.5 rounded-2xl bg-white hover:bg-[#F3F7F8] border border-[#D7E1E5] shadow-[0_4px_14px_rgba(23,50,77,0.12)] text-[#17324D] font-extrabold text-xs flex items-center gap-1.5 cursor-pointer transition">
                <svg class="w-5 h-5 text-[#16A6D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 2v3"/>
                    <path d="M12 19v3"/>
                    <path d="M2 12h3"/>
                    <path d="M19 12h3"/>
                </svg>
                <span class="hidden sm:inline">Lokasi Saya</span>
            </button>
        </div>

        <!-- Selected Marker Summary Card Overlay (Style Guide 9.2) -->
        <div x-show="selectedReport"
             x-transition
             class="absolute bottom-24 lg:bottom-6 left-3 right-3 sm:left-auto sm:right-6 sm:w-[390px] z-20 bg-white rounded-[20px] border-2 border-[#16A6D8] shadow-[0_8px_24px_rgba(23,50,77,0.2)] p-4">
            <template x-if="selectedReport">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold"
                                  :style="`background:${selectedReport.status_bg}; color:${selectedReport.status_text}`"
                                  x-text="selectedReport.status_label"></span>
                            <span class="text-xs font-mono font-bold text-[#617482]" x-text="selectedReport.ticket_id"></span>
                        </div>
                        <button type="button"
                                @click="selectedReport = null"
                                class="w-8 h-8 rounded-lg hover:bg-[#F3F7F8] text-[#617482] font-bold flex items-center justify-center cursor-pointer"
                                aria-label="Tutup ringkasan">
                            ✕
                        </button>
                    </div>

                    <div class="mt-3 flex gap-3">
                        <template x-if="selectedReport.photo_url">
                            <img :src="selectedReport.photo_url"
                                 :alt="selectedReport.title"
                                 class="w-20 h-20 rounded-xl object-cover border border-[#D7E1E5] shrink-0 bg-[#F3F7F8]">
                        </template>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-extrabold text-[#0D8BB8]" x-text="selectedReport.category_name"></p>
                            <h3 class="text-sm font-extrabold text-[#17324D] line-clamp-2 mt-0.5" x-text="selectedReport.title"></h3>
                            <p class="text-xs text-[#617482] font-semibold mt-1">
                                <span x-text="selectedReport.distance_label"></span> • <span x-text="selectedReport.created_human"></span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-3.5 pt-3 border-t border-[#D7E1E5] flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-[#617482] truncate" x-text="selectedReport.agency_name"></span>
                        <a :href="selectedReport.track_url"
                           class="min-h-[40px] px-4 py-2 rounded-xl bg-[#17324D] hover:bg-[#0D8BB8] text-white text-xs font-extrabold inline-flex items-center gap-1.5 shrink-0 transition">
                            <span>Lihat detail</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </template>
        </div>

        <!-- Mobile Floating CTA "+ Buat laporan" (Style Guide 8) -->
        <button type="button"
                @click="openCreateReportModal()"
                class="lg:hidden fixed bottom-20 right-4 z-20 min-h-[52px] px-5 py-3 rounded-full bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-sm shadow-[0_8px_24px_rgba(22,166,216,0.45)] border-2 border-white flex items-center gap-2 cursor-pointer">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/>
                <path d="M12 5v14"/>
            </svg>
            <span>Buat laporan</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- RIGHT / BOTTOM SHEET: NEARBY REPORTS & FILTERS (Style Guide 8 & 9.3)   -->
    <!-- ===================================================================== -->
    <aside :class="mobileSheetExpanded ? 'h-[78vh]' : 'h-[42vh]'"
           class="w-full lg:w-[36%] lg:h-full bg-white border-t lg:border-t-0 lg:border-l border-[#D7E1E5] shadow-[0_-8px_24px_rgba(23,50,77,0.1)] lg:shadow-none z-20 flex flex-col transition-all duration-200">

        <!-- Mobile Drag Handle -->
        <button type="button"
                @click="mobileSheetExpanded = !mobileSheetExpanded"
                class="lg:hidden w-full py-2.5 flex flex-col items-center justify-center border-b border-[#D7E1E5]/60 cursor-pointer"
                aria-label="Perluas atau kecilkan panel daftar laporan">
            <span class="w-12 h-1.5 rounded-full bg-[#D7E1E5]"></span>
            <span class="text-[11px] font-bold text-[#617482] mt-1"
                  x-text="mobileSheetExpanded ? 'Ketuk untuk mengecilkan panel' : 'Ketuk untuk melihat lebih banyak laporan'"></span>
        </button>

        <!-- Quick Ticket ID Search Bar on Homepage (PRD FR-17) -->
        <div class="p-3.5 sm:p-4 border-b border-[#D7E1E5] bg-[#F3F7F8]/60">
            <form action="{{ route('tickets.index') }}" method="GET" class="flex items-center gap-2">
                <label for="quick-ticket-id" class="sr-only">Cari laporan berdasarkan ID tiket</label>
                <div class="relative flex-1">
                    <input id="quick-ticket-id"
                           type="text"
                           name="ticket_id"
                           placeholder="Lacak ID tiket (mis. LT-7K9P-2M4X)"
                           class="w-full min-h-[44px] pl-3.5 pr-3 py-2 rounded-xl bg-white border border-[#D7E1E5] text-sm font-mono font-bold text-[#17324D] placeholder:font-sans placeholder:font-normal placeholder:text-[#617482] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8] uppercase">
                </div>
                <button type="submit"
                        class="min-h-[44px] px-4 py-2 rounded-xl bg-[#17324D] hover:bg-[#0D8BB8] text-white text-xs sm:text-sm font-extrabold shrink-0 transition cursor-pointer">
                    Cari ID
                </button>
            </form>
        </div>

        <!-- Filters Header: Radius, Status, & Category Chips (FR-14 & Style Guide 9.8) -->
        <div class="p-3.5 sm:p-4 border-b border-[#D7E1E5] space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-[#17324D] flex items-center gap-2">
                        <span>Laporan di sekitarmu</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8] tabular-nums"
                              x-text="reports.length"></span>
                    </h2>
                    <p class="text-xs text-[#617482] font-semibold">
                        Menampilkan masalah publik yang sedang ditangani dalam radius terpilih
                    </p>
                </div>

                <!-- Radius Selector Pills -->
                <div class="inline-flex rounded-xl bg-[#F3F7F8] p-1 border border-[#D7E1E5]" role="group" aria-label="Filter radius jarak">
                    <template x-for="r in [500, 1000, 5000]" :key="r">
                        <button type="button"
                                @click="setRadius(r)"
                                :class="filters.radius === r ? 'bg-[#16A6D8] text-white shadow-xs' : 'text-[#617482] hover:text-[#17324D]'"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-extrabold transition cursor-pointer tabular-nums"
                                x-text="r < 1000 ? r + ' m' : (r / 1000) + ' km'"></button>
                    </template>
                </div>
            </div>

            <!-- Status Filter Chips -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                <button type="button"
                        @click="setStatusFilter('all')"
                        :class="filters.status === 'all' ? 'bg-[#17324D] text-white border-[#17324D]' : 'bg-white text-[#17324D] border-[#D7E1E5] hover:bg-[#F3F7F8]'"
                        class="min-h-[36px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 transition cursor-pointer">
                    Semua Status Aktif
                </button>
                <button type="button"
                        @click="setStatusFilter('baru')"
                        :class="filters.status === 'baru' ? 'bg-[#16A6D8] text-white border-[#16A6D8]' : 'bg-white text-[#17324D] border-[#D7E1E5] hover:bg-[#F3F7F8]'"
                        class="min-h-[36px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 flex items-center gap-1.5 transition cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span>Baru</span>
                </button>
                <button type="button"
                        @click="setStatusFilter('ditugaskan')"
                        :class="filters.status === 'ditugaskan' ? 'bg-[#7966D9] text-white border-[#7966D9]' : 'bg-white text-[#17324D] border-[#D7E1E5] hover:bg-[#F3F7F8]'"
                        class="min-h-[36px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 flex items-center gap-1.5 transition cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span>Ditugaskan</span>
                </button>
                <button type="button"
                        @click="setStatusFilter('diproses')"
                        :class="filters.status === 'diproses' ? 'bg-[#F5B82E] text-[#17324D] border-[#F5B82E]' : 'bg-white text-[#17324D] border-[#D7E1E5] hover:bg-[#F3F7F8]'"
                        class="min-h-[36px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 flex items-center gap-1.5 transition cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span>Sedang diproses</span>
                </button>
            </div>

            <!-- Category Horizontal Chips -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                <button type="button"
                        @click="setCategoryFilter('all')"
                        :class="filters.category === 'all' ? 'bg-[#DFF5FC] text-[#0D8BB8] border-[#16A6D8]' : 'bg-[#F3F7F8] text-[#617482] border-transparent hover:text-[#17324D]'"
                        class="min-h-[34px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 transition cursor-pointer">
                    Semua Kategori
                </button>
                @foreach($categories as $category)
                    <button type="button"
                            @click="setCategoryFilter('{{ $category->slug }}')"
                            :class="filters.category === '{{ $category->slug }}' ? 'bg-[#DFF5FC] text-[#0D8BB8] border-[#16A6D8]' : 'bg-[#F3F7F8] text-[#617482] border-transparent hover:text-[#17324D]'"
                            class="min-h-[34px] px-3 py-1 rounded-full text-xs font-extrabold border shrink-0 transition cursor-pointer">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Scrollable Report List Area -->
        <div class="flex-1 overflow-y-auto p-3.5 sm:p-4 space-y-3">
            <!-- Skeleton Loading State (Style Guide 9.10) -->
            <template x-if="isLoadingNearby">
                <div class="space-y-3">
                    <template x-for="i in [1, 2, 3]" :key="i">
                        <div class="p-4 rounded-[18px] border border-[#D7E1E5] bg-white animate-pulse space-y-2.5">
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

            <!-- Empty State + Demo Relocation Helper (Style Guide 9.10) -->
            <template x-if="!isLoadingNearby && reports.length === 0">
                <div class="p-6 rounded-[18px] border border-dashed border-[#D7E1E5] bg-[#F3F7F8]/60 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#DFF5FC] text-[#16A6D8] mx-auto flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4.5 8-11.8A8 8 0 0 0 4 10.2C4 17.5 12 22 12 22z"/>
                            <path d="m9 10 2 2 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-[#17324D]">Belum ada laporan aktif pada radius ini</h3>
                    <p class="text-xs text-[#617482] font-semibold mt-1 leading-relaxed">
                        Kamu bisa memperluas radius ke <strong>5 km</strong>, menjadi warga pertama yang melapor, atau menarik data simulasi demo ke sekitar titik GPS kamu.
                    </p>

                    <div class="mt-4 flex flex-col gap-2">
                        <button type="button"
                                @click="openCreateReportModal()"
                                class="w-full min-h-[44px] px-4 py-2.5 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-xs sm:text-sm font-extrabold transition cursor-pointer">
                            ＋ Buat laporan pertama di sini
                        </button>

                        <button type="button"
                                @click="relocateDemoDataToUser()"
                                :disabled="isRelocatingDemo"
                                class="w-full min-h-[44px] px-4 py-2.5 rounded-xl bg-white hover:bg-[#DFF5FC]/50 border border-[#16A6D8] text-[#0D8BB8] text-xs font-extrabold transition cursor-pointer">
                            <span x-text="isRelocatingDemo ? 'Memindahkan data simulasi...' : '⚡ Tarik 6 Data Simulasi Demo ke Sekitar GPS Saya'"></span>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Report Cards (Style Guide 9.3: 1. Category Icon, 2. Title, 3. Location & Time, 4. Status Badge, 5. Supporting Info) -->
            <template x-for="report in reports" :key="report.id">
                <article @click="focusReportOnMap(report)"
                         :class="selectedReport && selectedReport.id === report.id ? 'border-[#16A6D8] bg-[#DFF5FC]/25 ring-2 ring-[#16A6D8]/30' : 'border-[#D7E1E5] bg-white hover:border-[#16A6D8]/60'"
                         class="p-4 rounded-[18px] border shadow-[0_4px_14px_rgba(23,50,77,0.06)] transition cursor-pointer">
                    <div class="flex items-start gap-3">
                        <!-- 1. Category Icon Badge -->
                        <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 text-white shadow-xs"
                             :style="`background-color: ${report.status_color}`">
                            <span class="text-xs font-extrabold uppercase" x-text="report.category_name.slice(0, 2)"></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-extrabold text-[#0D8BB8] truncate" x-text="report.category_name"></span>
                                <!-- 4. Status Badge -->
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold shrink-0"
                                      :style="`background-color: ${report.status_bg}; color: ${report.status_text}`"
                                      x-text="report.status_label"></span>
                            </div>

                            <!-- 2. Issue Title (Max 2 lines) -->
                            <h3 class="text-sm sm:text-[15px] font-extrabold text-[#17324D] line-clamp-2 mt-1" x-text="report.title"></h3>

                            <!-- 3. Short Location & Timestamp -->
                            <p class="text-xs text-[#617482] font-semibold mt-1.5 flex items-center flex-wrap gap-x-2 gap-y-0.5">
                                <span class="font-extrabold text-[#17324D] tabular-nums" x-text="report.distance_label"></span>
                                <span>•</span>
                                <span class="truncate max-w-[170px]" x-text="report.location_label"></span>
                                <span>•</span>
                                <span x-text="report.created_human"></span>
                            </p>

                            <!-- 5. Supporting Info & Detail Link -->
                            <div class="mt-3 pt-2.5 border-t border-[#D7E1E5]/70 flex items-center justify-between gap-2 text-xs">
                                <span class="font-mono font-bold text-[#617482]" x-text="report.ticket_id"></span>
                                <a :href="report.track_url"
                                   @click.stop
                                   class="font-extrabold text-[#0D8BB8] hover:underline inline-flex items-center gap-1">
                                    <span>Detail &amp; Linimasa</span>
                                    <span>→</span>
                                </a>
                            </div>
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
             class="w-full max-w-2xl bg-white rounded-t-[24px] sm:rounded-[24px] border border-[#D7E1E5] shadow-[0_8px_24px_rgba(23,50,77,0.25)] max-h-[92vh] flex flex-col overflow-hidden">

            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-[#D7E1E5] flex items-center justify-between bg-[#F3F7F8]">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#0D8BB8]">Tanpa Login • Dibawah 1 Menit</span>
                    <h2 id="modal-report-title" class="text-lg sm:text-xl font-extrabold text-[#17324D]">Buat Laporan Masalah Publik</h2>
                </div>
                <button type="button"
                        @click="closeCreateReportModal()"
                        class="min-w-[44px] min-h-[44px] rounded-xl hover:bg-white text-[#617482] hover:text-[#17324D] font-extrabold flex items-center justify-center cursor-pointer"
                        aria-label="Tutup formulir laporan">
                    ✕
                </button>
            </div>

            <!-- Form Content (Ordered per Style Guide 9.4: 1. Foto, 2. Kategori, 3. Lokasi GPS, 4. Deskripsi, 5. Pratinjau sekitar, 6. Kirim) -->
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

                <!-- 1. Foto Bukti (1 - 5 Foto, dikompresi otomatis di sisi klien) -->
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
                           class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-dashed border-[#16A6D8] bg-[#DFF5FC]/35 text-sm font-bold text-[#17324D] file:mr-3 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:bg-[#16A6D8] file:text-white file:font-extrabold hover:bg-[#DFF5FC]/60 cursor-pointer">

                    <p x-show="isCompressingPhotos" class="mt-2 text-xs font-bold text-[#0D8BB8]">
                        ⏳ Mengoptimalkan ukuran foto agar cepat diunggah...
                    </p>
                    <p x-show="photoError" x-text="photoError" class="mt-2 text-xs font-bold text-[#D94343]"></p>

                    <!-- Photo Previews -->
                    <div x-show="photoPreviews.length > 0" class="mt-3 grid grid-cols-3 sm:grid-cols-5 gap-2.5">
                        <template x-for="(src, idx) in photoPreviews" :key="idx">
                            <div class="relative aspect-square rounded-xl overflow-hidden border border-[#D7E1E5] bg-[#F3F7F8]">
                                <img :src="src" class="w-full h-full object-cover" alt="Pratinjau foto bukti">
                                <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded bg-[#17324D]/80 text-white text-[10px] font-bold"
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
                                    class="min-h-[54px] p-3 rounded-xl text-left flex items-center gap-2.5 transition cursor-pointer">
                                <div class="w-9 h-9 rounded-xl bg-[#16A6D8]/15 text-[#0D8BB8] flex items-center justify-center shrink-0">
                                    <x-category-icon :icon="$category->icon" class="w-5 h-5" />
                                </div>
                                <span class="text-xs font-extrabold leading-snug">{{ $category->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Lokasi GPS & Peta Mini (Pin dapat digeser tipis untuk akurasi) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-extrabold text-[#17324D]">
                            3. Titik lokasi masalah (Otomatis dari GPS) <span class="text-[#D94343]">*</span>
                        </label>
                        <span class="text-xs font-mono font-bold text-[#0D8BB8] tabular-nums"
                              x-text="form.latitude && form.longitude ? form.latitude.toFixed(5) + ', ' + form.longitude.toFixed(5) : ''"></span>
                    </div>

                    <input type="hidden" name="latitude" :value="form.latitude">
                    <input type="hidden" name="longitude" :value="form.longitude">

                    <div id="mini-picker-map" class="w-full h-44 rounded-2xl border border-[#D7E1E5] overflow-hidden bg-[#EAF1E8]"></div>
                    <p class="mt-1.5 text-xs text-[#617482] font-semibold">
                        💡 Geser penanda sedikit di peta mini jika titik masalah berada di seberang jalan dari posisi berdiri kamu.
                    </p>

                    <div class="mt-2.5">
                        <label for="location-label" class="block text-xs font-extrabold text-[#17324D] mb-1">
                            Patokan lokasi singkat (opsional)
                        </label>
                        <input id="location-label"
                               type="text"
                               name="location_label"
                               x-model="form.locationLabel"
                               placeholder="Contoh: Depan Halte Transjakarta Monas arah Harmoni"
                               class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-[#D7E1E5] text-sm text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]">
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
                              placeholder="Jelaskan kondisi masalah secara singkat (minimal 10 karakter), misalnya ukuran lubang jalan atau sejak kapan lampu jalan padam..."
                              class="w-full p-3.5 rounded-xl border border-[#D7E1E5] text-sm text-[#17324D] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]"></textarea>
                </div>

                <!-- 5. Pratinjau Laporan Serupa di Sekitar (Style Guide 9.4) -->
                <div x-show="reports.length > 0" class="p-3.5 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5]">
                    <p class="text-xs font-extrabold text-[#17324D]">
                        🔎 Ada <span x-text="reports.length"></span> laporan aktif di sekitar titik kamu:
                    </p>
                    <p class="text-xs text-[#617482] font-semibold mt-0.5">
                        Pastikan masalah yang kamu laporkan belum ada di daftar bawah agar tidak duplikat.
                    </p>
                    <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                        <template x-for="nearbyItem in reports.slice(0, 3)" :key="nearbyItem.id">
                            <a :href="nearbyItem.track_url"
                               target="_blank"
                               class="p-2.5 rounded-xl bg-white border border-[#D7E1E5] min-w-[210px] max-w-[230px] shrink-0 hover:border-[#16A6D8]">
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
                            class="w-full sm:w-auto min-h-[48px] px-5 py-2.5 rounded-xl bg-white border border-[#D7E1E5] text-[#17324D] font-extrabold text-sm hover:bg-[#F3F7F8] cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="isSubmitting || isCompressingPhotos"
                            class="w-full sm:w-auto min-h-[48px] px-6 py-3 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] disabled:opacity-60 text-white font-extrabold text-[15px] shadow-[0_6px_18px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                        <span x-text="isSubmitting ? 'Sedang mengirim laporan...' : 'Kirim laporan'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function laporTuntasMapApp() {
        return {
            gpsState: 'prompt', // 'prompt' | 'loading' | 'granted' | 'denied'
            gpsErrorMessage: '',
            userLat: null,
            userLng: null,
            mainMap: null,
            userMarker: null,
            radiusCircle: null,
            clusterGroup: null,
            miniMap: null,
            miniMarker: null,
            reports: [],
            selectedReport: null,
            isLoadingNearby: false,
            isRelocatingDemo: false,
            mobileSheetExpanded: false,
            reportModalOpen: false,
            isCompressingPhotos: false,
            isSubmitting: false,
            photoError: '',
            photoPreviews: [],
            compressedFiles: [],
            filters: {
                radius: 1000,
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
                // Check if browser already granted geolocation permission
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
                            this.setupMainMap();
                            this.fetchNearbyReports();
                            if (window.location.hash === '#buat-laporan') {
                                this.openCreateReportModal();
                            }
                        });
                    },
                    (error) => {
                        this.gpsState = 'denied';
                        if (error.code === error.PERMISSION_DENIED) {
                            this.gpsErrorMessage = 'Izin lokasi ditolak. Sesuai aturan Lapor Tuntas, mohon aktifkan izin lokasi di browser agar peta sekitar dan formulir laporan dapat digunakan.';
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
                if (this.mainMap) {
                    this.mainMap.setView([this.userLat, this.userLng], 15);
                    return;
                }

                const L = window.L;
                this.mainMap = L.map('main-map', {
                    zoomControl: false,
                }).setView([this.userLat, this.userLng], 15);

                L.control.zoom({ position: 'bottomleft' }).addTo(this.mainMap);

                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                }).addTo(this.mainMap);

                const userIcon = L.divIcon({
                    className: '',
                    html: '<div class="lt-user-dot" title="Posisi GPS Kamu"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                });

                this.userMarker = L.marker([this.userLat, this.userLng], { icon: userIcon })
                    .addTo(this.mainMap)
                    .bindPopup('<strong>Posisi Kamu Saat Ini</strong>');

                this.radiusCircle = L.circle([this.userLat, this.userLng], {
                    radius: this.filters.radius,
                    color: '#16A6D8',
                    weight: 1.5,
                    fillColor: '#16A6D8',
                    fillOpacity: 0.06,
                }).addTo(this.mainMap);

                this.clusterGroup = L.markerClusterGroup({
                    showCoverageOnHover: false,
                    maxClusterRadius: 42,
                });
                this.mainMap.addLayer(this.clusterGroup);
            },

            async fetchNearbyReports() {
                if (!this.userLat || !this.userLng) {
                    return;
                }

                this.isLoadingNearby = true;
                try {
                    const params = new URLSearchParams({
                        lat: this.userLat,
                        lng: this.userLng,
                        radius: this.filters.radius,
                        status: this.filters.status,
                        category: this.filters.category,
                    });

                    const response = await fetch(`/api/reports/nearby?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.reports = data.reports || [];
                        this.renderMapMarkers();
                    }
                } catch (e) {
                    console.error('Gagal memuat laporan sekitar:', e);
                } finally {
                    this.isLoadingNearby = false;
                }
            },

            renderMapMarkers() {
                if (!this.clusterGroup || !window.L) {
                    return;
                }

                const L = window.L;
                this.clusterGroup.clearLayers();

                this.reports.forEach((report) => {
                    const iconSvg = this.getCategorySvgMarkup(report.category_icon);
                    const pinHtml = `
                        <div class="lt-map-pin" style="background-color: ${report.status_color};">
                            ${iconSvg}
                        </div>
                    `;

                    const markerIcon = L.divIcon({
                        className: '',
                        html: pinHtml,
                        iconSize: [44, 44],
                        iconAnchor: [22, 44],
                    });

                    const marker = L.marker([report.latitude, report.longitude], { icon: markerIcon });
                    marker.on('click', () => {
                        this.selectedReport = report;
                        this.mainMap.panTo([report.latitude, report.longitude]);
                    });

                    this.clusterGroup.addLayer(marker);
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
                if (this.mainMap) {
                    this.mainMap.setView([report.latitude, report.longitude], 17, { animate: true });
                }
            },

            recenterToUser() {
                if (this.mainMap && this.userLat && this.userLng) {
                    this.mainMap.setView([this.userLat, this.userLng], 15, { animate: true });
                }
            },

            setRadius(meters) {
                this.filters.radius = meters;
                if (this.radiusCircle) {
                    this.radiusCircle.setRadius(meters);
                }
                if (this.mainMap) {
                    const zoomMap = { 500: 16, 1000: 15, 5000: 13 };
                    this.mainMap.setZoom(zoomMap[meters] || 15);
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

            async relocateDemoDataToUser() {
                if (!this.userLat || !this.userLng) {
                    return;
                }
                this.isRelocatingDemo = true;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch('/api/demo/relocate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            lat: this.userLat,
                            lng: this.userLng,
                        }),
                    });

                    if (response.ok) {
                        await this.fetchNearbyReports();
                    }
                } finally {
                    this.isRelocatingDemo = false;
                }
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

                if (this.miniMap) {
                    this.miniMap.invalidateSize();
                    this.miniMap.setView([this.form.latitude, this.form.longitude], 17);
                    this.miniMarker.setLatLng([this.form.latitude, this.form.longitude]);
                    return;
                }

                this.miniMap = L.map('mini-picker-map').setView([this.form.latitude, this.form.longitude], 17);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    maxZoom: 19,
                }).addTo(this.miniMap);

                this.miniMarker = L.marker([this.form.latitude, this.form.longitude], {
                    draggable: true,
                }).addTo(this.miniMap);

                this.miniMarker.on('dragend', (evt) => {
                    const pos = evt.target.getLatLng();
                    this.form.latitude = pos.lat;
                    this.form.longitude = pos.lng;
                });

                this.miniMap.on('click', (evt) => {
                    this.miniMarker.setLatLng(evt.latlng);
                    this.form.latitude = evt.latlng.lat;
                    this.form.longitude = evt.latlng.lng;
                });
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

