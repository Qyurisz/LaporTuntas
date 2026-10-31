@extends('layouts.app', ['title' => 'Tindak Lanjut Tiket ' . $report->ticket_id . ' — Panel Petugas'])

@section('content')
<div class="max-w-[1160px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <a href="{{ route('staff.dashboard') }}" class="text-xs font-extrabold text-[#0D8BB8] hover:underline inline-flex items-center gap-1">
                <span>← Kembali ke Daftar Tugas</span>
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-2.5">
                <span class="px-3 py-1 rounded-lg bg-[#17324D] text-white font-mono font-extrabold text-sm tabular-nums">
                    {{ $report->ticket_id }}
                </span>
                <x-status-badge :status="$report->status" />
                <span class="text-xs font-bold {{ $report->is_visible_on_map ? 'text-[#0D8BB8]' : 'text-[#278653]' }}">
                    {{ $report->is_visible_on_map ? '● Penanda Aktif di Peta Publik' : '✓ Penanda Sudah Ditutup dari Peta Publik' }}
                </span>
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold text-[#17324D]">{{ $report->title }}</h1>
        </div>

        <a href="{{ route('tickets.show', ['ticketId' => $report->ticket_id]) }}"
           target="_blank"
           class="min-h-[44px] px-4 py-2.5 rounded-xl bg-white border border-[#D7E1E5] hover:bg-[#F3F7F8] text-[#17324D] text-xs font-extrabold inline-flex items-center gap-1.5 shrink-0">
            <span>Lihat Halaman Publik Warga</span>
            <span>↗</span>
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-[#FF6B5C]/15 border border-[#D94343] text-[#D94343] text-xs sm:text-sm font-bold">
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
            <div class="bg-white rounded-[24px] border border-[#D7E1E5] p-5 sm:p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-sm">
                    <div class="p-3.5 rounded-xl bg-[#F3F7F8]">
                        <p class="text-xs font-bold text-[#617482]">Kategori</p>
                        <p class="font-extrabold text-[#17324D] mt-0.5">{{ $report->category?->name }}</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-[#F3F7F8]">
                        <p class="text-xs font-bold text-[#617482]">Instansi &amp; Petugas</p>
                        <p class="font-extrabold text-[#17324D] mt-0.5">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
                        @if($report->assignedOfficer)
                            <p class="text-xs font-bold text-[#0D8BB8]">{{ $report->assignedOfficer->name }}</p>
                        @endif
                    </div>
                </div>

                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-[#617482]">Deskripsi Masalah</h2>
                    <p class="mt-1.5 p-4 rounded-2xl bg-[#F3F7F8] text-sm font-semibold text-[#17324D] leading-relaxed">
                        {{ $report->description }}
                    </p>
                </div>

                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-[#617482] mb-2">Titik GPS Lokasi Masalah</h2>
                    <p class="text-xs font-bold text-[#0D8BB8] mb-2">
                        📍 {{ $report->location_label }} ({{ number_format($report->latitude, 6) }}, {{ number_format($report->longitude, 6) }})
                    </p>
                    <div id="staff-report-map" class="w-full h-52 rounded-2xl border border-[#D7E1E5] overflow-hidden bg-[#EAF1E8]"></div>
                </div>

                <!-- Media Evidence -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-[#DFF5FC] text-[#0D8BB8] text-xs font-extrabold mb-2">
                            Foto Sebelum (Warga)
                        </span>
                        @foreach($report->media->where('type', 'before') as $media)
                            <img src="{{ $media->publicUrl() }}"
                                 alt="Foto bukti sebelum perbaikan"
                                 class="w-full h-48 object-cover rounded-2xl border border-[#D7E1E5] mb-2">
                        @endforeach
                    </div>

                    <div>
                        <span class="inline-block px-2.5 py-1 rounded-lg bg-[#E1F5EA] text-[#185B37] text-xs font-extrabold mb-2">
                            Foto Sesudah Perbaikan (Petugas)
                        </span>
                        @forelse($report->media->where('type', 'after') as $media)
                            <img src="{{ $media->publicUrl() }}"
                                 alt="Foto setelah perbaikan"
                                 class="w-full h-48 object-cover rounded-2xl border-2 border-[#278653] mb-2">
                        @empty
                            <div class="h-48 rounded-2xl border border-dashed border-[#D7E1E5] bg-[#F3F7F8] flex items-center justify-center p-4 text-center text-xs font-semibold text-[#617482]">
                                Belum ada foto bukti perbaikan yang diunggah petugas.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Action Forms (Update Status, Admin Assignment, Reject, Timeline) -->
        <div class="lg:col-span-5 space-y-5">

            <!-- 1. Officer & Admin Operational Status Update Form (FR-22 & FR-23) -->
            <div class="bg-white rounded-[24px] border-2 border-[#16A6D8] p-5 sm:p-6 shadow-[0_4px_14px_rgba(23,50,77,0.08)]">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#0D8BB8]">Tindakan Petugas &amp; Admin</span>
                <h2 class="text-lg font-extrabold text-[#17324D] mt-0.5">Perbarui Status &amp; Tandai Selesai</h2>
                <p class="text-xs text-[#617482] font-semibold mt-1">
                    Saat ditandai <strong>Selesai</strong>, penanda otomatis hilang dari peta beranda publik namun tetap dapat dilacak lewat ID tiket.
                </p>

                <form action="{{ route('staff.reports.status', $report) }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="status-select" class="block text-xs font-extrabold text-[#17324D] mb-1">Pilih Status Baru</label>
                        <select id="status-select"
                                name="status"
                                required
                                class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-[#D7E1E5] text-sm font-extrabold text-[#17324D] bg-white">
                            <option value="ditugaskan" @selected($report->status === 'ditugaskan')>Ditugaskan ke Petugas</option>
                            <option value="diproses" @selected($report->status === 'diproses' || $report->status === 'baru')>Sedang Diproses di Lapangan</option>
                            <option value="selesai" @selected($report->status === 'selesai')>✓ Selesai / Tuntas (Sembunyikan dari Peta)</option>
                        </select>
                    </div>

                    <div>
                        <label for="after-photo-input" class="block text-xs font-extrabold text-[#17324D] mb-1">
                            Foto Bukti Setelah Perbaikan (Opsional, disarankan saat Selesai)
                        </label>
                        <input id="after-photo-input"
                               type="file"
                               name="after_photo"
                               accept="image/jpeg,image/png,image/webp"
                               class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-dashed border-[#278653] bg-[#E1F5EA]/40 text-xs font-bold text-[#17324D] file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-[#278653] file:text-white file:font-extrabold">
                    </div>

                    <div>
                        <label for="status-note" class="block text-xs font-extrabold text-[#17324D] mb-1">
                            Catatan Tindak Lanjut Publik <span class="text-[#D94343]">*</span>
                        </label>
                        <textarea id="status-note"
                                  name="note"
                                  rows="3"
                                  required
                                  placeholder="Contoh: Tim lapangan sudah menambal lubang jalan dengan hotmix dan lokasi sudah aman dilalui..."
                                  class="w-full p-3 rounded-xl border border-[#D7E1E5] text-sm text-[#17324D]"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full min-h-[48px] px-5 py-3 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white font-extrabold text-sm shadow-[0_4px_14px_rgba(22,166,216,0.3)] transition cursor-pointer">
                        Simpan Perubahan Status
                    </button>
                </form>
            </div>

            <!-- 2. Admin Only: Manual Assignment & Moderation Reject (FR-22 & FR-24) -->
            @if($user->isAdmin())
                <div class="bg-white rounded-[24px] border border-[#D7E1E5] p-5 sm:p-6 space-y-4">
                    <div>
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#7966D9]">Khusus Moderator / Admin</span>
                        <h3 class="text-base font-extrabold text-[#17324D]">Penugasan Instansi &amp; Petugas</h3>
                    </div>

                    <form action="{{ route('staff.reports.assign', $report) }}" method="POST" class="space-y-3.5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="agency-select" class="block text-xs font-extrabold text-[#17324D] mb-1">Instansi Berwenang</label>
                            <select id="agency-select"
                                    name="agency_id"
                                    required
                                    class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                                @foreach($agencies as $agency)
                                    <option value="{{ $agency->id }}" @selected($report->agency_id === $agency->id)>
                                        {{ $agency->code }} — {{ $agency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="officer-select" class="block text-xs font-extrabold text-[#17324D] mb-1">Petugas Lapangan</label>
                            <select id="officer-select"
                                    name="assigned_to"
                                    class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                                <option value="">-- Pilih Petugas Lapangan --</option>
                                @foreach($officers as $officer)
                                    <option value="{{ $officer->id }}" @selected($report->assigned_to === $officer->id)>
                                        {{ $officer->name }} ({{ $officer->agency?->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assign-note" class="block text-xs font-extrabold text-[#17324D] mb-1">Catatan Penugasan (Opsional)</label>
                            <input id="assign-note"
                                   type="text"
                                   name="note"
                                   placeholder="Misal: Mohon segera ditinjau hari ini"
                                   class="w-full min-h-[44px] px-3 py-2 rounded-xl border border-[#D7E1E5] text-xs text-[#17324D]">
                        </div>

                        <button type="submit"
                                class="w-full min-h-[44px] px-4 py-2.5 rounded-xl bg-[#17324D] hover:bg-[#0D8BB8] text-white text-xs font-extrabold transition cursor-pointer">
                            Tugaskan Laporan
                        </button>
                    </form>

                    <!-- Reject Spam/Invalid Form -->
                    <div class="pt-4 border-t border-[#D7E1E5]" x-data="{ showReject: false }">
                        <button type="button"
                                @click="showReject = !showReject"
                                class="text-xs font-extrabold text-[#D94343] hover:underline cursor-pointer">
                            ✕ Tolak Laporan (Spam / Tidak Valid / Di Luar Kewenangan)
                        </button>

                        <form x-show="showReject"
                              x-transition
                              action="{{ route('staff.reports.reject', $report) }}"
                              method="POST"
                              class="mt-3 space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="rejection-reason" class="block text-xs font-extrabold text-[#D94343] mb-1">
                                    Alasan Penolakan (Ditampilkan Transparan di Lacak Tiket) *
                                </label>
                                <textarea id="rejection-reason"
                                          name="rejection_reason"
                                          rows="2"
                                          required
                                          minlength="10"
                                          placeholder="Jelaskan alasan penolakan secara sopan dan jelas..."
                                          class="w-full p-2.5 rounded-xl border border-[#D94343] text-xs text-[#17324D]"></textarea>
                            </div>
                            <button type="submit"
                                    class="w-full min-h-[42px] px-4 py-2 rounded-xl bg-[#D94343] hover:opacity-90 text-white text-xs font-extrabold cursor-pointer">
                                Konfirmasi Tolak Laporan
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <!-- 3. Status History Timeline -->
            <div class="bg-white rounded-[24px] border border-[#D7E1E5] p-5 sm:p-6">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-[#17324D] mb-4">Riwayat Perubahan Status</h3>
                <ol class="relative border-l-2 border-[#D7E1E5] ml-2.5 space-y-4">
                    @foreach($report->statusHistories as $history)
                        @php
                            $meta = \App\Models\Report::getStatusMetadata($history->to_status);
                        @endphp
                        <li class="ml-4">
                            <span class="absolute -left-[9px] w-4 h-4 rounded-full border-2 border-white"
                                  style="background-color: {{ $meta['color'] }};"></span>
                            <div class="text-xs">
                                <span class="font-extrabold text-[#17324D]">{{ $meta['label'] }}</span>
                                <span class="text-[#617482] font-semibold"> • {{ $history->created_at?->format('d M Y H:i') }}</span>
                                <p class="mt-0.5 text-[#617482] font-semibold">{{ $history->note }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.L && document.getElementById('staff-report-map')) {
            const lat = {{ (float) $report->latitude }};
            const lng = {{ (float) $report->longitude }};
            const map = window.L.map('staff-report-map').setView([lat, lng], 16);
            window.L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
            }).addTo(map);
            window.L.marker([lat, lng]).addTo(map);
        }
    });
</script>
@endsection

