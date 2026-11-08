@extends('layouts.app', ['title' => 'Tugas Petugas — Lapor Tuntas'])

@section('content')
<div class="max-w-[1180px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <section class="bg-navy text-white rounded-[24px] p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-lime text-navy">Petugas Lapangan</span>
                @if($user->agency)
                    <span class="text-xs font-bold text-primary-soft">{{ $user->agency->name }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Tugas lapangan, {{ $user->name }}</h1>
            <p class="mt-1 text-sm text-primary-soft/85">Pantau tugas lintas dinas, pilih dinas yang ingin dilihat, lalu laporkan progres penanganannya.</p>
        </div>
        <form action="{{ route('staff.logout') }}" method="POST">
            @csrf
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-extrabold cursor-pointer transition">Keluar</button>
        </form>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3" aria-label="Ringkasan tugas petugas">
        @foreach([
            ['all', 'Semua tugas', $counts['total'], '#17324D'],
            ['ditugaskan', 'Siap ditangani', $counts['ditugaskan'], '#7966D9'],
            ['diproses', 'Sedang dikerjakan', $counts['diproses'], '#B77900'],
            ['selesai', 'Sudah selesai', $counts['selesai'], '#278653'],
        ] as [$status, $label, $count, $color])
            <a href="{{ route('staff.dashboard', ['status' => $status]) }}" class="rounded-2xl border border-border bg-surface p-4 hover:shadow-xs transition">
                <p class="text-xs font-bold text-text-muted">{{ $label }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums" style="color: {{ $color }}">{{ $count }}</p>
            </a>
        @endforeach
    </section>

    <section class="bg-surface rounded-[24px] border border-border shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-border bg-canvas/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-navy">Daftar tugas saya</h2>
                <p class="mt-0.5 text-xs text-text-muted">Laporan hanya muncul setelah admin menetapkannya kepada Anda.</p>
            </div>
            <form action="{{ route('staff.dashboard') }}" method="GET" class="flex flex-wrap gap-2">
                <select name="status" onchange="this.form.submit()" aria-label="Filter status tugas" class="min-h-[42px] px-3 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy">
                    <option value="all" @selected($statusFilter === 'all')>Semua status</option>
                    <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Siap ditangani</option>
                    <option value="diproses" @selected($statusFilter === 'diproses')>Sedang dikerjakan</option>
                    <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                </select>
                <select name="category" onchange="this.form.submit()" aria-label="Filter kategori tugas" class="min-h-[42px] px-3 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy">
                    <option value="all" @selected($categoryFilter === 'all')>Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $categoryFilter === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="agency" onchange="this.form.submit()" aria-label="Filter dinas tugas" class="min-h-[42px] px-3 rounded-xl bg-surface border border-border text-xs font-extrabold text-navy">
                    <option value="0" @selected($agencyFilter === 0)>Semua dinas</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected($agencyFilter === $agency->id)>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($reports as $report)
                <article class="rounded-2xl border border-border bg-surface p-4 sm:p-5 flex flex-col gap-4 shadow-2xs">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-mono font-extrabold text-primary-hover">{{ $report->ticket_id }}</span>
                            <h3 class="mt-1 font-extrabold text-navy">{{ $report->title }}</h3>
                            <p class="mt-1 text-xs font-semibold text-text-muted">{{ $report->category?->name }} · {{ $report->created_at?->diffForHumans() }}</p>
                            @if($report->supports_count > 0)
                                <div class="mt-1.5">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-warning-soft text-warning border border-warning/30">
                                        <x-icon name="thumbs-up" class="w-3 h-3 shrink-0" stroke-width="2.2" />
                                        <span>{{ $report->supports_count }} Dukungan Warga</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                        <x-status-badge :status="$report->status" size="sm" />
                    </div>
                    <div class="rounded-xl bg-canvas p-3 text-xs">
                        <p class="font-extrabold text-navy">{{ $report->location_label }}</p>
                        <p class="mt-1 text-text-muted">{{ $report->agency?->name ?? $user->agency?->name }}</p>
                    </div>
                    <a href="{{ route('staff.reports.show', $report) }}" class="min-h-[42px] px-4 py-2 rounded-xl bg-navy hover:bg-primary text-white text-xs font-extrabold inline-flex items-center justify-center gap-2 transition">
                        {{ $report->status === 'selesai' ? 'Lihat laporan selesai' : 'Buka dan perbarui progres' }}
                        <x-icon name="arrow-right" class="w-4 h-4 text-white" stroke-width="2.5" />
                    </a>
                </article>
            @empty
                <div class="lg:col-span-2 py-12 text-center">
                    <p class="text-sm font-extrabold text-navy">Belum ada tugas untuk Anda</p>
                    <p class="mt-1 text-xs text-text-muted">Tugas baru akan muncul setelah admin menetapkan laporan kepada Anda.</p>
                </div>
            @endforelse
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-border">{{ $reports->links() }}</div>
        @endif
    </section>
</div>
@endsection
