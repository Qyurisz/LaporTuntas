<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportSupport;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportSupportController extends Controller
{
    /**
     * Record or toggle citizen support for a report.
     * When requested from the report creation form (support_only/from_form = true),
     * it ensures support is credited and returns the ticket details for tracking.
     */
    public function toggle(Request $request, string $ticketId): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['nullable', 'string', 'max:64'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'support_only' => ['nullable', 'boolean'],
            'from_form' => ['nullable', 'boolean'],
        ]);

        $report = Report::query()->where('ticket_id', $ticketId)->firstOrFail();

        $ip = $request->ip() ?? '127.0.0.1';
        $ipHash = hash('sha256', $ip . config('app.key'));
        $userAgentHash = hash('sha256', (string) $request->userAgent());
        $deviceToken = $validated['device_token'] ?? $request->cookie('lt_device_token');
        $isSupportOnly = $request->boolean('support_only') || $request->boolean('from_form');

        // Prevent original reporter from supporting their own report
        if ($report->reporter_ip_hash === $ipHash) {
            return response()->json([
                'success' => true,
                'supported' => true,
                'already_supported' => true,
                'is_reporter' => true,
                'supports_count' => max(0, (int) $report->supports_count),
                'ticket_id' => $report->ticket_id,
                'redirect_url' => route('reports.success', ['ticketId' => $report->ticket_id, 'mode' => 'supported']),
                'track_url' => route('tickets.show', ['ticketId' => $report->ticket_id]),
                'message' => 'Ini adalah laporan yang Anda buat sebelumnya.',
            ]);
        }

        $comment = ! empty($validated['comment']) ? trim((string) $validated['comment']) : null;

        $result = DB::transaction(function () use ($request, $report, $ipHash, $userAgentHash, $deviceToken, $isSupportOnly, $comment): array {
            $existingSupport = ReportSupport::query()
                ->where('report_id', $report->id)
                ->where(function ($query) use ($ipHash, $deviceToken): void {
                    $query->where('ip_hash', $ipHash);
                    if (! empty($deviceToken)) {
                        $query->orWhere('device_token', $deviceToken);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existingSupport !== null) {
                if ($isSupportOnly) {
                    if ($request->hasFile('photo') && empty($existingSupport->photo_path)) {
                        $storedPhoto = $request->file('photo')->store('reports/supports', 'public');
                        $existingSupport->update([
                            'photo_path' => $storedPhoto,
                            'comment' => $existingSupport->comment ?? $comment,
                        ]);
                    }

                    return [
                        'supported' => true,
                        'already_supported' => true,
                        'message' => 'Anda sudah terdaftar mendukung laporan ini sebelumnya.',
                    ];
                }

                $existingSupport->delete();
                $report->decrement('supports_count');
                $report->refresh();

                return [
                    'supported' => false,
                    'already_supported' => false,
                    'message' => 'Dukungan untuk laporan ini dibatalkan.',
                ];
            }

            try {
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('reports/supports', 'public');
                }

                ReportSupport::query()->create([
                    'report_id' => $report->id,
                    'device_token' => $deviceToken,
                    'ip_hash' => $ipHash,
                    'user_agent_hash' => $userAgentHash,
                    'photo_path' => $photoPath,
                    'comment' => $comment,
                ]);

                $report->increment('supports_count');
                $report->refresh();

                return [
                    'supported' => true,
                    'already_supported' => false,
                    'message' => 'Terima kasih! Dukungan Anda berhasil dicatat.',
                ];
            } catch (UniqueConstraintViolationException|\Throwable) {
                // Graceful fallback if concurrent request already inserted the vote
                $report->refresh();

                return [
                    'supported' => true,
                    'already_supported' => true,
                    'message' => 'Dukungan Anda sudah tercatat sebelumnya.',
                ];
            }
        });

        $response = response()->json([
            'success' => true,
            'supported' => $result['supported'],
            'already_supported' => $result['already_supported'],
            'supports_count' => max(0, (int) $report->supports_count),
            'ticket_id' => $report->ticket_id,
            'redirect_url' => route('reports.success', ['ticketId' => $report->ticket_id, 'mode' => 'supported']),
            'track_url' => route('tickets.show', ['ticketId' => $report->ticket_id]),
            'message' => $result['message'],
        ]);

        if (! empty($deviceToken)) {
            $response->withCookie(cookie('lt_device_token', $deviceToken, 525600));
        }

        return $response;
    }
}

