@extends('layouts.app', ['title' => 'Dashboard Admin — Lapor Tuntas'])

@section('content')
<div class="max-w-[1240px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

    <!-- Top Staff Banner -->
    <div class="bg-navy text-white rounded-[24px] p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-lime text-navy">
                    Administrator
                </span>
                @if($user->agency)
                    <span class="text-xs font-bold text-primary-soft">{{ $user->agency->name }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Halo, {{ $user->name }}</h1>
            <p class="text-xs sm:text-sm text-primary-soft/85 font-semibold mt-0.5">
                Tinjau laporan warga, verifikasi instansi penanggung jawab, dan tetapkan petugas lapangan pelaksana.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.reports.export') }}"
               class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs sm:text-sm font-extrabold inline-flex items-center gap-2 transition cursor-pointer"
               title="Ekspor rekap laporan ke Excel/CSV">
                <x-icon name="download" class="w-4 h-4" />
                <span>Unduh CSV</span>
            </a>

            <form action="{{ route('staff.logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs sm:text-sm font-extrabold transition cursor-pointer">
                    Keluar Sesi
                </button>
            </form>
        </div>
    </div>

    <!-- Admin assignment workflow summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ route('admin.dashboard', ['assignment' => 'pending']) }}"
           class="p-4 rounded-2xl border border-primary/30 bg-primary-soft/40 hover:shadow-xs transition">
            <p class="text-xs font-bold text-primary-hover">Menunggu Penugasan</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['menunggu_penugasan'] }}</p>
        </a>
        <a href="{{ route('admin.dashboard', ['assignment' => 'assigned']) }}"
           class="p-4 rounded-2xl border border-status-verified/30 bg-status-verified/10 hover:shadow-xs transition">
            <p class="text-xs font-bold text-status-verified">Sudah Ditugaskan</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['ditugaskan'] + $counts['diproses'] }}</p>
        </a>
        <a href="{{ route('admin.dashboard', ['status' => 'diproses']) }}"
           class="p-4 rounded-2xl border border-status-processing/40 bg-warning-soft/70 hover:shadow-xs transition">
            <p class="text-xs font-bold text-warning">Sedang Dikerjakan</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['diproses'] }}</p>
        </a>
        <a href="{{ route('admin.dashboard', ['status' => 'selesai']) }}"
           class="p-4 rounded-2xl border border-success/30 bg-success-soft/70 hover:shadow-xs transition">
            <p class="text-xs font-bold text-success">Laporan Selesai</p>
            <p class="mt-1 text-2xl font-extrabold text-navy tabular-nums">{{ $counts['selesai'] }}</p>
        </a>
    </div>

    <!-- Filter Bar & Reports Table -->
    <section class="bg-surface rounded-[24px] border border-border shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-canvas/60">
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-navy">Antrean Penugasan Dinas</h2>
                <p class="mt-0.5 text-xs text-text-muted">Pilih dinas dan petugas pelaksana untuk segera memproses laporan di lapangan.</p>
            </div>

            <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <select name="status"
                        onchange="this.form.submit()"
                        aria-label="Filter status laporan"
                        class="min-h-[42px] px-3 py-2 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                    <option value="baru" @selected($statusFilter === 'baru')>Baru</option>
                    <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Ditugaskan</option>
                    <option value="diproses" @selected($statusFilter === 'diproses')>Sedang Diproses</option>
                    <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                    <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
                </select>

                <select name="category"
                        onchange="this.form.submit()"
                        aria-label="Filter kategori laporan"
                        class="min-h-[42px] px-3 py-2 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="all" @selected($categoryFilter === 'all')>Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) $categoryFilter === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>

                <select name="assignment"
                        onchange="this.form.submit()"
                        aria-label="Filter penugasan"
                        class="min-h-[42px] px-3 py-2 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="all" @selected($assignmentFilter === 'all')>Semua Penugasan</option>
                    <option value="pending" @selected($assignmentFilter === 'pending')>Menunggu Penugasan</option>
                    <option value="assigned" @selected($assignmentFilter === 'assigned')>Sudah Ditugaskan</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-border bg-canvas text-caption font-extrabold uppercase tracking-wider text-text-muted">
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
                                <p class="text-caption text-text-muted font-semibold mt-0.5">{{ $report->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="text-xs font-extrabold text-primary-hover">{{ $report->category?->name }}</span>
                                <p class="font-extrabold text-navy line-clamp-2 mt-0.5">{{ $report->title }}</p>
                            </td>
                            <td class="py-4 px-4 align-top text-xs text-text-muted font-semibold">
                                <p class="font-bold text-navy">{{ $report->location_label }}</p>
                                <p class="font-mono tabular-nums mt-0.5">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
                            </td>
                            <td class="py-4 px-4 align-top text-xs">
                                @if($report->assignedOfficer)
                                    <p class="font-extrabold text-navy">{{ $report->agency?->name }}</p>
                                    <p class="text-primary-hover font-bold mt-0.5">{{ $report->assignedOfficer->name }}</p>
                                @else
                                    <p class="font-extrabold text-navy">Belum ada petugas</p>
                                    <p class="mt-0.5 text-text-muted">Saran dinas: {{ $report->category?->defaultAgency?->name ?? 'pilih dinas terkait' }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-top">
                                <x-status-badge :status="$report->status" size="sm" />
                                <p class="text-caption font-bold mt-1 {{ $report->is_visible_on_map ? 'text-primary-hover' : 'text-text-muted' }}">
                                    {{ $report->is_visible_on_map ? 'Tampil di peta' : 'Tidak tampil di peta' }}
                                </p>
                            </td>
                            <td class="py-4 px-4 align-top text-right">
                                <a href="{{ route('admin.reports.show', $report) }}"
                                   class="min-h-[38px] px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-extrabold inline-flex items-center gap-1.5 transition active:scale-[0.99]">
                                    <span>Atur Penugasan</span>
                                    <x-icon name="arrow-right" class="w-4 h-4" />
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
