<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportMedia;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffReportActionController extends Controller
{
    /**
     * Assign a report manually to an agency and/or field officer (Admin only).
     */
    public function assign(Request $request, Report $report): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menugaskan laporan.');
        }

        $validated = $request->validate([
            'agency_id' => ['required', 'integer', 'exists:agencies,id'],
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'petugas')
                    ->where('email', User::SHARED_PETUGAS_EMAIL)),
            ],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($report, $validated, $user): void {
            $oldStatus = $report->status;
            $newStatus = in_array($oldStatus, [Report::STATUS_BARU, Report::STATUS_DITOLAK], true)
                ? Report::STATUS_DITUGASKAN
                : $oldStatus;

            $report->update([
                'agency_id' => (int) $validated['agency_id'],
                'assigned_to' => (int) $validated['assigned_to'],
                'status' => $newStatus,
                'rejection_reason' => null,
                'is_visible_on_map' => in_array($newStatus, [
                    Report::STATUS_BARU,
                    Report::STATUS_DITUGASKAN,
                    Report::STATUS_DIPROSES,
                    Report::STATUS_SELESAI,
                ], true),
            ]);

            $report->refresh();
            $agencyName = $report->agency?->name ?? 'Instansi terkait';
            $officerName = $report->assignedOfficer?->name;

            $defaultNote = $officerName
                ? "Laporan ditugaskan ke {$agencyName} ({$officerName})."
                : "Laporan diteruskan ke {$agencyName}.";

            StatusHistory::query()->create([
                'report_id' => $report->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'note' => ! empty($validated['note']) ? trim($validated['note']) : $defaultNote,
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Laporan berhasil ditugaskan dan linimasa telah diperbarui.');
    }

    /**
     * Update report operational status (Diproses or Selesai) with optional after-photo and note.
     */
    public function updateStatus(Request $request, Report $report): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->isPetugas() && $report->assignedOfficer?->isPetugas(), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ditugaskan,diproses,selesai'],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
            'after_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'note.required' => 'Berikan catatan tindak lanjut agar warga mengetahui perkembangan penanganan.',
            'note.min' => 'Catatan tindak lanjut minimal 5 karakter.',
        ]);

        DB::transaction(function () use ($request, $report, $validated, $user): void {
            $oldStatus = $report->status;
            $newStatus = $validated['status'];
            $isCompleted = $newStatus === Report::STATUS_SELESAI;

            $report->update([
                'status' => $newStatus,
                'is_visible_on_map' => true,
                'resolved_at' => $isCompleted ? now() : null,
            ]);

            if ($request->hasFile('after_photo')) {
                $photoFile = $request->file('after_photo');
                $storedPath = $photoFile->store('reports/after', 'public');

                ReportMedia::query()->create([
                    'report_id' => $report->id,
                    'type' => 'after',
                    'file_path' => $storedPath,
                    'mime_type' => $photoFile->getClientMimeType(),
                    'file_size' => $photoFile->getSize(),
                ]);
            }

            StatusHistory::query()->create([
                'report_id' => $report->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'note' => trim($validated['note']),
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);
        });

        $message = $validated['status'] === Report::STATUS_SELESAI
            ? 'Laporan ditandai Selesai dan tetap ditampilkan sebagai tuntas di peta publik.'
            : 'Status dan catatan tindak lanjut berhasil diperbarui.';

        return back()->with('success', $message);
    }

    /**
     * Reject an invalid/spam report with a mandatory reason (Admin only).
     */
    public function reject(Request $request, Report $report): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat menolak laporan.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi secara transparan.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        DB::transaction(function () use ($report, $validated, $user): void {
            $oldStatus = $report->status;

            $report->update([
                'status' => Report::STATUS_DITOLAK,
                'rejection_reason' => trim($validated['rejection_reason']),
                'assigned_to' => null,
                'is_visible_on_map' => false,
            ]);

            StatusHistory::query()->create([
                'report_id' => $report->id,
                'from_status' => $oldStatus,
                'to_status' => Report::STATUS_DITOLAK,
                'note' => 'Laporan ditolak: '.trim($validated['rejection_reason']),
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Laporan telah ditolak dan penanda disembunyikan dari peta publik.');
    }
}

