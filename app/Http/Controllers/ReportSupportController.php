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
     * Toggle "Saya Juga Mengalami Ini" citizen support for a report.
     */
    public function toggle(Request $request, string $ticketId): JsonResponse
    {
        $report = Report::query()->where('ticket_id', $ticketId)->firstOrFail();

        $ip = $request->ip() ?? '127.0.0.1';
        $ipHash = hash('sha256', $ip . config('app.key'));
        $userAgentHash = hash('sha256', (string) $request->userAgent());

        $result = DB::transaction(function () use ($report, $ipHash, $userAgentHash): array {
            $existingSupport = ReportSupport::query()
                ->where('report_id', $report->id)
                ->where('ip_hash', $ipHash)
                ->lockForUpdate()
                ->first();

            if ($existingSupport !== null) {
                $existingSupport->delete();
                $report->decrement('supports_count');
                $report->refresh();

                return [
                    'supported' => false,
                    'message' => 'Dukungan untuk laporan ini dibatalkan.',
                ];
            }

            try {
                ReportSupport::query()->create([
                    'report_id' => $report->id,
                    'ip_hash' => $ipHash,
                    'user_agent_hash' => $userAgentHash,
                ]);

                $report->increment('supports_count');
                $report->refresh();

                return [
                    'supported' => true,
                    'message' => 'Terima kasih! Dukungan Anda berhasil dicatat.',
                ];
            } catch (UniqueConstraintViolationException) {
                // Graceful fallback if concurrent request already inserted the vote
                $report->refresh();

                return [
                    'supported' => true,
                    'message' => 'Dukungan Anda sudah tercatat sebelumnya.',
                ];
            }
        });

        return response()->json([
            'success' => true,
            'supported' => $result['supported'],
            'supports_count' => max(0, (int) $report->supports_count),
            'message' => $result['message'],
        ]);
    }
}

