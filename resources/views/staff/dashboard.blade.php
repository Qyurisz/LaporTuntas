@extends('layouts.app', ['title' => 'Tugas Petugas — Lapor Tuntas'])

@section('content')
<div class="max-w-[1180px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <section class="bg-[#17324D] text-white rounded-[24px] p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#C5E84A] text-[#17324D]">Petugas Lapangan</span>
                @if($user->agency)
                    <span class="text-xs font-bold text-[#DFF5FC]">{{ $user->agency->name }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Tugas lapangan, {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-[#DFF5FC]/85">Pantau tugas lintas dinas, pilih dinas yang ingin dilihat, lalu laporkan progres penanganannya.</p>
        </div>
        <form action="{{ route('staff.logout') }}" method="POST">
            @csrf
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-extrabold">Keluar</button>
        </form>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3" aria-label="Ringkasan tugas petugas">
        @foreach([
            ['all', 'Semua tugas', $counts['total'], '#17324D'],
            ['ditugaskan', 'Siap ditangani', $counts['ditugaskan'], '#7966D9'],
            ['diproses', 'Sedang dikerjakan', $counts['diproses'], '#B77900'],
            ['selesai', 'Sudah selesai', $counts['selesai'], '#278653'],
        ] as [$status, $label, $count, $color])
            <a href="{{ route('staff.dashboard', ['status' => $status]) }}" class="rounded-2xl border border-[#D7E1E5] bg-white p-4 hover:shadow-sm transition">
                <p class="text-xs font-bold text-[#617482]">{{ $label }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums" style="color: {{ $color }}">{{ $count }}</p>
            </a>
        @endforeach
    </section>

    <section class="bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-[#D7E1E5] bg-[#F3F7F8]/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-extrabold text-[#17324D]">Daftar tugas saya</h2>
                <p class="mt-0.5 text-xs text-[#617482]">Laporan hanya muncul setelah admin menetapkannya kepada Anda.</p>
            </div>
            <form action="{{ route('staff.dashboard') }}" method="GET" class="flex flex-wrap gap-2">
                <select name="status" onchange="this.form.submit()" aria-label="Filter status tugas" class="min-h-[42px] px-3 rounded-xl bg-white border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                    <option value="all" @selected($statusFilter === 'all')>Semua status</option>
                    <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Siap ditangani</option>
                    <option value="diproses" @selected($statusFilter === 'diproses')>Sedang dikerjakan</option>
                    <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                </select>
                <select name="category" onchange="this.form.submit()" aria-label="Filter kategori tugas" class="min-h-[42px] px-3 rounded-xl bg-white border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                    <option value="all" @selected($categoryFilter === 'all')>Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $categoryFilter === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="agency" onchange="this.form.submit()" aria-label="Filter dinas tugas" class="min-h-[42px] px-3 rounded-xl bg-white border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                    <option value="0" @selected($agencyFilter === 0)>Semua dinas</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected($agencyFilter === $agency->id)>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="p-4 sm:p-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($reports as $report)
                <article class="rounded-2xl border border-[#D7E1E5] p-4 sm:p-5 flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-mono font-extrabold text-[#0D8BB8]">{{ $report->ticket_id }}</span>
                            <h3 class="mt-1 font-extrabold text-[#17324D]">{{ $report->title }}</h3>
                            <p class="mt-1 text-xs font-semibold text-[#617482]">{{ $report->category?->name }} · {{ $report->created_at?->diffForHumans() }}</p>
                        </div>
                        <x-status-badge :status="$report->status" size="sm" />
                    </div>
                    <div class="rounded-xl bg-[#F3F7F8] p-3 text-xs">
                        <p class="font-extrabold text-[#17324D]">{{ $report->location_label }}</p>
                        <p class="mt-1 text-[#617482]">{{ $report->agency?->name ?? $user->agency?->name }}</p>
                    </div>
                    <a href="{{ route('staff.reports.show', $report) }}" class="min-h-[42px] px-4 py-2 rounded-xl bg-[#17324D] hover:bg-[#0D8BB8] text-white text-xs font-extrabold inline-flex items-center justify-center gap-2">
                        {{ $report->status === 'selesai' ? 'Lihat laporan selesai' : 'Buka dan perbarui progres' }}
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6"/>
                        </svg>
                    </a>
                </article>
            @empty
                <div class="lg:col-span-2 py-12 text-center">
                    <p class="text-sm font-extrabold text-[#17324D]">Belum ada tugas untuk Anda</p>
                    <p class="mt-1 text-xs text-[#617482]">Tugas baru akan muncul setelah admin menetapkan laporan kepada Anda.</p>
                </div>
            @endforelse
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-[#D7E1E5]">{{ $reports->links() }}</div>
        @endif
    </section>
</div>
@endsection
