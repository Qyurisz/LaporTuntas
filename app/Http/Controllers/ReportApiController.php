<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    /**
     * Return active nearby reports based on GPS coordinates, radius, category, and status.
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:25000'],
            'category' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', 'in:baru,ditugaskan,diproses'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $radiusMeters = (int) ($validated['radius'] ?? 1000);

        $query = Report::query()
            ->with(['category', 'agency', 'media'])
            ->activeOnMap()
            ->withinBoundingBox($lat, $lng, $radiusMeters);

        if (! empty($validated['category']) && $validated['category'] !== 'all') {
            $categorySlug = $validated['category'];
            $query->whereHas('category', function ($q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if (! empty($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        $candidates = $query->latest()->get();

        $results = [];
        foreach ($candidates as $report) {
            $distanceMeters = Report::calculateDistanceMeters(
                $lat,
                $lng,
                (float) $report->latitude,
                (float) $report->longitude
            );

            if ($distanceMeters > $radiusMeters) {
                continue;
            }

            $meta = $report->statusMeta();
            $beforeMedia = $report->media->firstWhere('type', 'before');

            $results[] = [
                'id' => $report->id,
                'ticket_id' => $report->ticket_id,
                'title' => $report->title,
                'description' => $report->description,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'location_label' => $report->location_label ?: 'Titik koordinat GPS warga',
                'distance_meters' => $distanceMeters,
                'distance_label' => $distanceMeters < 1000
                    ? "{$distanceMeters} m"
                    : number_format($distanceMeters / 1000, 1, ',', '.').' km',
                'status' => $report->status,
                'status_label' => $meta['label'],
                'status_color' => $meta['color'],
                'status_bg' => $meta['bg'],
                'status_text' => $meta['text'],
                'status_icon' => $meta['icon'],
                'category_id' => $report->category_id,
                'category_name' => $report->category?->name ?? 'Lainnya',
                'category_slug' => $report->category?->slug ?? 'lainnya',
                'category_icon' => $report->category?->icon ?? 'map-pin',
                'agency_name' => $report->agency?->name ?? 'Menunggu Penugasan Instansi',
                'agency_code' => $report->agency?->code ?? '-',
                'photo_url' => $beforeMedia ? $beforeMedia->publicUrl() : null,
                'updated_human' => $report->updated_at?->diffForHumans() ?? 'Baru saja',
                'created_human' => $report->created_at?->diffForHumans() ?? 'Baru saja',
                'track_url' => route('tickets.show', ['ticketId' => $report->ticket_id]),
            ];
        }

        usort($results, fn (array $a, array $b): int => $a['distance_meters'] <=> $b['distance_meters']);

        return response()->json([
            'count' => count($results),
            'radius_meters' => $radiusMeters,
            'reports' => $results,
        ]);
    }

    /**
     * Relocate seeded demo reports around the user's live GPS coordinates for demo convenience.
     */
    public function relocateDemoData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $baseLat = (float) $validated['lat'];
        $baseLng = (float) $validated['lng'];

        $offsets = [
            ['dLat' => 0.0016, 'dLng' => -0.0012, 'label' => 'Sekitar 210m arah Utara-Barat'],
            ['dLat' => -0.0021, 'dLng' => 0.0018, 'label' => 'Sekitar 290m arah Selatan-Timur'],
            ['dLat' => 0.0029, 'dLng' => 0.0022, 'label' => 'Sekitar 380m arah Timur Laut'],
            ['dLat' => -0.0014, 'dLng' => -0.0026, 'label' => 'Sekitar 320m arah Barat Daya'],
            ['dLat' => 0.0009, 'dLng' => 0.0031, 'label' => 'Sekitar 350m arah Timur'],
            ['dLat' => -0.0033, 'dLng' => -0.0008, 'label' => 'Sekitar 370m arah Selatan'],
            ['dLat' => 0.0012, 'dLng' => 0.0011, 'label' => 'Sekitar 180m dari lokasi Anda (Selesai)'],
        ];

        $reports = Report::query()->orderBy('id')->limit(count($offsets))->get();

        foreach ($reports as $index => $report) {
            $offset = $offsets[$index];
            $report->update([
                'latitude' => round($baseLat + $offset['dLat'], 7),
                'longitude' => round($baseLng + $offset['dLng'], 7),
                'location_label' => $offset['label'],
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Data simulasi demo berhasil dipindahkan ke sekitar titik GPS Anda.',
        ]);
    }
}

