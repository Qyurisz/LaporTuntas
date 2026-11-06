<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    /**
     * Return public nearby reports based on search coordinates, radius, category, and status.
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'user_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'user_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:100', 'max:100000'],
            'category' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string', 'in:all,baru,ditugaskan,diproses,selesai'],
            'focus_ticket' => ['nullable', 'string', 'max:25'],
        ]);

        $searchLat = (float) $validated['lat'];
        $searchLng = (float) $validated['lng'];
        $originLat = isset($validated['user_lat']) ? (float) $validated['user_lat'] : $searchLat;
        $originLng = isset($validated['user_lng']) ? (float) $validated['user_lng'] : $searchLng;
        $radiusMeters = (int) ($validated['radius'] ?? 5000);
        $focusTicket = ! empty($validated['focus_ticket'])
            ? strtoupper(trim((string) $validated['focus_ticket']))
            : null;

        $query = Report::query()
            ->with(['category', 'agency', 'media'])
            ->activeOnMap()
            ->withinBoundingBox($searchLat, $searchLng, $radiusMeters);

        if (! empty($validated['category']) && $validated['category'] !== 'all') {
            $categorySlug = $validated['category'];
            $query->whereHas('category', function ($q) use ($categorySlug): void {
                $q->where('slug', $categorySlug);
            });
        }

        if (! empty($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        $candidates = $query->latest()->get()->keyBy('id');

        if ($focusTicket !== null) {
            $focusedReport = Report::query()
                ->with(['category', 'agency', 'media'])
                ->activeOnMap()
                ->where('ticket_id', $focusTicket)
                ->first();

            if ($focusedReport && ! $candidates->has($focusedReport->id)) {
                $candidates->put($focusedReport->id, $focusedReport);
            }
        }

        $results = [];
        foreach ($candidates as $report) {
            $searchDistanceMeters = Report::calculateDistanceMeters(
                $searchLat,
                $searchLng,
                (float) $report->latitude,
                (float) $report->longitude
            );

            $isFocused = $focusTicket !== null && $report->ticket_id === $focusTicket;

            if (! $isFocused && $searchDistanceMeters > $radiusMeters) {
                continue;
            }

            $userDistanceMeters = Report::calculateDistanceMeters(
                $originLat,
                $originLng,
                (float) $report->latitude,
                (float) $report->longitude
            );

            $meta = $report->statusMeta();
            $slaMeta = $report->getSlaMeta();
            $beforeMedia = $report->media->firstWhere('type', 'before');
            $afterMedia = $report->media->firstWhere('type', 'after');
            $primaryMedia = $report->status === Report::STATUS_SELESAI
                ? ($afterMedia ?? $beforeMedia)
                : $beforeMedia;

            $results[] = [
                'id' => $report->id,
                'ticket_id' => $report->ticket_id,
                'title' => $report->title,
                'description' => $report->description,
                'latitude' => (float) $report->latitude,
                'longitude' => (float) $report->longitude,
                'location_label' => $report->location_label ?: 'Titik koordinat GPS warga',
                'distance_meters' => $userDistanceMeters,
                'distance_label' => $userDistanceMeters < 1000
                    ? "{$userDistanceMeters} m"
                    : number_format($userDistanceMeters / 1000, 1, ',', '.').' km',
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
                'photo_url' => $primaryMedia ? $primaryMedia->publicUrl() : null,
                'supports_count' => (int) ($report->supports_count ?? 0),
                'sla_target_label' => $slaMeta['target_label'],
                'sla_deadline_label' => $slaMeta['deadline_label'],
                'sla_badge_label' => $slaMeta['badge_label'],
                'sla_badge_type' => $slaMeta['badge_type'],
                'is_overdue' => (bool) $slaMeta['is_overdue'],
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
}
