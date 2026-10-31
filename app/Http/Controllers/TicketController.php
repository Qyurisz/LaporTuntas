<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display the ticket search page or redirect if a ticket ID is provided in query string.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $rawTicketId = trim((string) $request->query('ticket_id', ''));

        if ($rawTicketId !== '') {
            $normalized = strtoupper($rawTicketId);

            if (! preg_match('/^LT-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $normalized)) {
                return view('tickets.track', [
                    'searchedId' => $rawTicketId,
                    'report' => null,
                    'errorMessage' => 'Format ID tiket tidak sesuai. Gunakan format seperti LT-7K9P-2M4X.',
                ]);
            }

            return redirect()->route('tickets.show', ['ticketId' => $normalized]);
        }

        return view('tickets.track', [
            'searchedId' => '',
            'report' => null,
            'errorMessage' => null,
        ]);
    }

    /**
     * Display a specific report by its unique ticket ID, including completed/closed reports.
     */
    public function show(string $ticketId): View
    {
        $normalized = strtoupper(trim($ticketId));

        if (! preg_match('/^LT-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $normalized)) {
            return view('tickets.track', [
                'searchedId' => $ticketId,
                'report' => null,
                'errorMessage' => 'Format ID tiket tidak sesuai. Pastikan menggunakan format seperti LT-7K9P-2M4X.',
            ]);
        }

        $report = Report::query()
            ->with(['category', 'agency', 'assignedOfficer', 'media', 'statusHistories.actor'])
            ->where('ticket_id', $normalized)
            ->first();

        if (! $report) {
            return view('tickets.track', [
                'searchedId' => $normalized,
                'report' => null,
                'errorMessage' => 'ID tiket belum ditemukan. Periksa kembali karakter ID yang kamu masukkan.',
            ]);
        }

        return view('tickets.track', [
            'searchedId' => $normalized,
            'report' => $report,
            'errorMessage' => null,
        ]);
    }
}

