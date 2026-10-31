@extends('layouts.app', ['title' => 'Dashboard Operasional Petugas & Admin — Lapor Tuntas'])

@section('content')
<div class="max-w-[1240px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">

    <!-- Top Staff Banner -->
    <div class="bg-[#17324D] text-white rounded-[24px] p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider bg-[#C5E84A] text-[#17324D]">
                    {{ $user->isAdmin() ? 'Admin / Moderator' : 'Petugas Lapangan' }}
                </span>
                @if($user->agency)
                    <span class="text-xs font-bold text-[#DFF5FC]">• {{ $user->agency->name }}</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Halo, {{ $user->name }}</h1>
            <p class="text-xs sm:text-sm text-[#DFF5FC]/85 font-semibold mt-0.5">
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
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="{{ route('staff.dashboard', ['status' => 'all']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'all' ? 'border-[#17324D] bg-white ring-2 ring-[#17324D]/20' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#617482]">Total Laporan</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['total'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'baru']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'baru' ? 'border-[#16A6D8] bg-[#DFF5FC]/40' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#0D8BB8]">Baru Masuk</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['baru'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'ditugaskan']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'ditugaskan' ? 'border-[#7966D9] bg-[#EFE9FC]/50' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#7966D9]">Ditugaskan</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['ditugaskan'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'diproses']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'diproses' ? 'border-[#F5B82E] bg-[#FEF5DC]/60' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#8A5D00]">Diproses</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['diproses'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'selesai']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'selesai' ? 'border-[#278653] bg-[#E1F5EA]/60' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#278653]">Selesai (Tuntas)</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['selesai'] }}</p>
        </a>
        <a href="{{ route('staff.dashboard', ['status' => 'ditolak']) }}"
           class="p-4 rounded-2xl border {{ $statusFilter === 'ditolak' ? 'border-[#697782] bg-[#E9EEF1]' : 'border-[#D7E1E5] bg-white' }}">
            <p class="text-xs font-bold text-[#697782]">Ditolak</p>
            <p class="mt-1 text-2xl font-extrabold text-[#17324D] tabular-nums">{{ $counts['ditolak'] }}</p>
        </a>
    </div>

    <!-- Filter Bar & Reports Table -->
    <section class="bg-white rounded-[24px] border border-[#D7E1E5] shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-[#D7E1E5] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-[#F3F7F8]/60">
            <h2 class="text-base sm:text-lg font-extrabold text-[#17324D]">Daftar Tugas &amp; Laporan Masuk</h2>

            <form action="{{ route('staff.dashboard') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <select name="status"
                        onchange="this.form.submit()"
                        aria-label="Filter status laporan"
                        class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                    <option value="all" @selected($statusFilter === 'all')>Semua Status</option>
                    <option value="baru" @selected($statusFilter === 'baru')>Baru</option>
                    <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Ditugaskan</option>
                    <option value="diproses" @selected($statusFilter === 'diproses')>Sedang diproses</option>
                    <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                    <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
                </select>

                <select name="category"
                        onchange="this.form.submit()"
                        aria-label="Filter kategori laporan"
                        class="min-h-[42px] px-3 py-2 rounded-xl bg-white border border-[#D7E1E5] text-xs font-extrabold text-[#17324D]">
                    <option value="all" @selected($categoryFilter === 'all')>Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) $categoryFilter === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#D7E1E5] bg-[#F3F7F8] text-[11px] font-extrabold uppercase tracking-wider text-[#617482]">
                        <th class="py-3.5 px-4">ID Tiket</th>
                        <th class="py-3.5 px-4">Masalah &amp; Kategori</th>
                        <th class="py-3.5 px-4">Lokasi</th>
                        <th class="py-3.5 px-4">Instansi / Petugas</th>
                        <th class="py-3.5 px-4">Status &amp; Peta</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#D7E1E5] text-sm">
                    @forelse($reports as $report)
                        <tr class="hover:bg-[#F3F7F8]/60 transition">
                            <td class="py-4 px-4 align-top">
                                <span class="font-mono font-extrabold text-[#17324D] tabular-nums">{{ $report->ticket_id }}</span>
                                <p class="text-[11px] text-[#617482] font-semibold mt-0.5">{{ $report->created_at?->diffForHumans() }}</p>
                            </td>
                            <td class="py-4 px-4 align-top max-w-xs">
                                <span class="text-xs font-extrabold text-[#0D8BB8]">{{ $report->category?->name }}</span>
                                <p class="font-extrabold text-[#17324D] line-clamp-2 mt-0.5">{{ $report->title }}</p>
                            </td>
                            <td class="py-4 px-4 align-top text-xs text-[#617482] font-semibold">
                                <p class="font-bold text-[#17324D]">{{ $report->location_label }}</p>
                                <p class="font-mono tabular-nums mt-0.5">{{ number_format($report->latitude, 5) }}, {{ number_format($report->longitude, 5) }}</p>
                            </td>
                            <td class="py-4 px-4 align-top text-xs">
                                <p class="font-extrabold text-[#17324D]">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
                                @if($report->assignedOfficer)
                                    <p class="text-[#0D8BB8] font-bold mt-0.5">{{ $report->assignedOfficer->name }}</p>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-top">
                                <x-status-badge :status="$report->status" size="sm" />
                                <p class="text-[11px] font-bold mt-1 {{ $report->is_visible_on_map ? 'text-[#0D8BB8]' : 'text-[#617482]' }}">
                                    {{ $report->is_visible_on_map ? '● Tampil di Peta' : '○ Hilang dari Peta' }}
                                </p>
                            </td>
                            <td class="py-4 px-4 align-top text-right">
                                <a href="{{ route('staff.reports.show', $report) }}"
                                   class="min-h-[38px] px-3.5 py-2 rounded-xl bg-[#16A6D8] hover:bg-[#0D8BB8] text-white text-xs font-extrabold inline-flex items-center gap-1 transition">
                                    <span>Tindak Lanjut</span>
                                    <span>→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-sm font-bold text-[#617482]">
                                Tidak ada laporan yang cocok dengan filter terpilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-[#D7E1E5]">
                {{ $reports->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

