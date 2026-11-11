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
        <!-- Left Column: Report Details & Photos (Matches Image 1) -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-surface rounded-2xl sm:rounded-3xl border border-border p-5 sm:p-7 space-y-6 shadow-xs">
                <!-- Header: Label & Title -->
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-muted">MASALAH</p>
                    <h2 class="mt-1 text-lg sm:text-xl font-extrabold text-navy leading-snug">
                        {{ $report->title }}
                    </h2>
                </div>

                <!-- Metadata Rows -->
                <div class="space-y-3 text-xs sm:text-sm">
                    <div class="grid grid-cols-12 gap-2 items-baseline">
                        <span class="col-span-4 sm:col-span-3 text-text-muted font-bold">Kategori</span>
                        <span class="col-span-8 sm:col-span-9 font-extrabold text-navy">{{ $report->category?->name ?? 'Lainnya' }}</span>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-baseline">
                        <span class="col-span-4 sm:col-span-3 text-text-muted font-bold">Instansi</span>
                        <div class="col-span-8 sm:col-span-9">
                            <p class="font-extrabold text-navy">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
                            @if($report->assignedOfficer)
                                <p class="text-xs text-text-muted font-medium mt-0.5">{{ $report->assignedOfficer->name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-baseline">
                        <span class="col-span-4 sm:col-span-3 text-text-muted font-bold">SLA</span>
                        <div class="col-span-8 sm:col-span-9">
                            @php $sla = $report->getSlaMeta(); @endphp
                            <span class="font-extrabold {{ $sla['is_overdue'] ? 'text-danger' : 'text-navy' }}">
                                {{ $sla['badge_label'] }}
                            </span>
                            <span class="text-text-muted font-normal">(target {{ $sla['target_label'] }})</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-2 items-baseline">
                        <span class="col-span-4 sm:col-span-3 text-text-muted font-bold">Dukungan</span>
                        <span class="col-span-8 sm:col-span-9 font-extrabold text-navy tabular-nums">{{ (int) ($report->supports_count ?? 0) }} warga</span>
                    </div>
                </div>

                <!-- Lokasi Section -->
                <div class="space-y-2 pt-1 border-t border-border-soft">
                    <div class="flex items-center justify-between pt-3">
                        <h3 class="text-xs sm:text-sm font-extrabold text-navy">Lokasi</h3>
                        <a href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}"
                           target="_blank"
                           rel="noopener"
                           class="text-xs font-bold text-primary hover:underline">
                            Buka di Google Maps
                        </a>
                    </div>
                    <p class="text-xs sm:text-sm text-text-muted font-medium">
                        {{ $report->location_label }}
                    </p>
                    <div id="staff-report-map" class="w-full h-52 sm:h-56 rounded-2xl border border-border overflow-hidden bg-canvas"></div>
                </div>

                <!-- Sebelum & Sesudah Photos -->
                <div class="pt-1 border-t border-border-soft">
                    <div class="grid grid-cols-2 gap-4 pt-3">
                        <!-- Sebelum -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-extrabold text-navy mb-2">Sebelum</h4>
                            @php $beforePhoto = $report->media->where('type', 'before')->first(); @endphp
                            @if($beforePhoto)
                                <img src="{{ $beforePhoto->publicUrl() }}"
                                     alt="Foto sebelum perbaikan"
                                     class="w-full h-44 sm:h-48 object-cover rounded-2xl border border-border">
                            @else
                                <div class="w-full h-44 sm:h-48 rounded-2xl bg-[#D7E1E8] flex items-center justify-center text-text-muted/70">
                                    <x-icon name="image" class="w-8 h-8" stroke-width="1.8" />
                                </div>
                            @endif
                        </div>

                        <!-- Sesudah -->
                        <div>
                            <h4 class="text-xs sm:text-sm font-extrabold text-navy mb-2">Sesudah</h4>
                            @php $afterPhoto = $report->media->where('type', 'after')->first(); @endphp
                            @if($afterPhoto)
                                <img src="{{ $afterPhoto->publicUrl() }}"
                                     alt="Foto sesudah perbaikan"
                                     class="w-full h-44 sm:h-48 object-cover rounded-2xl border-2 border-success">
                            @else
                                <div class="w-full h-44 sm:h-48 rounded-2xl border border-dashed border-border flex items-center justify-center p-4 text-center text-xs text-text-muted font-medium">
                                    Belum ada foto perbaikan.
                                </div>
                            @endif
                        </div>
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

            <!-- 2. Admin Only: Manual Assignment & Moderation Reject (Matches Image 2) -->
            @if($user->isAdmin())
                <div class="bg-surface rounded-2xl sm:rounded-3xl border border-border p-5 sm:p-7 space-y-4 shadow-xs"
                     x-data="{
                         selectedAgencyId: '{{ $report->agency_id ?? $report->category?->default_agency_id }}',
                         selectedOfficerId: '{{ $report->assigned_to ?? '' }}',
                         showReject: false
                     }">
                    <h3 class="text-base sm:text-lg font-extrabold text-navy">Tugaskan laporan</h3>

                    <form action="{{ route('admin.reports.assign', $report) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <!-- Instansi Field -->
                        <div>
                            <label for="agency-select" class="block text-xs font-bold text-navy mb-1.5">Instansi</label>
                            <div class="relative">
                                <select id="agency-select"
                                        name="agency_id"
                                        x-model="selectedAgencyId"
                                        required
                                        class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-border text-xs sm:text-sm font-bold text-navy bg-surface appearance-none pr-9 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary cursor-pointer">
                                    @foreach($agencies as $agency)
                                        <option value="{{ $agency->id }}" @selected(($report->agency_id ?? $report->category?->default_agency_id) === $agency->id)>
                                            {{ $agency->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-text-muted">
                                    <x-icon name="chevron-down" class="w-4 h-4" stroke-width="2.2" />
                                </div>
                            </div>
                            <p class="mt-1.5 text-xs text-text-muted font-medium">
                                Disarankan untuk kategori {{ $report->category?->name ?? 'masalah ini' }}.
                            </p>
                        </div>

                        <!-- Petugas Field -->
                        <div>
                            <label for="officer-select" class="block text-xs font-bold text-navy mb-1.5">Petugas</label>
                            <div class="relative">
                                <select id="officer-select"
                                        name="assigned_to"
                                        x-model="selectedOfficerId"
                                        required
                                        class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-border text-xs sm:text-sm font-bold text-navy bg-surface appearance-none pr-9 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary cursor-pointer">
                                    <option value="">-- Pilih Petugas Pelaksana --</option>
                                    @foreach($officers as $officer)
                                        <option value="{{ $officer->id }}" @selected($report->assigned_to === $officer->id || (! $report->assigned_to && $officer->agency_id === ($report->agency_id ?? $report->category?->default_agency_id)))>
                                            {{ $officer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-text-muted">
                                    <x-icon name="chevron-down" class="w-4 h-4" stroke-width="2.2" />
                                </div>
                            </div>
                            <p class="mt-1.5 text-xs text-text-muted font-medium">
                                {{ $report->assignedOfficer?->email ?? ($officers->first()?->email ?? 'petugas@laportuntas.id') }}
                            </p>
                        </div>

                        <!-- Catatan Field -->
                        <div>
                            <label for="assign-note" class="block text-xs font-bold text-navy mb-1.5">Catatan (opsional)</label>
                            <input id="assign-note"
                                   type="text"
                                   name="note"
                                   placeholder="Mis. mohon segera ditinjau hari ini"
                                   class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl border border-border text-xs sm:text-sm text-navy placeholder:text-text-muted/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        </div>

                        <!-- Action Row (Matches Image 2) -->
                        <div class="pt-2 flex items-center justify-between gap-3">
                            <button type="button"
                                    @click="showReject = !showReject"
                                    class="text-xs sm:text-sm font-bold text-danger hover:underline cursor-pointer">
                                Tolak laporan
                            </button>

                            <button type="submit"
                                    class="min-h-[42px] px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-extrabold shadow-[0_4px_14px_rgba(10,123,157,0.3)] transition active:scale-[0.99] cursor-pointer">
                                Tugaskan
                            </button>
                        </div>
                    </form>

                    <!-- Reject Form Box (appears when clicking Tolak laporan) -->
                    <div x-show="showReject"
                         x-transition
                         class="pt-3 border-t border-border mt-3">
                        <form action="{{ route('admin.reports.reject', $report) }}"
                              method="POST"
                              class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="rejection-reason" class="block text-xs font-bold text-danger mb-1">
                                    Alasan Penolakan Laporan <span class="text-danger">*</span>
                                </label>
                                <textarea id="rejection-reason"
                                          name="rejection_reason"
                                          rows="2"
                                          required
                                          minlength="10"
                                          placeholder="Jelaskan alasan penolakan secara jelas (mis. laporan spam / di luar kewenangan dinas)..."
                                          class="w-full p-2.5 rounded-xl border border-danger/40 text-xs text-navy focus:outline-none focus:ring-2 focus:ring-danger/30"></textarea>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                        @click="showReject = false"
                                        class="px-3 py-1.5 rounded-xl border border-border text-xs font-bold text-text-muted hover:bg-canvas">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="px-4 py-1.5 rounded-xl bg-danger hover:opacity-90 text-white text-xs font-extrabold transition cursor-pointer">
                                    Konfirmasi Tolak
                                </button>
                            </div>
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

