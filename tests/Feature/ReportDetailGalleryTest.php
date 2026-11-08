<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportMedia;
use App\Models\ReportSupport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportDetailGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_supporter_photos_and_comments_render_on_tracking_and_staff_screens(): void
    {
        Storage::fake('public');

        $agency = Agency::query()->create([
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH',
            'email' => 'dlh@malangkota.go.id',
        ]);

        $category = Category::query()->create([
            'name' => 'Sampah Liar',
            'slug' => 'sampah-liar',
            'icon' => 'trash',
            'sla_hours' => 24,
            'default_agency_id' => $agency->id,
        ]);

        $report = Report::query()->create([
            'ticket_id' => 'LT-GALL-0001',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Sampah Liar: Tumpukan sampah di bahu jalan',
            'description' => 'Tumpukan sampah plastik menutupi trotoar.',
            'latitude' => -7.960000,
            'longitude' => 112.640000,
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
            'supports_count' => 1,
        ]);

        ReportMedia::query()->create([
            'report_id' => $report->id,
            'type' => 'before',
            'file_path' => 'reports/before/sample_main.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
        ]);

        ReportSupport::query()->create([
            'report_id' => $report->id,
            'device_token' => 'device-supporter-1',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'photo_path' => 'reports/supports/supporter_angle1.jpg',
            'comment' => 'Baunya sangat menyengat terutama saat sore hari.',
        ]);

        // 1. Check Public Tracking Page
        $trackRes = $this->get(route('tickets.show', ['ticketId' => 'LT-GALL-0001']));
        $trackRes->assertOk();
        $trackRes->assertSee('Baunya sangat menyengat terutama saat sore hari.');
        $trackRes->assertSee('supporter_angle1.jpg');

        // 2. Check Staff Detail Page
        $staffUser = User::query()->create([
            'name' => 'Petugas Lapangan',
            'email' => User::SHARED_PETUGAS_EMAIL,
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'agency_id' => $agency->id,
        ]);

        $report->update([
            'assigned_to' => $staffUser->id,
            'status' => Report::STATUS_DITUGASKAN,
        ]);

        $staffRes = $this->actingAs($staffUser)->get(route('staff.reports.show', ['report' => $report->id]));
        $staffRes->assertOk();
        $staffRes->assertSee('Baunya sangat menyengat terutama saat sore hari.');
        $staffRes->assertSee('supporter_angle1.jpg');
    }
}
