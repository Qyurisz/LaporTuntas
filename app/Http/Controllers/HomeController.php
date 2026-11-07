<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Render the map-first Lapor Tuntas homepage.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $isInternalUser = $user?->isAdmin() || $user?->isPetugas();

        if ($isInternalUser && ! $request->boolean('map')) {
            return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard');
        }

        $categories = Category::query()
            ->with('defaultAgency')
            ->orderBy('id')
            ->get();

        $activeStatuses = [
            Report::STATUS_BARU => Report::getStatusMetadata(Report::STATUS_BARU),
            Report::STATUS_DITUGASKAN => Report::getStatusMetadata(Report::STATUS_DITUGASKAN),
            Report::STATUS_DIPROSES => Report::getStatusMetadata(Report::STATUS_DIPROSES),
            Report::STATUS_SELESAI => Report::getStatusMetadata(Report::STATUS_SELESAI),
        ];

        return view('home', [
            'categories' => $categories,
            'activeStatuses' => $activeStatuses,
            'isInternalMap' => (bool) $isInternalUser,
        ]);
    }
}

