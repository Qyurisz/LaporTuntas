@extends('layouts.app', ['title' => 'Laporan ' . $report->ticket_id . ' — Lapor Tuntas'])

@section('content')
<div class="max-w-[1160px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <a href="{{ route('tickets.show', ['ticketId' => $report->ticket_id]) }}"
       class="inline-flex items-center gap-1.5 text-xs font-extrabold text-primary-hover hover:underline">
        <x-icon name="arrow-left" class="w-3.5 h-3.5" />
        <span>Kembali ke Lacak Tiket Publik</span>
    </a>

    <section class="bg-surface rounded-[24px] border border-border p-5 sm:p-6 space-y-4 shadow-[0_4px_14px_rgba(23,50,77,0.06)]">
        <div class="flex flex-wrap items-center gap-3">
            <span class="px-3 py-1 rounded-xl bg-navy text-white font-mono font-extrabold text-sm">{{ $report->ticket_id }}</span>
            <x-status-badge :status="$report->status" />
        </div>
        <h1 class="text-2xl font-extrabold text-navy">{{ $report->title }}</h1>
        <p class="text-sm font-semibold text-text-muted">{{ $report->location_label }}</p>
        <p class="text-sm text-navy whitespace-pre-line leading-relaxed">{{ $report->description }}</p>
        <div class="pt-3 border-t border-border-soft flex items-center justify-between text-xs text-text-muted font-bold">
            <span>Instansi: <strong class="text-navy">{{ $report->agency?->name ?? 'Belum ditugaskan' }}</strong></span>
            @if($report->assignedOfficer)
                <span>Petugas: <strong class="text-primary-hover">{{ $report->assignedOfficer->name }}</strong></span>
            @endif
        </div>
    </section>
</div>
@endsection
