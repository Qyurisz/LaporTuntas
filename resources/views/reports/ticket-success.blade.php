@extends('layouts.app', ['title' => 'Tiket Laporan ' . $report->ticket_id . ' — Lapor Tuntas'])

@section('content')
<div x-data="ticketSuccessPage()"
     x-init="renderQrCode()"
     class="max-w-[780px] w-full mx-auto px-4 py-8 sm:py-12">

    <div class="bg-surface rounded-2xl border border-border shadow-[0_8px_24px_rgba(23,50,77,0.08)] overflow-hidden">
        <!-- Top Banner -->
        <div class="bg-primary px-6 py-5 text-white flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                    <x-icon name="check-circle" class="w-7 h-7 text-white" stroke-width="2.5" />
                </div>
                <div>
                    <span class="text-xs font-extrabold uppercase tracking-wider text-primary-soft">Laporan Berhasil Terkirim</span>
                    <h1 class="text-xl sm:text-2xl font-extrabold leading-tight">Simpan ID tiket untuk melacak laporanmu</h1>
                </div>
            </div>
            <x-status-badge :status="$report->status" />
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            <!-- Ticket ID Highlight & QR Box (Style Guide 9.5) -->
            <div class="p-5 sm:p-6 rounded-2xl bg-primary-soft/60 border-2 border-primary flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="text-center md:text-left flex-1">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-primary-hover">ID Tiket Unik Kamu</p>
                    <p class="mt-1 text-3xl sm:text-4xl font-mono font-extrabold tracking-wider text-navy tabular-nums select-all">
                        {{ $report->ticket_id }}
                    </p>
                    <p class="mt-2 text-xs sm:text-sm text-text-muted font-semibold">
                        Gunakan ID ini kapan saja di menu <strong>Lacak tiket</strong> tanpa perlu login.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center justify-center md:justify-start gap-2">
                        <button type="button"
                                @click="copyTicketId()"
                                class="min-h-[44px] px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-sm font-extrabold flex items-center gap-2 shadow-xs transition cursor-pointer">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                            </svg>
                            <span x-text="copied ? 'ID Tiket Tersalin!' : 'Salin ID Tiket'"></span>
                        </button>

                        <button type="button"
                                @click="downloadPngCard()"
                                class="min-h-[44px] px-4 py-2 rounded-xl bg-surface hover:bg-canvas border border-border text-navy text-sm font-extrabold flex items-center gap-2 transition cursor-pointer">
                            <svg class="w-4 h-4 text-primary-hover" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" x2="12" y1="15" y2="3"/>
                            </svg>
                            <span>Simpan Tiket (PNG + QR)</span>
                        </button>

                        <button type="button"
                                @click="window.print()"
                                class="min-h-[44px] px-4 py-2 rounded-xl bg-surface hover:bg-canvas border border-border text-navy text-sm font-extrabold flex items-center gap-2 transition cursor-pointer">
                            <span>Cetak / PDF</span>
                        </button>
                    </div>
                </div>

                <!-- QR Code Canvas -->
                <div class="bg-surface p-3.5 rounded-xl border border-border shadow-xs text-center shrink-0">
                    <canvas x-ref="qrCanvas" class="w-36 h-36 mx-auto"></canvas>
                    <p class="mt-1.5 text-[11px] font-extrabold text-text-muted">Scan untuk Lacak Status</p>
                </div>
            </div>

            <!-- Warning Notice to Save Ticket ID (Style Guide 9.5) -->
            <div class="p-4 rounded-xl bg-warning-soft border border-warning/30 text-warning text-xs sm:text-sm font-bold flex items-start gap-3">
                <x-icon name="alert-triangle" class="w-5 h-5 text-warning shrink-0" stroke-width="2.2" />
                <div>
                    <p class="font-extrabold">Penting: Simpan atau unduh bukti tiket ini sekarang</p>
                    <p class="font-semibold mt-0.5">
                        Demi menjaga privasi, Lapor Tuntas tidak meminta nomor HP atau email. Jika kamu kehilangan ID tiket ini, tiket tidak dapat dipulihkan kembali.
                    </p>
                </div>
            </div>

            <!-- Summary Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border text-sm">
                <div class="p-3.5 rounded-xl bg-canvas">
                    <p class="text-xs font-bold text-text-muted">Kategori Masalah</p>
                    <p class="font-extrabold text-navy mt-0.5">{{ $report->category?->name }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-canvas">
                    <p class="text-xs font-bold text-text-muted">Instansi Tujuan Awal</p>
                    <p class="font-extrabold text-navy mt-0.5">{{ $report->agency?->name ?? 'Menunggu Penugasan Moderator' }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-canvas sm:col-span-2">
                    <p class="text-xs font-bold text-text-muted">Deskripsi &amp; Lokasi GPS</p>
                    <p class="font-bold text-navy mt-0.5">{{ $report->description }}</p>
                    <p class="text-xs font-mono font-bold text-primary-hover mt-1 tabular-nums flex items-center gap-1.5">
                        <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ $report->location_label }} ({{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }})</span>
                    </p>
                </div>
            </div>

            <!-- Action Footer -->
            <div class="pt-4 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('home', ['focus' => $report->ticket_id, 'lat' => $report->latitude, 'lng' => $report->longitude]) }}"
                   class="w-full sm:w-auto min-h-[44px] px-5 py-2 rounded-xl bg-surface border border-border hover:bg-canvas text-navy font-extrabold text-sm flex items-center justify-center gap-2 transition">
                    <x-icon name="map-pin" class="w-4 h-4 text-primary-hover" />
                    <span>Lihat Laporan di Peta Utama</span>
                </a>
                <a href="{{ route('tickets.show', ['ticketId' => $report->ticket_id]) }}"
                   class="w-full sm:w-auto min-h-[44px] px-6 py-2 rounded-xl bg-navy hover:bg-primary-hover text-white font-extrabold text-sm flex items-center justify-center gap-2 transition">
                    <span>Lihat Halaman Lacak &amp; Linimasa</span>
                    <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function ticketSuccessPage() {
        return {
            copied: false,
            ticketData: {
                ticketId: @js($report->ticket_id),
                category: @js($report->category?->name ?? 'Masalah Publik'),
                description: @js($report->description),
                coordinates: @js($report->location_label . ' (' . number_format($report->latitude, 5) . ', ' . number_format($report->longitude, 5) . ')'),
                createdAt: @js($report->created_at?->format('d M Y, H:i') . ' WIB'),
                statusLabel: @js($statusMeta['label']),
                trackUrl: @js(route('tickets.show', ['ticketId' => $report->ticket_id])),
            },

            renderQrCode() {
                if (window.QRCode && this.$refs.qrCanvas) {
                    window.QRCode.toCanvas(this.$refs.qrCanvas, this.ticketData.trackUrl, {
                        width: 144,
                        margin: 1,
                        color: {
                            dark: '#17324D',
                            light: '#FFFFFF',
                        },
                    });
                }
            },

            async copyTicketId() {
                try {
                    await navigator.clipboard.writeText(this.ticketData.ticketId);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2500);
                } catch (e) {
                    alert('ID Tiket: ' + this.ticketData.ticketId);
                }
            },

            async downloadPngCard() {
                if (window.downloadTicketCardPng) {
                    await window.downloadTicketCardPng(this.ticketData);
                }
            },
        };
    }
</script>
@endsection

