@extends('layouts.app', ['title' => 'Laporan ' . $report->ticket_id . ' — Lapor Tuntas'])

@section('content')
<div class="max-w-[1160px] w-full mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <a href="{{ route('tickets.show', ['ticketId' => $report->ticket_id]) }}" class="text-xs font-extrabold text-[#0D8BB8] hover:underline">← Kembali ke tiket publik</a>
    <section class="bg-white rounded-[24px] border border-[#D7E1E5] p-5 sm:p-6 space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="px-3 py-1 rounded-lg bg-[#17324D] text-white font-mono font-extrabold text-sm">{{ $report->ticket_id }}</span>
            <x-status-badge :status="$report->status" />
        </div>
        <h1 class="text-2xl font-extrabold text-[#17324D]">{{ $report->title }}</h1>
        <p class="text-sm text-[#617482]">{{ $report->location_label }}</p>
        <p class="text-sm whitespace-pre-line">{{ $report->description }}</p>
        <p class="text-sm font-bold">Instansi: {{ $report->agency?->name ?? 'Belum ditugaskan' }}</p>
    </section>
</div>
@endsection
