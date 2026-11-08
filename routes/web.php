<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportApiController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportSupportController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\StaffReportActionController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Public Citizen Routes (No Login Required)
Route::get('/media/{path}', function (string $path): StreamedResponse {
    if (str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.public');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('api')->group(function (): void {
    Route::get('/reports/nearby', [ReportApiController::class, 'nearby'])->name('api.reports.nearby');
    Route::post('/reports/{ticketId}/support', [ReportSupportController::class, 'toggle'])
        ->middleware('throttle:60,1')
        ->name('api.reports.support');
});

Route::post('/lapor', [ReportController::class, 'store'])
    ->middleware('throttle:create-report')
    ->name('reports.store');

Route::get('/tiket/{ticketId}/berhasil', [ReportController::class, 'success'])
    ->name('reports.success');

Route::get('/lacak', [TicketController::class, 'index'])
    ->middleware('throttle:track-ticket')
    ->name('tickets.index');

Route::get('/lacak/{ticketId}', [TicketController::class, 'show'])
    ->middleware('throttle:track-ticket')
    ->name('tickets.show');

Route::get('/laporan/{report}', [StaffDashboardController::class, 'publicShow'])->name('reports.public.show');

// Staff & Admin Authentication Routes
Route::prefix('staff')->group(function (): void {
    Route::get('/login', [StaffAuthController::class, 'showLogin'])->name('staff.login');
    Route::post('/login', [StaffAuthController::class, 'login'])->name('staff.login.submit');
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('staff.logout');

    Route::middleware(['auth', 'role:petugas'])->group(function (): void {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
        Route::get('/reports/export', [StaffDashboardController::class, 'export'])->name('staff.reports.export');
        Route::get('/reports/{report}', [StaffDashboardController::class, 'show'])->name('staff.reports.show');
        Route::patch('/reports/{report}/status', [StaffReportActionController::class, 'updateStatus'])->name('staff.reports.status');
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [StaffDashboardController::class, 'adminIndex'])->name('dashboard');
    Route::get('/reports/{report}', [StaffDashboardController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report}/assign', [StaffReportActionController::class, 'assign'])->name('reports.assign');
    Route::patch('/reports/{report}/reject', [StaffReportActionController::class, 'reject'])->name('reports.reject');
});
