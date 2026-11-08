@extends('layouts.app', ['title' => 'Tindak Lanjut Tiket ' . $report->ticket_id . ' - Lapor Tuntas'])

@section('content')
<div class="max-w-[1160px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <a href="{{ route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard') }}" class="text-xs font-extrabold text-primary-hover hover:underline inline-flex items-center gap-1.5">
                <x-icon name="arrow-left" class="w-3.5 h-3.5" />
                <span>Kembali ke Daftar Tugas</span>
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-2.5">
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-navy text-white font-mono font-extrabold text-xs sm:text-sm tracking-wide shadow-xs">
                    {{ $report->ticket_id }}
                </span>
                <x-status-badge :status="$report->status" size="md" />
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $report->isVisibleOnPublicMap() ? 'bg-primary-soft text-primary-hover border border-primary-hover/20' : 'bg-canvas text-text-muted border border-border' }}">
                    @if($report->isVisibleOnPublicMap())
                        <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" stroke-width="2.2" />
                        <span>Tampil di Peta Publik</span>
                    @elseif($report->status === \App\Models\Report::STATUS_SELESAI)
                        <x-icon name="clock" class="w-3.5 h-3.5 shrink-0" stroke-width="2" />
                        <span>Diarsipkan dari Peta (> 7 hari)</span>
                    @else
                        <x-icon name="info" class="w-3.5 h-3.5 shrink-0" stroke-width="2" />
                        <span>Disembunyikan dari Peta Publik</span>
                    @endif
                </span>
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold text-navy">{{ $report->title }}</h1>
        </div>

        <a href="{{ route('tickets.show', ['ticketId' => $report->ticket_id]) }}"
           target="_blank"
           class="min-h-[44px] px-4 py-2 rounded-xl bg-surface border border-border hover:bg-canvas text-navy text-xs font-extrabold inline-flex items-center gap-2 shrink-0 transition">
            <span>Lihat Halaman Publik Warga</span>
            <x-icon name="external-link" class="w-4 h-4 text-text-muted" />
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-danger/15 border border-danger text-danger text-xs sm:text-sm font-bold">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left Column: Report Details & Photos -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-surface rounded-2xl border border-border p-5 sm:p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-sm">
                    <div class="p-3.5 rounded-xl bg-canvas border border-border">
                        <p class="text-xs font-bold text-text-muted">Kategori &amp; Target SLA</p>
                        <p class="font-extrabold text-navy mt-0.5">{{ $report->category?->name }}</p>
                        @php
                            $sla = $report->getSlaMeta();
                        @endphp
                        <div class="mt-1.5 flex items-center gap-1.5">
                            @if($sla['is_overdue'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#FDE8E8] text-[#9B1C1C] border border-[#F98080]/50">
                                    <x-icon name="alert-triangle" class="w-3.5 h-3.5 shrink-0 text-[#9B1C1C]" stroke-width="2.2" />
                                    <span>{{ $sla['badge_label'] }} (Batas: {{ $sla['deadline_label'] }})</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-surface text-text-muted border border-border">
                                    <x-icon name="clock" class="w-3.5 h-3.5 shrink-0 text-text-muted" stroke-width="2" />
                                    <span>{{ $sla['badge_label'] }} (Target: {{ $sla['target_label'] }})</span>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-canvas border border-border">
                        <p class="text-xs font-bold text-text-muted">Instansi &amp; Dukungan Warga</p>
                        <p class="font-extrabold text-navy mt-0.5">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
                        <div class="mt-1.5 flex items-center gap-2">
                            @if($report->assignedOfficer)
                                <p class="text-xs font-bold text-primary-hover">{{ $report->assignedOfficer->name }}</p>
                            @endif
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold {{ $report->supports_count > 0 ? 'bg-[#FEF5DC] text-[#8A5D00] border border-[#F5B82E]/40' : 'bg-surface text-text-muted border border-border' }}">
                                <x-icon name="thumbs-up" class="w-3 h-3 shrink-0" stroke-width="2.2" />
                                <span>{{ $report->supports_count ?? 0 }} Dukungan Warga</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-text-muted">Deskripsi Masalah</h2>
                    <p class="mt-1.5 p-4 rounded-2xl bg-canvas text-sm font-semibold text-navy leading-relaxed">
                        {{ $report->description }}
                    </p>
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <h2 class="text-xs font-extrabold uppercase tracking-wider text-text-muted">Titik GPS Lokasi Masalah</h2>
                        <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}"
                           target="_blank"
                           rel="noopener"
                           class="text-xs font-extrabold text-primary-hover hover:underline inline-flex items-center gap-1">
                            <span>Buka Navigasi Google Maps</span>
                            <x-icon name="external-link" class="w-3 h-3" />
                        </a>
                    </div>
                    <p class="text-xs font-bold text-primary-hover mb-2 inline-flex items-center gap-1">
                        <x-icon name="map-pin" class="w-3.5 h-3.5 shrink-0" />
                        <span>{{ $report->location_label }} ({{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }})</span>
                    </p>
                    <div id="staff-report-map" class="w-full h-60 rounded-2xl border border-border overflow-hidden bg-canvas"></div>
                </div>

                <!-- Media Evidence -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="inline-block px-2.5 py-1 rounded-full bg-primary-soft text-primary-hover text-xs font-extrabold mb-2">
                            Foto Sebelum (Warga)
                        </span>
                        @foreach($report->media->where('type', 'before') as $media)
                            <img src="{{ $media->publicUrl() }}"
                                 alt="Foto bukti sebelum perbaikan"
                                 class="w-full h-48 object-cover rounded-2xl border border-border mb-2">
                        @endforeach
                    </div>

                    <div>
                        <span class="inline-block px-2.5 py-1 rounded-full bg-success-soft text-success text-xs font-extrabold mb-2">
                            Foto Sesudah Perbaikan (Petugas)
                        </span>
                        @forelse($report->media->where('type', 'after') as $media)
                            <img src="{{ $media->publicUrl() }}"
                                 alt="Foto setelah perbaikan"
                                 class="w-full h-48 object-cover rounded-2xl border-2 border-success mb-2">
                        @empty
                            <div class="h-48 rounded-2xl border border-dashed border-border bg-canvas flex items-center justify-center p-4 text-center text-xs font-semibold text-text-muted">
                                Belum ada foto bukti perbaikan yang diunggah petugas.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Supporter Additional Photos Evidence Gallery -->
                @if($report->supportsWithPhotos->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-border-soft">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-soft text-primary-hover text-xs font-extrabold mb-2">
                            <x-icon name="camera" class="w-3.5 h-3.5" />
                            <span>Foto Bukti Tambahan dari Warga ({{ $report->supportsWithPhotos->count() }})</span>
                        </span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            @foreach($report->supportsWithPhotos as $supporterMedia)
                                <div class="rounded-xl overflow-hidden border border-border bg-canvas">
                                    <img src="{{ $supporterMedia->photoUrl() }}"
                                         alt="Foto bukti tambahan warga"
                                         class="w-full h-32 object-cover">
                                    @if($supporterMedia->comment)
                                        <div class="p-2 text-[11px] font-medium text-navy bg-surface border-t border-border-soft line-clamp-2">
                                            "{{ $supporterMedia->comment }}"
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Supporter Comments & Field Notes -->
                @if($report->supportsWithComments->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-border-soft">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-soft text-primary-hover text-xs font-extrabold mb-2">
                            <x-icon name="message-square" class="w-3.5 h-3.5" />
                            <span>Suara & Catatan Warga ({{ $report->supportsWithComments->count() }})</span>
                        </span>
                        <div class="space-y-2 mt-1 max-h-48 overflow-y-auto pr-1">
                            @foreach($report->supportsWithComments as $supporterNote)
                                <div class="p-3 rounded-xl bg-canvas border border-border-soft text-xs text-navy">
                                    <p class="font-medium">"{{ $supporterNote->comment }}"</p>
                                    <span class="text-[10px] text-text-muted mt-1 block font-semibold">
                                        {{ $supporterNote->created_at ? $supporterNote->created_at->diffForHumans() : 'Baru saja' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Action Forms (Update Status, Admin Assignment, Reject, Timeline) -->
        <div class="lg:col-span-5 space-y-5">

            @if($user->isPetugas())
            <!-- 1. Officer Operational Status Update Form -->
            <div class="bg-surface rounded-2xl border-2 border-primary p-5 sm:p-6 shadow-[0_4px_14px_rgba(23,50,77,0.08)]">
                <span class="text-caption font-extrabold uppercase tracking-wider text-primary-hover">Pembaruan Petugas</span>
                <h2 class="text-lg font-extrabold text-navy mt-0.5">Progres Penanganan Lapangan</h2>
                <p class="text-xs text-text-muted font-semibold mt-1">
                    Saat ditandai <strong>Selesai</strong>, penanda pada peta publik tetap tampil berwarna hijau beserta bukti tindak lanjut.
                </p>

                <form action="{{ route('staff.reports.status', $report) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="status-select" class="block text-xs font-extrabold text-navy mb-1">Pilih Status Baru</label>
                        <select id="status-select"
                                name="status"
                                required
                                class="w-full min-h-[48px] px-4 py-2 rounded-xl border border-border text-sm font-extrabold text-navy bg-surface focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
                            <option value="ditugaskan" @selected($report->status === 'ditugaskan')>Ditugaskan ke Petugas</option>
                            <option value="diproses" @selected($report->status === 'diproses' || $report->status === 'baru')>Diproses di Lapangan</option>
                            <option value="selesai" @selected($report->status === 'selesai')>Selesai / Tuntas</option>
                        </select>
                    </div>

                    <div>
                        <label for="after-photo-input" class="block text-xs font-extrabold text-navy mb-1">
                            Foto Bukti Setelah Perbaikan (Opsional, disarankan saat Selesai)
                        </label>
                        <input id="after-photo-input"
                               type="file"
                               name="after_photo"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full min-h-[44px] px-4 py-2 rounded-xl border border-dashed border-success bg-success-soft/40 text-xs font-bold text-navy file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-success file:text-white file:font-extrabold">
                    </div>

                    <div>
                        <label for="status-note" class="block text-xs font-extrabold text-navy mb-1">
                            Catatan Tindak Lanjut Publik <span class="text-danger">*</span>
                        </label>
                        <textarea id="status-note"
                                  name="note"
                                  rows="3"
                                  required
                                  placeholder="Contoh: Tim lapangan sudah menambal lubang jalan dengan hotmix dan lokasi sudah aman dilalui..."
                                  class="w-full p-3 rounded-xl border border-border text-sm text-navy focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full min-h-[44px] px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white font-extrabold text-sm shadow-[0_4px_14px_rgba(22,166,216,0.3)] transition cursor-pointer active:scale-[0.99]">
                        Simpan Perubahan Status
                    </button>
                </form>
            </div>
            @endif

            <!-- 2. Admin Only: Manual Assignment & Moderation Reject (FR-22 & FR-24) -->
            @if($user->isAdmin())
                <div class="bg-surface rounded-2xl border border-border p-5 sm:p-6 space-y-4">
                    <div>
                        <span class="text-caption font-extrabold uppercase tracking-wider text-[#7966D9]">Tugas Administrator</span>
                        <h3 class="text-base font-extrabold text-navy">Tentukan Dinas Penanggung Jawab</h3>
                        <p class="mt-1 text-xs text-text-muted">Kategori ini disarankan ditangani oleh {{ $report->category?->defaultAgency?->name ?? 'dinas terkait' }}.</p>
                    </div>

                    <form action="{{ route('admin.reports.assign', $report) }}" method="POST" class="space-y-3.5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="agency-select" class="block text-xs font-extrabold text-navy mb-1">Instansi Berwenang</label>
                            <select id="agency-select"
                                    name="agency_id"
                                    required
                                    class="w-full min-h-[44px] px-4 py-2 rounded-xl border border-border text-xs font-extrabold text-navy bg-surface focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" @selected(($report->agency_id ?? $report->category?->default_agency_id) === $agency->id)>
                                        {{ $agency->code }} — {{ $agency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($sharedPetugas)
                            <input type="hidden" name="assigned_to" value="{{ $sharedPetugas->id }}">
                            <div class="rounded-xl bg-canvas border border-border p-3">
                                <p class="text-xs font-extrabold text-navy">Petugas Pelaksana</p>
                                <p class="mt-1 text-xs text-text-muted">{{ $sharedPetugas->name }} · akun bersama untuk seluruh dinas</p>
                            </div>
                        @else
                            <div class="rounded-xl bg-warning-surface border border-warning/30 p-3 text-xs font-bold text-warning-text">
                                Akun petugas bersama belum tersedia. Jalankan seeder database untuk menyiapkannya.
                            </div>
                        @endif

                        <div>
                            <label for="assign-note" class="block text-xs font-extrabold text-navy mb-1">Catatan Penugasan (Opsional)</label>
                            <input id="assign-note"
                                   type="text"
                                   name="note"
                                   placeholder="Misal: Mohon segera ditinjau hari ini"
                                   class="w-full min-h-[44px] px-4 py-2 rounded-xl border border-border text-xs text-navy focus:outline-none focus:ring-3 focus:ring-primary/35 focus:border-primary">
                        </div>

                        <button type="submit" @disabled(! $sharedPetugas)
                                class="w-full min-h-[44px] px-4 py-2.5 rounded-xl bg-navy hover:bg-primary-hover disabled:opacity-50 text-white text-xs font-extrabold transition cursor-pointer active:scale-[0.99]">
                            Tetapkan Dinas dan Kirim ke Petugas
                        </button>
                        </button>
                    </form>

                    <!-- Reject Spam/Invalid Form -->
                    <div class="pt-4 border-t border-border" x-data="{ showReject: false }">
                        <button type="button"
                                @click="showReject = !showReject"
                                class="text-xs font-extrabold text-danger hover:underline inline-flex items-center gap-1.5 cursor-pointer">
                            <x-icon name="x-circle" class="w-3.5 h-3.5" />
                            <span>Tolak Laporan (Spam / Tidak Valid / Di Luar Kewenangan)</span>
                        </button>

                        <form x-show="showReject"
                              x-transition
                              action="{{ route('admin.reports.reject', $report) }}"
                              method="POST"
                              class="mt-3 space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="rejection-reason" class="block text-xs font-extrabold text-danger mb-1">
                                    Alasan Penolakan (Ditampilkan Transparan di Lacak Tiket) *
                                </label>
                                <textarea id="rejection-reason"
                                          name="rejection_reason"
                                          rows="2"
                                          required
                                          minlength="10"
                                          placeholder="Jelaskan alasan penolakan secara sopan dan jelas..."
                                          class="w-full p-2.5 rounded-xl border border-danger text-xs text-navy focus:outline-none focus:ring-3 focus:ring-danger/30"></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full min-h-[40px] px-4 py-2 rounded-xl bg-danger hover:opacity-90 text-white text-xs font-extrabold cursor-pointer transition active:scale-[0.99]">
                                Konfirmasi Tolak Laporan
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- 3. Status History Timeline -->
            <div class="bg-surface rounded-2xl border border-border p-5 sm:p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-navy mb-4">Riwayat Perubahan Status</h3>
                <ol class="relative border-l-2 border-border ml-2.5 space-y-4">
                    @foreach($report->statusHistories as $history)
                        @php
                            $meta = \App\Models\Report::getStatusMetadata($history->to_status);
                        @endphp
                        <li class="ml-4">
                            <span class="absolute -left-[9px] w-4 h-4 rounded-full border-2 border-white"
                                  style="background-color: {{ $meta['color'] }};"></span>
                            <div class="text-xs">
                                <span class="font-extrabold text-navy">{{ $meta['label'] }}</span>
                                <span class="text-text-muted font-semibold"> • {{ $history->created_at?->format('d M Y H:i') }}</span>
                                <p class="mt-0.5 text-text-muted font-semibold">{{ $history->note }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>

@php
    $staffPinMeta = \App\Models\Report::getStatusMetadata($report->status);
@endphp
<script>
    (function () {
        const mountStaffMap = function () {
            if (typeof window.initStaticReportMap === 'function') {
                window.initStaticReportMap(
                    'staff-report-map',
                    {{ (float) $report->latitude }},
                    {{ (float) $report->longitude }},
                    @js($report->title),
                    @js($staffPinMeta['color'])
                );
            }
        };
        if (window.L && typeof window.initStaticReportMap === 'function') {
            mountStaffMap();
        } else {
            window.addEventListener('leaflet:ready', mountStaffMap, { once: true });
            window.addEventListener('load', mountStaffMap, { once: true });
        }
    })();
</script>
@endsection

