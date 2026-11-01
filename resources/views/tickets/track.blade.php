@extends('layouts.app', ['title' => ($report ? 'Lacak Tiket ' . $report->ticket_id : 'Lacak Tiket Laporan') . ' — Lapor Tuntas'])

@section('content')
<div class="max-w-[960px] w-full mx-auto px-4 py-6 sm:py-10 space-y-6">

    <!-- Search Card (Style Guide 9.5 & PRD FR-17..20) -->
    <section class="bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_4px_14px_rgba(23,50,77,0.08)] p-5 sm:p-7">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-[#DFF5FC] text-[#0D8BB8]">
                Transparansi Tanpa Login
            </span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#17324D]">
                Lacak Status &amp; Riwayat Laporan
            </h1>
            <p class="mt-1 text-sm text-[#617482] font-semibold">
                Masukkan ID tiket unik yang kamu terima saat mengirim laporan. Laporan yang sudah selesai tetap tersimpan sebagai riwayat publik.
            </p>
        </div>

        <form action="{{ route('tickets.index') }}" method="GET" class="mt-5 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1">
                <label for="ticket-search-input" class="block text-xs font-extrabold text-[#17324D] mb-1">
                    ID Tiket Laporan (Format: <code class="font-mono text-[#0D8BB8]">LT-XXXX-XXXX</code>)
                </label>
                <input id="ticket-search-input"
                       type="text"
                       name="ticket_id"
                       value="{{ $searchedId }}"
                       required
                       placeholder="Masukkan ID tiket dari bukti laporan kamu"
                       class="w-full min-h-[48px] px-4 py-3 rounded-xl border border-[#D7E1E5] font-mono font-extrabold text-base text-[#17324D] uppercase placeholder:font-sans placeholder:font-normal placeholder:normal-case placeholder:text-[#617482] focus:outline-none focus:ring-3 focus:ring-[#16A6D8]/35 focus:border-[#16A6D8]">
            </div>
            <div class="sm:pt-5">
                <button type="submit"
                        class="w-full sm:w-auto min-h-[48px] px-6 py-3 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span>Cari laporan</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Error / Not Found State (Style Guide 9.10 & 10) -->
    @if($errorMessage)
        <section class="bg-white rounded-[24px] border-2 border-[#FF6B5C] p-6 sm:p-8 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#FF6B5C]/15 text-[#D94343] mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" x2="12" y1="8" y2="12"/>
                    <line x1="12" x2="12.01" y1="16" y2="16"/>
                </svg>
            </div>
            <h2 class="text-lg sm:text-xl font-extrabold text-[#17324D]">{{ $errorMessage }}</h2>
            <p class="mt-2 text-sm text-[#617482] max-w-lg mx-auto">
                Pastikan seluruh huruf dan tanda hubung sesuai dengan bukti tiket yang kamu simpan (format: <code class="font-mono font-bold text-[#17324D]">LT-XXXX-XXXX</code>).
            </p>
        </section>
    @endif

    <!-- Report Details & Vertical Status Timeline (PRD FR-18 & FR-19) -->
    @if($report)
        @php
            $beforePhotos = $report->media->where('type', 'before');
            $afterPhotos = $report->media->where('type', 'after');
        @endphp

        <section class="bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_4px_14px_rgba(23,50,77,0.08)] overflow-hidden">
            <!-- Header Bar -->
            <div class="p-5 sm:p-7 border-b border-[#D7E1E5] flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[#F3F7F8]/60">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="px-3 py-1 rounded-lg bg-[#17324D] text-white font-mono font-extrabold text-sm tabular-nums">
                            {{ $report->ticket_id }}
                        </span>
                        <x-status-badge :status="$report->status" />
                        @if(!$report->is_visible_on_map)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#E9EEF1] text-[#617482]">
                                Tidak ditampilkan di peta publik
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#DFF5FC] text-[#0D8BB8]">
                                Tampil di peta sekitar
                            </span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-xl sm:text-2xl font-extrabold text-[#17324D]">
                        {{ $report->title }}
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-[#617482] font-semibold">
                        Dilaporkan {{ $report->created_at?->format('d M Y, H:i') }} WIB ({{ $report->created_at?->diffForHumans() }})
                    </p>
                </div>

                @auth
                    <a href="{{ route('staff.reports.show', $report) }}"
                       class="min-h-[44px] px-4 py-2.5 rounded-xl bg-[#17324D] hover:bg-[#0D8BB8] text-white text-xs font-extrabold inline-flex items-center gap-2 shrink-0">
                        <span>Kelola di Panel Petugas</span>
                        <span>→</span>
                    </a>
                @endauth
            </div>

            <div class="p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
                <!-- Left Column: Info, Photos (Before & After), and Map -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Metadata Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="p-4 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5]">
                            <p class="text-xs font-bold text-[#617482]">Kategori Masalah</p>
                            <div class="mt-1 flex items-center gap-2 font-extrabold text-[#17324D]">
                                <x-category-icon :icon="$report->category?->icon ?? 'map-pin'" class="w-5 h-5 text-[#0D8BB8]" />
                                <span>{{ $report->category?->name }}</span>
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl bg-[#F3F7F8] border border-[#D7E1E5]">
                            <p class="text-xs font-bold text-[#617482]">Instansi Penangan</p>
                            <p class="mt-1 font-extrabold text-[#17324D]">
                                {{ $report->agency?->name ?? 'Menunggu Penugasan Instansi' }}
                            </p>
                            @if($report->assignedOfficer)
                                <p class="text-xs text-[#0D8BB8] font-bold mt-0.5">
                                    Petugas: {{ $report->assignedOfficer->name }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h3 class="text-sm font-extrabold text-[#17324D] uppercase tracking-wider">Deskripsi Laporan Warga</h3>
                        <p class="mt-2 p-4 rounded-2xl bg-[#F3F7F8] text-sm sm:text-base text-[#17324D] leading-relaxed">
                            {{ $report->description }}
                        </p>
                    </div>

                    @if($report->status === 'ditolak' && $report->rejection_reason)
                        <div class="p-4 rounded-2xl bg-[#E9EEF1] border border-[#697782] text-sm text-[#17324D]">
                            <p class="font-extrabold text-[#D94343]">Alasan Penolakan Laporan:</p>
                            <p class="mt-1 font-semibold">{{ $report->rejection_reason }}</p>
                        </div>
                    @endif

                    <!-- Before & After Photo Evidence Comparison (Style Guide 9.7) -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-extrabold text-[#17324D] uppercase tracking-wider">
                            Dokumentasi Bukti Lapangan
                        </h3>

                        <div class="grid grid-cols-1 {{ $afterPhotos->isNotEmpty() ? 'sm:grid-cols-2' : '' }} gap-4">
                            <!-- Before Photos -->
                            <div class="space-y-2">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-[#DFF5FC] text-[#0D8BB8] text-xs font-extrabold">
                                    Foto Sebelum (Laporan Warga)
                                </span>
                                <div class="grid grid-cols-1 gap-2.5">
                                    @foreach($beforePhotos as $media)
                                        <img src="{{ $media->publicUrl() }}"
                                             alt="Foto bukti laporan warga"
                                             class="w-full h-52 object-cover rounded-2xl border border-[#D7E1E5] bg-[#F3F7F8]">
                                    @endforeach
                                </div>
                            </div>

                            <!-- After Photos (When completed by officer) -->
                            @if($afterPhotos->isNotEmpty())
                                <div class="space-y-2">
                                    <span class="inline-block px-2.5 py-1 rounded-lg bg-[#E1F5EA] text-[#185B37] text-xs font-extrabold">
                                        ✓ Foto Sesudah Perbaikan (Petugas)
                                    </span>
                                    <div class="grid grid-cols-1 gap-2.5">
                                        @foreach($afterPhotos as $media)
                                            <img src="{{ $media->publicUrl() }}"
                                                 alt="Foto bukti setelah perbaikan"
                                                 class="w-full h-52 object-cover rounded-2xl border-2 border-[#278653] bg-[#F3F7F8]">
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Location Map Preview -->
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="text-sm font-extrabold text-[#17324D] uppercase tracking-wider">Titik Lokasi Masalah</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono font-bold text-[#617482] tabular-nums">
                                    {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}
                                </span>
                                <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-xs font-extrabold text-[#0D8BB8] hover:underline">
                                    Google Maps ↗
                                </a>
                            </div>
                        </div>
                        <p class="text-xs font-bold text-[#0D8BB8] mb-2">📍 {{ $report->location_label }}</p>
                        <div id="detail-report-map" class="w-full h-60 rounded-2xl border border-[#D7E1E5] overflow-hidden bg-[#DCE8EE]"></div>
                    </div>
                </div>

                <!-- Right Column: Vertical Status History Timeline (Style Guide 9.6) -->
                <div class="lg:col-span-5">
                    <div class="p-5 rounded-[20px] bg-[#F3F7F8] border border-[#D7E1E5]">
                        <h3 class="text-base font-extrabold text-[#17324D] flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#16A6D8]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span>Linimasa Penanganan</span>
                        </h3>
                        <p class="text-xs text-[#617482] font-semibold mt-1">
                            Setiap perubahan status tercatat secara transparan beserta waktu dan tindak lanjutnya.
                        </p>

                        <ol class="mt-5 relative border-l-2 border-[#D7E1E5] ml-3 space-y-6">
                            @foreach($report->statusHistories as $history)
                                @php
                                    $stepMeta = \App\Models\Report::getStatusMetadata($history->to_status);
                                @endphp
                                <li class="ml-5">
                                    <span class="absolute -left-[11px] w-5 h-5 rounded-full border-2 border-white shadow-xs flex items-center justify-center"
                                          style="background-color: {{ $stepMeta['color'] }};"></span>

                                    <div class="bg-white p-3.5 rounded-2xl border border-[#D7E1E5] shadow-2xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold"
                                                  style="background-color: {{ $stepMeta['bg'] }}; color: {{ $stepMeta['text'] }};">
                                                {{ $stepMeta['label'] }}
                                            </span>
                                            <time class="text-[11px] font-bold text-[#617482] tabular-nums">
                                                {{ $history->created_at?->format('d M Y, H:i') }}
                                            </time>
                                        </div>

                                        <p class="mt-2 text-xs sm:text-sm font-bold text-[#17324D] leading-relaxed">
                                            {{ $history->note ?: $stepMeta['description'] }}
                                        </p>

                                        @if($history->actor)
                                            <p class="mt-1.5 text-[11px] font-semibold text-[#617482]">
                                                Oleh: {{ $history->actor->name }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        @php
            $pinMeta = \App\Models\Report::getStatusMetadata($report->status);
        @endphp
        <script>
            (function () {
                const mountDetailMap = function () {
                    if (typeof window.initStaticReportMap === 'function') {
                        window.initStaticReportMap(
                            'detail-report-map',
                            {{ (float) $report->latitude }},
                            {{ (float) $report->longitude }},
                            @js($report->title),
                            @js($pinMeta['color'])
                        );
                    }
                };
                if (window.L && typeof window.initStaticReportMap === 'function') {
                    mountDetailMap();
                } else {
                    window.addEventListener('leaflet:ready', mountDetailMap, { once: true });
                    window.addEventListener('load', mountDetailMap, { once: true });
                }
            })();
        </script>
    @endif
</div>
@endsection

