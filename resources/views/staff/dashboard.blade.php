@extends('layouts.app', ['title' => 'Dashboard Operasional Petugas & Admin — Lapor Tuntas'])

@section('content')
<div class="max-w-[1240px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

    <!-- Top Staff Banner -->
    <div class="bg-navy text-white rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-lime text-navy">
                    {{ $user->isAdmin() ? 'Admin / Moderator' : 'Petugas Lapangan' }}
                </span>
                @if($user->agency)
                    <span class="text-xs font-bold text-primary-soft">• {{ $user->agency->name }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Halo, {{ $user->name }}</h1>
            <p class="text-xs sm:text-sm text-primary-soft/85 font-semibold mt-0.5">
                Kelola penugasan, ubah status penanganan, dan unggah foto bukti perbaikan hingga tuntas.
            </p>
        </div>

        <form action="{{ route('staff.logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs sm:text-sm font-extrabold transition cursor-pointer">
                Keluar Sesi
            </button>
        </form>
    </div>

    <!-- Metric Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <a href="{{ route('staff.dashboard', ['status' => 'all']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'all' ? 'border-navy bg-surface ring-2 ring-navy/20' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-text-muted">Total Laporan</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['total'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'baru']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'baru' ? 'border-primary bg-primary-soft/40' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-primary-hover">Baru Masuk</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['baru'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'ditugaskan']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'ditugaskan' ? 'border-[#7966D9] bg-[#EFE9FC]/50' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-[#7966D9]">Ditugaskan</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['ditugaskan'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'diproses']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'diproses' ? 'border-[#F5B82E] bg-warning-soft/60' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-warning">Diproses</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['diproses'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'selesai']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'selesai' ? 'border-[#278653] bg-success-soft/60' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-success">Selesai (Tuntas)</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['selesai'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'overdue']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'overdue' ? 'border-[#E02424] bg-[#FDE8E8]' : 'border-border bg-surface hover:border-[#F98080]' }} transition">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-[#E02424]">Terlambat SLA</p>
                @if($counts['overdue'] > 0)
                    <span class="inline-flex w-2 h-2 rounded-full bg-[#E02424] animate-ping"></span>
                @endif
            </div>
            <p class="mt-1 text-2xl font-extrabold text-danger tabular-nums">{{ $counts['overdue'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'ditolak']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'ditolak' ? 'border-[#697782] bg-[#E9EEF1]' : 'border-border bg-surface' }}">
            <p class="text-xs font-bold text-[#697782]">Ditolak</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['ditolak'] }}</p>
        </a>
    </div>

    <!-- Filter Bar & Reports Table -->
    <section class="bg-surface rounded-2xl border border-border shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-canvas/60">
            <h2 class="text-base sm:text-lg font-extrabold text-navy">Daftar Tugas &amp; Laporan Masuk</h2>

            <div class="flex flex-wrap items-center gap-2">
                <form action="{{ route('staff.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <select name="status"
                            onchange="this.form.submit()"
                            aria-label="Filter status laporan"
                            class="min-h-[40px] px-3 py-2 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy">
                        <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                        <option value="baru" @selected($statusFilter === 'baru')>Baru</option>
                        <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Ditugaskan</option>
                        <option value="diproses" @selected($statusFilter === 'diproses')>Sedang diproses</option>
                        <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                        <option value="overdue" @selected($statusFilter === 'overdue')>Terlambat (Overdue SLA)</option>
                        <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
                    </select>

                    <select name="category"
                            onchange="this.form.submit()"
                            aria-label="Filter kategori laporan"
                            class="min-h-[40px] px-3 py-2 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy">
                        <option value="all" @selected($categoryFilter === 'all')>Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((int) $categoryFilter === $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('staff.reports.export', request()->only(['status', 'category'])) }}"
                   class="min-h-[40px] px-4 py-2 rounded-xl bg-[#278653] hover:bg-[#1f6b42] text-white text-xs font-extrabold inline-flex items-center gap-1.5 shadow-xs transition cursor-pointer"
                   title="Unduh rekap laporan sesuai filter aktif (Excel / CSV)">
                    <x-icon name="download" class="w-4 h-4 shrink-0" stroke-width="2.2" />
                    <span>Ekspor CSV</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-border bg-canvas text-[11px] font-extrabold uppercase tracking-wider text-text-muted">
                        <th class="py-3.5 px-4">ID Tiket</th>
                        <th class="py-3.5 px-4">Masalah &amp; Kategori</th>
                        <th class="py-3.5 px-4">Lokasi</th>
                        <th class="py-3.5 px-4">Instansi / Petugas</th>
                        <th class="py-3.5 px-4">Status &amp; Peta</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border text-sm">
                    @forelse($reports as $report)
                        <tr class="hover:bg-canvas/60 transition">
                            <td class="py-4 px-4 align-top">
                                <span class="font-mono font-extrabold text-navy tabular-nums">{{ $report->ticket_id }}</span>
                                <p class="text-[11px] text-text-muted font-semibold mt-0.5">{{ $report->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="text-xs font-extrabold text-primary-hover">{{ $report->category?->name }}</span>
                                <p class="font-extrabold text-navy line-clamp-2 mt-0.5">{{ $report->title }}</p>
                                @if($report->supports_count > 0)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-warning-soft text-warning border border-warning/30"
                                              title="{{ $report->supports_count }} warga juga melaporkan/mengalami masalah ini">
                                            <x-icon name="thumbs-up" class="w-3 h-3 shrink-0" stroke-width="2.2" />
                                            <span>{{ $report->supports_count }} Dukungan</span>
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-top text-xs text-text-muted font-semibold">
                                <p class="font-bold text-navy">{{ $report->location_label }}</p>
                                <p class="font-mono tabular-nums mt-0.5">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
                            </td>
                            <td class="py-4 px-4 align-top text-xs">
                                <p class="font-extrabold text-navy">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
                                @if($report->assignedOfficer)
                                    <p class="text-primary-hover font-bold mt-0.5">{{ $report->assignedOfficer->name }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-top">
                                <x-status-badge :status="$report->status" size="sm" />
                                <p class="text-[11px] font-bold mt-1">
                                    @if($report->isVisibleOnPublicMap())
                                        <span class="inline-flex items-center gap-1.5 text-primary-hover">
                                            <span class="inline-block w-2 h-2 rounded-full bg-primary-hover"></span>
                                            <span>Tampil di Peta</span>
                                        </span>
                                    @elseif($report->status === \App\Models\Report::STATUS_SELESAI)
                                        <span class="inline-flex items-center gap-1.5 text-text-muted">
                                            <span class="inline-block w-2 h-2 rounded-full border border-current"></span>
                                            <span>Diarsipkan (> 7 hari)</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-text-muted">
                                            <span class="inline-block w-2 h-2 rounded-full border border-current"></span>
                                            <span>Hilang dari Peta</span>
                                        </span>
                                    @endif
                                </p>
                                @php
                                    $sla = $report->getSlaMeta();
                                @endphp
                                <div class="mt-1">
                                    @if($sla['is_overdue'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-[#FDE8E8] text-danger border border-danger/30"
                                              title="Target SLA: {{ $sla['target_label'] }} (Batas: {{ $sla['deadline_label'] }})">
                                            <x-icon name="alert-triangle" class="w-3 h-3 shrink-0 text-danger" stroke-width="2.2" />
                                            <span>{{ $sla['badge_label'] }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-canvas text-text-muted"
                                              title="Target SLA: {{ $sla['target_label'] }} (Batas: {{ $sla['deadline_label'] }})">
                                            <x-icon name="clock" class="w-3 h-3 shrink-0 text-text-muted" stroke-width="2" />
                                            <span>{{ $sla['badge_label'] }}</span>
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 align-top text-right">
                                <a href="{{ route('staff.reports.show', $report) }}"
                                   class="min-h-[40px] px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-extrabold inline-flex items-center gap-1.5 transition">
                                    <span>Tindak Lanjut</span>
                                    <x-icon name="arrow-right" class="w-3.5 h-3.5" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm font-bold text-text-muted">
                                Tidak ada laporan yang cocok dengan filter terpilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-border">
                {{ $reports->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

