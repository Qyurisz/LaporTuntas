@extends('layouts.app', ['title' => 'Tugas Petugas — Lapor Tuntas'])

@section('content')
<div x-data="dashboardTableFilter()" class="max-w-[1180px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <section class="bg-navy text-white rounded-[24px] p-5 sm:p-7 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-[0_8px_24px_rgba(23,50,77,0.12)]">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-lime text-navy">Petugas Lapangan</span>
                @if($user->agency)
                    <span class="text-xs font-bold text-primary-soft">{{ $user->agency->name }} ({{ $user->agency->code }})</span>
                @endif
            </div>
            <h1 class="mt-2 text-xl sm:text-2xl font-extrabold">Tugas Lapangan: {{ $user->name }}</h1>
            <p class="mt-1 text-sm text-primary-soft/85">
                @if($user->agency)
                    Pantau dan laporkan progres penanganan khusus kewenangan {{ $user->agency->name }}.
                @else
                    Pantau tugas lapangan yang ditugaskan kepada Anda, lalu laporkan progres penanganannya.
                @endif
            </p>
        </div>
        <form action="{{ route('staff.logout') }}" method="POST">
            @csrf
            <button type="submit" class="min-h-[44px] px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-extrabold cursor-pointer transition">Keluar</button>
        </form>
    </section>

    <section id="dashboard-stats-grid" @click="handleStatsClick($event)" class="grid grid-cols-2 lg:grid-cols-4 gap-3" aria-label="Ringkasan tugas petugas">
        @foreach([
            ['all', 'Semua Tugas', $counts['total'], 'text-navy'],
            ['ditugaskan', 'Siap Ditangani', $counts['ditugaskan'], 'text-status-verified'],
            ['diproses', 'Sedang Dikerjakan', $counts['diproses'], 'text-warning'],
            ['selesai', 'Sudah Selesai', $counts['selesai'], 'text-success'],
        ] as [$status, $label, $count, $colorClass])
            <a href="{{ route('staff.dashboard', ['status' => $status]) }}" class="rounded-2xl border border-border bg-surface p-4 hover:shadow-xs transition">
                <p class="text-xs font-bold text-text-muted">{{ $label }}</p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums {{ $colorClass }}">{{ $count }}</p>
            </a>
        @endforeach
    </section>

    <!-- Daftar Tugas Saya Table Section (Matches Reference Image) -->
    <section class="bg-surface rounded-2xl sm:rounded-3xl border border-border shadow-[0_4px_14px_rgba(23,50,77,0.06)] overflow-hidden relative">
        <!-- Top Loading Indicator -->
        <div x-show="loading"
             x-transition.opacity
             class="absolute inset-x-0 top-0 h-1 bg-primary/20 overflow-hidden z-20">
            <div class="h-full bg-primary animate-pulse w-full"></div>
        </div>

        <div class="p-4 sm:p-6 border-b border-border flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-surface">
            <div>
                <h2 class="text-xl font-extrabold text-navy tracking-tight">Daftar tugas saya</h2>
                <p class="mt-1 text-xs sm:text-sm text-text-muted">
                    {{ $user->agency?->name ?? 'Dinas Bina Marga & Pekerjaan Umum' }} · Laporan muncul setelah admin menetapkannya kepada Anda.
                </p>
            </div>

            <form x-ref="filterForm"
                  action="{{ route('staff.dashboard') }}"
                  method="GET"
                  @change="applyFilter()"
                  class="flex flex-wrap items-center gap-2.5">
                <!-- Status Filter -->
                <div class="relative">
                    <select name="status"
                            aria-label="Filter status tugas"
                            class="min-h-[38px] pl-3.5 pr-8 py-1.5 rounded-xl bg-surface border border-border text-xs font-bold text-navy appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="all" @selected($statusFilter === 'all')>Semua status</option>
                        <option value="baru" @selected($statusFilter === 'baru')>Belum diproses</option>
                        <option value="ditugaskan" @selected($statusFilter === 'ditugaskan')>Ditugaskan</option>
                        <option value="diproses" @selected($statusFilter === 'diproses')>Sedang diproses</option>
                        <option value="selesai" @selected($statusFilter === 'selesai')>Selesai</option>
                        <option value="ditolak" @selected($statusFilter === 'ditolak')>Ditolak</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-text-muted">
                        <x-icon name="chevron-down" class="w-3.5 h-3.5" stroke-width="2.2" />
                    </div>
                </div>

                @if(! $user->agency_id)
                <!-- Agency Filter (If officer is not tied to one agency) -->
                <div class="relative">
                    <select name="agency"
                            aria-label="Filter dinas tugas"
                            class="min-h-[38px] pl-3.5 pr-8 py-1.5 rounded-xl bg-surface border border-border text-xs font-bold text-navy appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-primary/30">
                        <option value="0" @selected($agencyFilter === 0)>Semua dinas</option>
                        @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected($agencyFilter === $agency->id)>{{ $agency->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-text-muted">
                        <x-icon name="chevron-down" class="w-3.5 h-3.5" stroke-width="2.2" />
                    </div>
                </div>
                @endif
            </form>
        </div>

        <div id="dashboard-table-container"
             class="overflow-x-auto transition-opacity duration-150"
             :class="loading ? 'opacity-50 pointer-events-none' : ''">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-border bg-surface text-caption font-extrabold uppercase tracking-wider text-text-muted">
                        <th class="py-3.5 px-4 sm:px-6">TIKET</th>
                        <th class="py-3.5 px-4 sm:px-6">LAPORAN</th>
                        <th class="py-3.5 px-4 sm:px-6">LOKASI</th>
                        <th class="py-3.5 px-4 sm:px-6">STATUS</th>
                        <th class="py-3.5 px-4 sm:px-6">DUKUNGAN</th>
                        <th class="py-3.5 px-4 sm:px-6 text-left">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border text-sm">
                    @forelse($reports as $report)
                        @php
                            $locParts = array_filter(array_map('trim', explode(',', $report->location_label ?? '')));
                            if (count($locParts) > 1) {
                                $city = array_pop($locParts);
                                $street = implode(', ', $locParts);
                            } else {
                                $street = $report->location_label ?: 'Titik Lokasi Warga';
                                $city = 'Kota Malang';
                            }
                        @endphp
                        <tr class="hover:bg-canvas/50 transition">
                            <!-- TIKET -->
                            <td class="py-4 px-4 sm:px-6 align-middle">
                                <span class="font-mono font-extrabold text-navy text-xs sm:text-sm tabular-nums">{{ $report->ticket_id }}</span>
                                <p class="text-xs text-text-muted font-medium mt-0.5">{{ $report->created_at?->diffForHumans() }}</p>
                            </td>

                            <!-- LAPORAN -->
                            <td class="py-4 px-4 sm:px-6 align-middle max-w-xs">
                                <p class="font-extrabold text-navy text-xs sm:text-sm line-clamp-1">{{ $report->title }}</p>
                                <p class="text-xs text-text-muted font-medium mt-0.5">{{ $report->category?->name }}</p>
                            </td>

                            <!-- LOKASI -->
                            <td class="py-4 px-4 sm:px-6 align-middle text-xs">
                                <p class="font-bold text-navy text-xs sm:text-sm">{{ $street }}</p>
                                <p class="text-xs text-text-muted font-medium mt-0.5">{{ $city }}</p>
                            </td>

                            <!-- STATUS -->
                            <td class="py-4 px-4 sm:px-6 align-middle">
                                @if($report->status === 'selesai')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-success-soft text-success">
                                        <x-icon name="check-circle" class="w-3.5 h-3.5" stroke-width="2.2" />
                                        <span>Selesai</span>
                                    </span>
                                @elseif($report->status === 'ditugaskan')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#F0EBFA] text-[#6B46C1]">
                                        <x-icon name="clock" class="w-3.5 h-3.5" stroke-width="2.2" />
                                        <span>Ditugaskan</span>
                                    </span>
                                @elseif($report->status === 'diproses')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-warning-soft text-warning">
                                        <x-icon name="clock" class="w-3.5 h-3.5" stroke-width="2.2" />
                                        <span>Diproses</span>
                                    </span>
                                @elseif($report->status === 'ditolak')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ECEFF3] text-[#475569]">
                                        <x-icon name="x-circle" class="w-3.5 h-3.5" stroke-width="2.2" />
                                        <span>Ditolak</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#E1F3FB] text-[#0A7B9D]">
                                        <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/>
                                            <circle cx="12" cy="12" r="2"/>
                                        </svg>
                                        <span>Belum diproses</span>
                                    </span>
                                @endif
                            </td>

                            <!-- DUKUNGAN -->
                            <td class="py-4 px-4 sm:px-6 align-middle">
                                @if($report->supports_count > 0)
                                    <div class="inline-flex items-center gap-1.5 text-xs font-bold text-[#8A4500]">
                                        <x-icon name="thumbs-up" class="w-3.5 h-3.5 shrink-0" stroke-width="2.2" />
                                        <span class="tabular-nums">{{ $report->supports_count }}</span>
                                    </div>
                                @else
                                    <span class="text-text-muted font-medium text-xs">-</span>
                                @endif
                            </td>

                            <!-- AKSI -->
                            <td class="py-4 px-4 sm:px-6 align-middle text-left whitespace-nowrap">
                                @if($report->status === 'selesai' || $report->status === 'ditolak')
                                    <a href="{{ route('staff.reports.show', $report) }}"
                                       class="min-h-[38px] w-[130px] px-3.5 py-2 rounded-xl bg-surface hover:bg-canvas border border-primary/35 text-primary-hover text-xs font-extrabold inline-flex items-center justify-center whitespace-nowrap text-center transition active:scale-[0.99]">
                                        Lihat detail
                                    </a>
                                @else
                                    <a href="{{ route('staff.reports.show', $report) }}"
                                       class="min-h-[38px] w-[130px] px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-extrabold inline-flex items-center justify-center whitespace-nowrap text-center transition active:scale-[0.99] shadow-xs">
                                        Perbarui progres
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-sm font-bold text-text-muted">
                                Belum ada tugas yang cocok dengan filter terpilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="dashboard-pagination-container" @click="handlePaginationClick($event)">
            @if($reports->hasPages())
                <div class="p-4 sm:p-5 border-t border-border bg-surface">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
