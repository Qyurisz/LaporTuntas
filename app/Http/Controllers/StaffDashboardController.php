<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function adminIndex(Request $request): View
    {
        return $this->dashboard($request, true);
    }

    /**
     * Display the operational dashboard for officers and admins.
     */
    public function index(Request $request): View
    {
        return $this->dashboard($request, false);
    }

    private function dashboard(Request $request, bool $isAdmin): View
    {
        /** @var User $user */
        $user = Auth::user();

        $baseQuery = Report::query()
            ->with(['category', 'agency', 'assignedOfficer', 'media']);

        if (! $isAdmin) {
            $baseQuery->whereHas('assignedOfficer', function ($query): void {
                $query->where('role', 'petugas');
            });
        }

        $statusFilter = (string) $request->query('status', 'all');
        $categoryFilter = (string) $request->query('category', 'all');
        $assignmentFilter = (string) $request->query('assignment', 'all');
        $agencyFilter = (int) $request->query('agency', 0);

        $filteredQuery = clone $baseQuery;

        if ($statusFilter === 'overdue') {
            $filteredQuery->overdue();
        } elseif ($statusFilter !== 'all' && $statusFilter !== '') {
            $filteredQuery->where('status', $statusFilter);
        }

        if ($categoryFilter !== 'all' && $categoryFilter !== '') {
            $filteredQuery->where('category_id', (int) $categoryFilter);
        }

        if ($agencyFilter > 0) {
            $filteredQuery->where('agency_id', $agencyFilter);
        }

        if ($isAdmin && $assignmentFilter === 'pending') {
            $filteredQuery->whereNull('assigned_to')->where('status', Report::STATUS_BARU);
        } elseif ($isAdmin && $assignmentFilter === 'assigned') {
            $filteredQuery->whereNotNull('assigned_to');
        }

        $reports = $filteredQuery->latest()->paginate(15)->withQueryString();

        $statsQuery = clone $baseQuery;
        if ($agencyFilter > 0) {
            $statsQuery->where('agency_id', $agencyFilter);
        }
        $allReportsForStats = $statsQuery->get(['id', 'status', 'latitude', 'longitude', 'ticket_id', 'title', 'category_id', 'assigned_to', 'is_visible_on_map']);

        $counts = [
            'total' => $allReportsForStats->count(),
            'baru' => $allReportsForStats->where('status', Report::STATUS_BARU)->count(),
            'ditugaskan' => $allReportsForStats->where('status', Report::STATUS_DITUGASKAN)->count(),
            'diproses' => $allReportsForStats->where('status', Report::STATUS_DIPROSES)->count(),
            'selesai' => $allReportsForStats->where('status', Report::STATUS_SELESAI)->count(),
            'ditolak' => $allReportsForStats->where('status', Report::STATUS_DITOLAK)->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),
            'menunggu_penugasan' => $allReportsForStats->whereNull('assigned_to')->where('status', Report::STATUS_BARU)->count(),
        ];

        $categories = Category::query()->with('defaultAgency')->orderBy('name')->get();
        $agencies = Agency::query()->orderBy('name')->get();

        return view($isAdmin ? 'admin.dashboard' : 'staff.dashboard', [
            'user' => $user,
            'reports' => $reports,
            'counts' => $counts,
            'categories' => $categories,
            'statusFilter' => $statusFilter,
            'categoryFilter' => $categoryFilter,
            'isAdminDashboard' => $isAdmin,
            'assignmentFilter' => $assignmentFilter,
            'agencyFilter' => $agencyFilter,
            'agencies' => $agencies,
        ]);
    }

    /**
     * Export filtered reports to CSV (Excel-compatible with UTF-8 BOM).
     */
    public function export(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Report::query()
            ->with(['category', 'agency', 'assignedOfficer', 'media', 'statusHistories']);

        if ($user->isPetugas() && $user->agency_id !== null) {
            $query->where(function ($q) use ($user): void {
                $q->where('agency_id', $user->agency_id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        $statusFilter = (string) $request->query('status', 'all');
        $categoryFilter = (string) $request->query('category', 'all');

        if ($statusFilter === 'overdue') {
            $query->overdue();
        } elseif ($statusFilter !== 'all' && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($categoryFilter !== 'all' && $categoryFilter !== '') {
            $query->where('category_id', (int) $categoryFilter);
        }

        $filename = 'rekap-laporan-laportuntas-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'No',
                'ID Tiket',
                'Tanggal Lapor',
                'Status',
                'Kategori Masalah',
                'Judul Laporan',
                'Deskripsi',
                'Instansi Penangan',
                'Petugas Lapangan',
                'Dukungan Warga',
                'Target SLA',
                'Batas Waktu SLA',
                'Status SLA',
                'Patokan Lokasi',
                'Latitude',
                'Longitude',
                'Tanggal Selesai',
                'Catatan Tindak Lanjut Terakhir',
                'Jumlah Foto Bukti',
                'Tampil di Peta Publik',
            ]);

            $counter = 1;

            $query->latest()->chunk(200, function ($reports) use ($handle, &$counter): void {
                foreach ($reports as $report) {
                    $lastHistory = $report->statusHistories->last();
                    $lastNote = $lastHistory ? $lastHistory->note : ($report->rejection_reason ?? '-');
                    $sla = $report->getSlaMeta();

                    fputcsv($handle, [
                        $counter++,
                        $report->ticket_id,
                        $report->created_at?->format('Y-m-d H:i:s'),
                        $report->statusMeta()['label'],
                        $report->category?->name ?? 'Tanpa Kategori',
                        $report->title,
                        $report->description,
                        $report->agency?->name ?? 'Belum Ditugaskan',
                        $report->assignedOfficer?->name ?? '-',
                        (int) ($report->supports_count ?? 0),
                        $sla['target_label'],
                        $sla['deadline_label'],
                        $sla['badge_label'],
                        $report->location_label ?? '-',
                        $report->latitude,
                        $report->longitude,
                        $report->resolved_at?->format('Y-m-d H:i:s') ?? '-',
                        $lastNote,
                        $report->media->count(),
                        $report->isVisibleOnPublicMap() ? 'Ya' : 'Tidak',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Display the operational detail and action panel for a single report.
     */
    public function show(Report $report): View
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isPetugas()) {
            abort_unless($report->assignedOfficer?->isPetugas(), 403);
        }

        $report->load(['category.defaultAgency', 'agency', 'assignedOfficer', 'media', 'statusHistories.actor', 'supportsWithPhotos', 'supportsWithComments']);

        $agencies = $user->isAdmin() ? Agency::query()->orderBy('name')->get() : collect();
        $sharedPetugas = $user->isAdmin()
            ? User::query()->where('email', User::SHARED_PETUGAS_EMAIL)->first()
            : null;

        return view('staff.show', [
            'user' => $user,
            'report' => $report,
            'agencies' => $agencies,
            'sharedPetugas' => $sharedPetugas,
        ]);
    }

    public function publicShow(Report $report): View
    {
        $report->load(['category', 'agency', 'assignedOfficer', 'media', 'statusHistories.actor']);

        return view('staff.public-show', ['report' => $report]);
    }
}

