<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffDashboardController extends Controller
{
    /**
     * Display the operational dashboard for officers and admins.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $baseQuery = Report::query()
            ->with(['category', 'agency', 'assignedOfficer', 'media']);

        if ($user->isPetugas() && $user->agency_id !== null) {
            $baseQuery->where(function ($q) use ($user): void {
                $q->where('agency_id', $user->agency_id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        $statusFilter = (string) $request->query('status', 'all');
        $categoryFilter = (string) $request->query('category', 'all');

        $filteredQuery = clone $baseQuery;

        if ($statusFilter !== 'all' && $statusFilter !== '') {
            $filteredQuery->where('status', $statusFilter);
        }

        if ($categoryFilter !== 'all' && $categoryFilter !== '') {
            $filteredQuery->where('category_id', (int) $categoryFilter);
        }

        $reports = $filteredQuery->latest()->paginate(15)->withQueryString();

        $statsQuery = clone $baseQuery;
        $allReportsForStats = $statsQuery->get(['id', 'status', 'latitude', 'longitude', 'ticket_id', 'title', 'category_id', 'is_visible_on_map']);

        $counts = [
            'total' => $allReportsForStats->count(),
            'baru' => $allReportsForStats->where('status', Report::STATUS_BARU)->count(),
            'ditugaskan' => $allReportsForStats->where('status', Report::STATUS_DITUGASKAN)->count(),
            'diproses' => $allReportsForStats->where('status', Report::STATUS_DIPROSES)->count(),
            'selesai' => $allReportsForStats->where('status', Report::STATUS_SELESAI)->count(),
            'ditolak' => $allReportsForStats->where('status', Report::STATUS_DITOLAK)->count(),
        ];

        $categories = Category::query()->orderBy('name')->get();

        return view('staff.dashboard', [
            'user' => $user,
            'reports' => $reports,
            'counts' => $counts,
            'categories' => $categories,
            'statusFilter' => $statusFilter,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    /**
     * Display the operational detail and action panel for a single report.
     */
    public function show(Report $report): View
    {
        /** @var User $user */
        $user = Auth::user();

        $report->load(['category', 'agency', 'assignedOfficer', 'media', 'statusHistories.actor']);

        $agencies = Agency::query()->orderBy('name')->get();
        $officers = User::query()
            ->where('role', 'petugas')
            ->with('agency')
            ->orderBy('name')
            ->get();

        return view('staff.show', [
            'user' => $user,
            'report' => $report,
            'agencies' => $agencies,
            'officers' => $officers,
        ]);
    }
}

