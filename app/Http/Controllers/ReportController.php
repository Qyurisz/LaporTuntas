<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportMedia;
use App\Models\ReportSupport;
use App\Models\StatusHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Store a new citizen report without requiring authentication.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'category_id' => ['required', 'exists:categories,id'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'location_label' => ['nullable', 'string', 'max:160'],
                'description' => ['required', 'string', 'min:10', 'max:1000'],
                'device_token' => ['nullable', 'string', 'max:64'],
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

        $lat = (float) $validated['latitude'];
        $lng = (float) $validated['longitude'];

        // Check for active duplicate reports with identical category within 30 meters
        $nearbyDuplicate = $this->findNearbyActiveDuplicate((int) $validated['category_id'], $lat, $lng, 30.0);

        if ($nearbyDuplicate !== null) {
            return $this->mergeIntoExistingReport($request, $nearbyDuplicate, $validated);
        }

        $category = Category::query()->findOrFail($validated['category_id']);

        $report = DB::transaction(function () use ($request, $validated, $category, $lat, $lng): Report {
            $ticketId = Report::generateTicketId();
            $shortDesc = Str::limit(trim($validated['description']), 68, '');
            $title = "{$category->name}: {$shortDesc}";

            $report = Report::query()->create([
                'ticket_id' => $ticketId,
                'category_id' => $category->id,
                'agency_id' => $category->default_agency_id,
                'title' => $title,
                'description' => trim($validated['description']),
                'latitude' => $lat,
                'longitude' => $lng,
                'location_label' => ! empty($validated['location_label'])
                    ? trim($validated['location_label'])
                    : 'Koordinat GPS Warga ('.number_format($lat, 5).', '.number_format($lng, 5).')',
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

        $deviceToken = $validated['device_token'] ?? $request->cookie('lt_device_token') ?? Str::uuid()->toString();

        return redirect()
            ->route('reports.success', ['ticketId' => $report->ticket_id])
            ->withCookie(cookie('lt_device_token', $deviceToken, 525600))
            ->with('success', 'Laporan berhasil dikirim. Simpan ID tiket untuk melacaknya.');
    }

    /**
     * Find an active report of the same category within the given radius (meters).
     */
    private function findNearbyActiveDuplicate(int $categoryId, float $lat, float $lng, float $radiusMeters): ?Report
    {
        $candidates = Report::query()
            ->where('category_id', $categoryId)
            ->whereIn('status', [Report::STATUS_BARU, Report::STATUS_DITUGASKAN, Report::STATUS_DIPROSES])
            ->withinBoundingBox($lat, $lng, (int) ceil($radiusMeters + 10))
            ->get();

        foreach ($candidates as $candidate) {
            $dist = $this->calculateDistanceMeters($lat, $lng, (float) $candidate->latitude, (float) $candidate->longitude);
            if ($dist <= $radiusMeters) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Calculate spherical distance between two coordinate pairs in meters.
     */
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Merge a duplicate submission into an existing report as citizen support with evidence.
     *
     * @param array<string, mixed> $validated
     */
    private function mergeIntoExistingReport(Request $request, Report $existingReport, array $validated): RedirectResponse
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $ipHash = hash('sha256', $ip.config('app.key'));
        $userAgentHash = hash('sha256', (string) $request->userAgent());
        $deviceToken = $validated['device_token'] ?? $request->cookie('lt_device_token') ?? Str::uuid()->toString();

        // Check if the user is the original creator of this report
        if ($existingReport->reporter_ip_hash === $ipHash) {
            return redirect()
                ->route('reports.success', ['ticketId' => $existingReport->ticket_id, 'mode' => 'supported'])
                ->withCookie(cookie('lt_device_token', $deviceToken, 525600))
                ->with('info', 'Ini adalah laporan yang Anda buat sebelumnya ('.$existingReport->ticket_id.'). Anda tidak perlu membuat laporan atau dukungan ulang.');
        }

        try {
            DB::transaction(function () use ($request, $existingReport, $ipHash, $userAgentHash, $deviceToken, $validated): void {
                $existingSupport = ReportSupport::query()
                    ->where('report_id', $existingReport->id)
                    ->where(function ($query) use ($ipHash, $deviceToken): void {
                        $query->where('ip_hash', $ipHash);
                        if (! empty($deviceToken)) {
                            $query->orWhere('device_token', $deviceToken);
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if ($existingSupport === null) {
                    $photoPath = null;
                    $photos = $request->file('photos', []);
                    if (! empty($photos) && isset($photos[0])) {
                        $photoPath = $photos[0]->store('reports/supports', 'public');
                    }

                    ReportSupport::query()->create([
                        'report_id' => $existingReport->id,
                        'device_token' => $deviceToken,
                        'ip_hash' => $ipHash,
                        'user_agent_hash' => $userAgentHash,
                        'photo_path' => $photoPath,
                        'comment' => trim($validated['description']),
                    ]);

                    $existingReport->increment('supports_count');
                } elseif (empty($existingSupport->photo_path)) {
                    $photos = $request->file('photos', []);
                    if (! empty($photos) && isset($photos[0])) {
                        $photoPath = $photos[0]->store('reports/supports', 'public');
                        $existingSupport->update([
                            'photo_path' => $photoPath,
                            'comment' => $existingSupport->comment ?? trim($validated['description']),
                        ]);
                    }
                }
            });
        } catch (\Throwable) {
            // Graceful fallback for concurrent duplicate submissions
        }

        return redirect()
            ->route('reports.success', ['ticketId' => $existingReport->ticket_id, 'mode' => 'supported'])
            ->withCookie(cookie('lt_device_token', $deviceToken, 525600))
            ->with('info', 'Laporan serupa sudah aktif di titik ini. Laporan Anda berhasil digabungkan sebagai bukti tambahan & dukungan warga.');
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
            'isSupporter' => request()->query('mode') === 'supported',
        ]);
    }
}

