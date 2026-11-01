<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Render the map-first Lapor Tuntas homepage.
     */
    public function index(): View
    {
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
        ]);
    }
}

