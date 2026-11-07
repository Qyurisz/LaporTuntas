<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportMedia;
use App\Models\StatusHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Store a new citizen report without requiring authentication.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(Auth::user()?->isAdmin() || Auth::user()?->isPetugas(), 403, 'Akun internal tidak dapat membuat laporan warga.');

        $validated = $request->validate(
            [
                'category_id' => ['required', 'exists:categories,id'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'location_label' => ['nullable', 'string', 'max:160'],
                'description' => ['required', 'string', 'min:10', 'max:1000'],
                'photos' => ['required', 'array', 'min:1', 'max:5'],
                'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            [
                'category_id.required' => 'Pilih salah satu kategori masalah.',
                'latitude.required' => 'Izin lokasi GPS wajib aktif untuk mengirim laporan.',
                'longitude.required' => 'Izin lokasi GPS wajib aktif untuk mengirim laporan.',
                'description.required' => 'Tuliskan deskripsi singkat kondisi masalah di lapangan.',
                'description.min' => 'Deskripsi minimal 10 karakter agar petugas mudah memahami masalah.',
                'photos.required' => 'Unggah minimal 1 foto bukti kondisi masalah.',
                'photos.max' => 'Maksimal 5 foto untuk setiap laporan.',
                'photos.*.image' => 'File yang diunggah harus berupa gambar (JPG, PNG, atau WebP).',
                'photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
            ]
        );

        $category = Category::query()->findOrFail($validated['category_id']);

        $report = DB::transaction(function () use ($request, $validated, $category): Report {
            $ticketId = Report::generateTicketId();
            $shortDesc = Str::limit(trim($validated['description']), 68, '');
            $title = "{$category->name}: {$shortDesc}";

            $report = Report::query()->create([
                'ticket_id' => $ticketId,
                'category_id' => $category->id,
                'agency_id' => $category->default_agency_id,
                'title' => $title,
                'description' => trim($validated['description']),
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
                'location_label' => ! empty($validated['location_label'])
                    ? trim($validated['location_label'])
                    : 'Koordinat GPS Warga ('.number_format((float) $validated['latitude'], 5).', '.number_format((float) $validated['longitude'], 5).')',
                'status' => Report::STATUS_BARU,
                'is_visible_on_map' => true,
                'reporter_ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
            ]);

            foreach ($request->file('photos', []) as $photoFile) {
                $storedPath = $photoFile->store('reports/before', 'public');

                ReportMedia::query()->create([
                    'report_id' => $report->id,
                    'type' => 'before',
                    'file_path' => $storedPath,
                    'mime_type' => $photoFile->getClientMimeType(),
                    'file_size' => $photoFile->getSize(),
                ]);
            }

            StatusHistory::query()->create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => Report::STATUS_BARU,
                'note' => 'Laporan berhasil dikirim warga dan menunggu verifikasi penugasan.',
                'changed_by' => null,
                'created_at' => now(),
            ]);

            return $report;
        });

        return redirect()
            ->route('reports.success', ['ticketId' => $report->ticket_id])
            ->with('success', 'Laporan berhasil dikirim. Simpan ID tiket untuk melacaknya.');
    }

    /**
     * Show the ticket confirmation and download screen after submitting a report.
     */
    public function success(string $ticketId): View
    {
        $report = Report::query()
            ->with(['category', 'agency', 'media'])
            ->where('ticket_id', strtoupper(trim($ticketId)))
            ->firstOrFail();

        return view('reports.ticket-success', [
            'report' => $report,
            'statusMeta' => $report->statusMeta(),
        ]);
    }
}

