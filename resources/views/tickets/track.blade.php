@extends('layouts.app', ['title' => ($report ? 'Lacak Tiket ' . $report->ticket_id : 'Lacak Tiket Laporan') . ' — Lapor Tuntas'])

@section('content')
<div class="max-w-[960px] w-full mx-auto px-4 py-6 sm:py-10 space-y-6">

    <!-- Search Card (Style Guide 9.5 & PRD FR-17..20) -->
    <section class="bg-surface rounded-2xl border border-border shadow-[0_4px_14px_rgba(23,50,77,0.08)] p-5 sm:p-7">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-primary-soft text-primary-hover">
                Transparansi Tanpa Login
            </span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-navy">
                Lacak Status &amp; Riwayat Laporan
            </h1>
            <p class="mt-1 text-sm text-text-muted font-semibold">
                Masukkan ID tiket unik yang kamu terima saat mengirim laporan. Laporan yang sudah selesai tetap tersimpan sebagai riwayat publik.
            </p>
        </div>

        <form action="{{ route('tickets.index') }}" method="GET" class="mt-5 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1">
                <label for="ticket-search-input" class="block text-xs font-extrabold text-navy mb-1">
                    ID Tiket Laporan (Format: <code class="font-mono text-primary-hover">LT-XXXX-XXXX</code>)
                </label>
                <input id="ticket-search-input"
                       type="text"
                       name="ticket_id"
                       value="{{ $searchedId }}"
                       required
                       placeholder="Masukkan ID tiket dari bukti laporan kamu"
                       class="w-full min-h-[48px] px-4 py-3 rounded-xl border border-border font-mono font-extrabold text-base text-navy uppercase placeholder:font-sans placeholder:font-normal placeholder:normal-case placeholder:text-text-muted focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
            </div>
            <div class="sm:pt-5">
                <button type="submit"
                        class="w-full sm:w-auto min-h-[48px] px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-extrabold text-[15px] shadow-[0_4px_14px_rgba(22,166,216,0.35)] flex items-center justify-center gap-2 transition cursor-pointer active:scale-[0.99]">
                    <x-icon name="search" class="w-5 h-5" stroke-width="2.3" />
                    <span>Cari laporan</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Error / Not Found State (Style Guide 9.10 & 10) -->
    @if($errorMessage)
        <section class="bg-surface rounded-2xl border-2 border-coral p-6 sm:p-8 text-center">
            <div class="w-14 h-14 rounded-2xl bg-coral/15 text-danger mx-auto flex items-center justify-center mb-3">
                <x-icon name="alert-triangle" class="w-7 h-7" stroke-width="2.2" />
            </div>
            <h2 class="text-lg sm:text-xl font-extrabold text-navy">{{ $errorMessage }}</h2>
            <p class="mt-2 text-sm text-text-muted max-w-lg mx-auto">
                Pastikan seluruh huruf dan tanda hubung sesuai dengan bukti tiket yang kamu simpan (format: <code class="font-mono font-bold text-navy">LT-XXXX-XXXX</code>).
            </p>
        </section>
    @endif

    <!-- Report Details & Vertical Status Timeline (PRD FR-18 & FR-19) -->
    @if($report)
        @php
            $beforePhotos = $report->media->where('type', 'before');
            $afterPhotos = $report->media->where('type', 'after');
        @endphp

        <section class="bg-surface rounded-2xl border border-border shadow-[0_4px_14px_rgba(23,50,77,0.08)] overflow-hidden">
            <!-- Header Bar -->
            <div class="p-5 sm:p-7 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-canvas/60">
                <div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-navy text-white font-mono font-extrabold text-xs sm:text-sm tracking-wide shadow-xs">
                            {{ $report->ticket_id }}
                        </span>
                        <x-status-badge :status="$report->status" size="md" />
                        @if(!$report->is_visible_on_map)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-canvas text-text-muted border border-border">
                                <x-icon name="info" class="w-3.5 h-3.5 shrink-0" stroke-width="2.2" />
                                <span>Tidak tampil di peta publik</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-extrabold bg-primary-soft text-primary-hover border border-primary-hover/20">
                                <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" stroke-width="2.2" />
                                <span>Tampil di peta sekitar</span>
                            </span>
                        @endif
                    </div>
                    <h2 class="mt-3 text-xl sm:text-2xl font-extrabold text-navy">
                        {{ $report->title }}
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-text-muted font-semibold">
                        Dilaporkan {{ $report->created_at?->format('d M Y, H:i') }} WIB ({{ $report->created_at?->diffForHumans() }})
                    </p>
                </div>

                @auth
                    <a href="{{ route('reports.public.show', $report) }}"
                       class="min-h-[44px] px-4 py-2 rounded-xl bg-navy hover:bg-primary-hover text-white text-xs font-extrabold inline-flex items-center gap-2 shrink-0 transition">
                        <span>Kelola di Panel Petugas</span>
                        <x-icon name="arrow-right" class="w-4 h-4" />
                    </a>
                @endauth
            </div>

            <div class="p-5 sm:p-7 grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
                <!-- Left Column: Info, Photos (Before & After), and Map -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Metadata Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="p-4 rounded-2xl bg-canvas border border-border">
                            <p class="text-xs font-bold text-text-muted">Kategori Masalah</p>
                            <div class="mt-1 flex items-center gap-2 font-extrabold text-navy">
                                <x-category-icon :icon="$report->category?->icon ?? 'map-pin'" class="w-5 h-5 text-primary-hover" />
                                <span>{{ $report->category?->name }}</span>
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl bg-canvas border border-border">
                            <p class="text-xs font-bold text-text-muted">Instansi Penangan</p>
                            <p class="mt-1 font-extrabold text-navy">
                                {{ $report->agency?->name ?? 'Menunggu Penugasan Instansi' }}
                            </p>
                        </div>
                    </div>

                    <!-- SLA Target Card -->
                    @php
                        $sla = $report->getSlaMeta();
                    @endphp
                    <div class="p-4 sm:p-5 rounded-2xl border {{ $sla['is_overdue'] ? 'bg-danger/10 border-danger/30' : 'bg-canvas border-border' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-xs font-bold {{ $sla['is_overdue'] ? 'text-danger' : 'text-text-muted' }}">
                                Target Standar Waktu Penanganan (SLA)
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-extrabold text-navy text-sm">
                                    {{ $sla['target_label'] }}
                                </span>
                                <span class="text-xs text-text-muted font-semibold">
                                    (Batas target: {{ $sla['deadline_label'] }} WIB)
                                </span>
                            </div>
                        </div>

                        <div>
                            @if($sla['is_overdue'])
                                <span class="px-3 py-1.5 rounded-full text-xs font-extrabold bg-danger/10 text-danger border border-danger/30 inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <x-icon name="alert-triangle" class="w-4 h-4 text-danger" stroke-width="2.2" />
                                    <span>{{ $sla['badge_label'] }}</span>
                                </span>
                            @else
                                <span class="px-3 py-1.5 rounded-full text-xs font-extrabold {{ $sla['badge_type'] === 'success' ? 'bg-success-soft text-success border border-success/30' : 'bg-surface text-navy border border-border' }} inline-flex items-center gap-1.5 whitespace-nowrap">
                                    @if($sla['badge_type'] === 'success')
                                        <x-icon name="check-circle" class="w-4 h-4 text-success" stroke-width="2.2" />
                                    @else
                                        <x-icon name="clock" class="w-4 h-4 text-text-muted" stroke-width="2.2" />
                                    @endif
                                    <span>{{ $sla['badge_label'] }}</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Citizen Community Support Impact Summary (Matches Image 1) -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-surface border border-border space-y-3.5 shadow-xs">
                        <div class="flex items-center gap-3.5">
                            <!-- Left Rounded Icon Box -->
                            <div class="w-11 h-11 rounded-2xl bg-warning-soft border border-warning/30 flex items-center justify-center shrink-0 text-warning">
                                <x-icon name="thumbs-up" class="w-5 h-5 text-warning" stroke-width="2.2" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm sm:text-base font-extrabold text-navy leading-snug">Dukungan warga</h3>
                                <p class="text-xs sm:text-sm mt-0.5">
                                    <span class="font-extrabold text-warning tabular-nums">{{ (int) ($report->supports_count ?? 0) }} warga</span>
                                    <span class="text-text-muted font-medium">mengalami masalah ini</span>
                                </p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-border-soft">
                            <p class="text-xs sm:text-sm text-text-muted font-normal leading-relaxed">
                                Dukunganmu membantu dinas terkait memprioritaskan laporan ini, tanpa perlu membuat laporan ganda.
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h3 class="text-xs font-extrabold text-text-muted uppercase tracking-wider">Deskripsi Laporan Warga</h3>
                        <p class="mt-2 p-4 rounded-2xl bg-canvas text-sm sm:text-base text-navy leading-relaxed">
                            {{ $report->description }}
                        </p>
                    </div>

                    @if($report->status === 'ditolak' && $report->rejection_reason)
                        <div class="p-4 rounded-2xl bg-canvas border border-border text-sm text-navy">
                            <p class="font-extrabold text-danger flex items-center gap-1.5">
                                <x-icon name="x-circle" class="w-4 h-4" />
                                <span>Alasan Penolakan Laporan:</span>
                            </p>
                            <p class="mt-1 font-semibold text-text-muted">{{ $report->rejection_reason }}</p>
                        </div>
                    @endif

                    <!-- Before & After Photo Evidence Comparison (Style Guide 9.7) -->
                    <div class="space-y-4">
                        <h3 class="text-xs font-extrabold text-text-muted uppercase tracking-wider">
                            Dokumentasi Bukti Lapangan
                        </h3>

                        <div class="grid grid-cols-1 {{ $afterPhotos->isNotEmpty() ? 'sm:grid-cols-2' : '' }} gap-4">
                            <!-- Before Photos -->
                            <div class="space-y-2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary-soft text-primary-hover text-xs font-extrabold">
                                    Foto Sebelum (Laporan Warga)
                                </span>
                                <div class="grid grid-cols-1 gap-2.5">
                                    @foreach($beforePhotos as $media)
                                        <img src="{{ $media->publicUrl() }}"
                                             alt="Foto bukti laporan warga"
                                             class="w-full h-52 object-cover rounded-xl border border-border bg-canvas">
                                    @endforeach
                                </div>
                            </div>

                            <!-- After Photos (When completed by officer) -->
                            @if($afterPhotos->isNotEmpty())
                                <div class="space-y-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-success-soft text-success text-xs font-extrabold">
                                        <x-icon name="check-circle" class="w-3.5 h-3.5" />
                                        <span>Foto Sesudah Perbaikan (Petugas)</span>
                                    </span>
                                    <div class="grid grid-cols-1 gap-2.5">
                                        @foreach($afterPhotos as $media)
                                            <img src="{{ $media->publicUrl() }}"
                                                 alt="Foto bukti setelah perbaikan"
                                                 class="w-full h-52 object-cover rounded-xl border-2 border-success bg-canvas">
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Supporter Additional Photos Gallery -->
                        @if($report->supportsWithPhotos->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-border-soft">
                                <div class="flex items-center gap-2 mb-2.5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-soft text-primary-hover text-xs font-extrabold">
                                        <x-icon name="camera" class="w-3.5 h-3.5" />
                                        <span>Foto Bukti Tambahan dari Warga ({{ $report->supportsWithPhotos->count() }})</span>
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                    @foreach($report->supportsWithPhotos as $supporterMedia)
                                        <div class="group relative rounded-xl overflow-hidden border border-border bg-canvas shadow-2xs">
                                            <img src="{{ $supporterMedia->photoUrl() }}"
                                                 alt="Foto bukti tambahan warga"
                                                 class="w-full h-32 sm:h-36 object-cover transition-transform duration-200 group-hover:scale-105">
                                            @if($supporterMedia->comment)
                                                <div class="p-2 bg-surface text-[11px] font-medium text-navy border-t border-border-soft line-clamp-2">
                                                    "{{ $supporterMedia->comment }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Location Map Preview -->
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="text-xs font-extrabold text-text-muted uppercase tracking-wider">Titik Lokasi Masalah</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-mono font-bold text-text-muted tabular-nums">
                                    {{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}
                                </span>
                                <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="text-xs font-extrabold text-primary-hover hover:underline inline-flex items-center gap-1">
                                    <span>Google Maps</span>
                                    <x-icon name="external-link" class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-xs font-bold text-primary-hover mb-2">
                            <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" />
                            <span>{{ $report->location_label }}</span>
                        </div>
                        <div id="detail-report-map" class="w-full h-60 rounded-2xl border border-border overflow-hidden bg-canvas"></div>
                    </div>
                </div>

                <!-- Right Column: Vertical Status History Timeline (Style Guide 9.6) -->
                <div class="lg:col-span-5">
                    <div class="p-5 rounded-2xl bg-canvas border border-border">
                        <h3 class="text-base font-extrabold text-navy flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <span>Linimasa Penanganan</span>
                        </h3>
                        <p class="text-xs text-text-muted font-semibold mt-1">
                            Setiap perubahan status tercatat secara transparan beserta waktu dan tindak lanjutnya.
                        </p>

                        <ol class="mt-5 relative border-l-2 border-border ml-3 space-y-6">
                            @foreach($report->statusHistories as $history)
                                @php
                                    $stepMeta = \App\Models\Report::getStatusMetadata($history->to_status);
                                @endphp
                                <li class="ml-5">
                                    <span class="absolute -left-[11px] w-5 h-5 rounded-full border-2 border-white shadow-xs flex items-center justify-center"
                                          style="background-color: {{ $stepMeta['color'] }};"></span>

                                    <div class="bg-surface p-3.5 rounded-2xl border border-border shadow-2xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold"
                                                  style="background-color: {{ $stepMeta['bg'] }}; color: {{ $stepMeta['text'] }};">
                                                {{ $stepMeta['label'] }}
                                            </span>
                                            <time class="text-[11px] font-bold text-text-muted tabular-nums">
                                                {{ $history->created_at?->format('d M Y, H:i') }}
                                            </time>
                                        </div>

                                        <p class="mt-2 text-xs sm:text-sm font-bold text-navy leading-relaxed">
                                            {{ $history->note ?: $stepMeta['description'] }}
                                        </p>

                                        @if($history->actor)
                                            <p class="mt-1.5 text-[11px] font-semibold text-text-muted">
                                                Oleh: {{ $history->actor->name }}
                                            </p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>

                        @if($report->supportsWithComments->isNotEmpty())
                            <div class="mt-8 pt-6 border-t border-border">
                                <h4 class="text-sm font-extrabold text-navy uppercase tracking-wider flex items-center gap-2">
                                    <x-icon name="message-square" class="w-4 h-4 text-primary" stroke-width="2.2" />
                                    <span>Suara & Catatan Warga ({{ $report->supportsWithComments->count() }})</span>
                                </h4>
                                <p class="text-xs text-text-muted font-semibold mt-1">
                                    Catatan kondisi lapangan dari warga sekitar yang ikut mendukung penanganan laporan ini.
                                </p>
                                <div class="mt-4 space-y-2.5">
                                    @foreach($report->supportsWithComments as $supporterNote)
                                        <div class="p-3.5 rounded-xl bg-canvas border border-border-soft text-xs text-navy flex items-start gap-3">
                                            <div class="w-7 h-7 rounded-lg bg-primary-soft text-primary flex items-center justify-center shrink-0 mt-0.5">
                                                <x-icon name="user" class="w-4 h-4" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-navy leading-relaxed">
                                                    "{{ $supporterNote->comment }}"
                                                </p>
                                                <span class="text-[10px] text-text-muted font-semibold mt-1 block">
                                                    {{ $supporterNote->created_at ? $supporterNote->created_at->diffForHumans() : 'Baru saja' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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

