<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportDuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    private Category $categoryRoad;
    private Category $categoryLight;
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->agency = Agency::query()->create([
            'name' => 'Dinas Pekerjaan Umum',
            'code' => 'DPU',
            'email' => 'dpu@malangkota.go.id',
        ]);

        $this->categoryRoad = Category::query()->create([
            'name' => 'Jalan Rusak / Berlubang',
            'slug' => 'jalan-rusak-berlubang',
            'icon' => 'alert-triangle',
            'sla_hours' => 72,
            'default_agency_id' => $this->agency->id,
        ]);

        $this->categoryLight = Category::query()->create([
            'name' => 'Penerangan Jalan Mati',
            'slug' => 'penerangan-jalan-mati',
            'icon' => 'sun',
            'sla_hours' => 48,
            'default_agency_id' => $this->agency->id,
        ]);
    }

    public function test_submitting_same_category_within_30m_merges_into_existing_report_as_support(): void
    {
        $existingReport = Report::query()->create([
            'ticket_id' => 'LT-ROAD-0001',
            'category_id' => $this->categoryRoad->id,
            'agency_id' => $this->agency->id,
            'title' => 'Jalan Rusak: Lubang jalan cukup dalam',
            'description' => 'Lubang jalan cukup dalam di dekat persimpangan.',
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'location_label' => 'Jl. Veteran No. 10',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
            'supports_count' => 0,
        ]);

        // Submit new report 15 meters away with same category
        // Latitude delta of 0.000135 is approximately 15 meters
        $response = $this->post(route('reports.store'), [
            'category_id' => $this->categoryRoad->id,
            'latitude' => -7.950135,
            'longitude' => 112.630000,
            'location_label' => 'Jl. Veteran No. 12',
            'description' => 'Saya juga melihat lubang ini, membahayakan pengendara motor.',
            'device_token' => 'device-alpha-111',
            'photos' => [
                UploadedFile::fake()->image('supporter_angle.jpg', 600, 600),
            ],
        ]);

        // Assert no new report was created (count remains 1)
        $this->assertEquals(1, Report::query()->count());

        // Assert redirected to existing ticket success screen as supporter
        $response->assertRedirect(route('reports.success', [
            'ticketId' => 'LT-ROAD-0001',
            'mode' => 'supported',
        ]));

        // Assert supports count incremented
        $existingReport->refresh();
        $this->assertEquals(1, $existingReport->supports_count);

        // Assert ReportSupport record created with photo and comment
        $support = ReportSupport::query()->where('report_id', $existingReport->id)->first();
        $this->assertNotNull($support);
        $this->assertEquals('device-alpha-111', $support->device_token);
        $this->assertStringContainsString('membahayakan pengendara motor', (string) $support->comment);
        $this->assertNotNull($support->photo_path);
        Storage::disk('public')->assertExists($support->photo_path);
    }

    public function test_submitting_different_category_at_same_location_creates_new_report(): void
    {
        $existingRoadReport = Report::query()->create([
            'ticket_id' => 'LT-ROAD-0001',
            'category_id' => $this->categoryRoad->id,
            'agency_id' => $this->agency->id,
            'title' => 'Jalan Rusak',
            'description' => 'Lubang jalan cukup dalam.',
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        // Submit report at the exact same coordinates but with DIFFERENT category (Penerangan Jalan Mati)
        $response = $this->post(route('reports.store'), [
            'category_id' => $this->categoryLight->id,
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'description' => 'Lampu jalan di titik ini mati total sejak kemarin malam.',
            'device_token' => 'device-beta-222',
            'photos' => [
                UploadedFile::fake()->image('lampu_mati.jpg', 600, 600),
            ],
        ]);

        // Assert 2 reports now exist in database
        $this->assertEquals(2, Report::query()->count());

        $newReport = Report::query()->where('category_id', $this->categoryLight->id)->first();
        $this->assertNotNull($newReport);
        $this->assertNotEquals('LT-ROAD-0001', $newReport->ticket_id);
        $response->assertRedirect(route('reports.success', ['ticketId' => $newReport->ticket_id]));
    }

    public function test_submitting_same_category_beyond_30m_creates_new_report(): void
    {
        Report::query()->create([
            'ticket_id' => 'LT-ROAD-0001',
            'category_id' => $this->categoryRoad->id,
            'agency_id' => $this->agency->id,
            'title' => 'Jalan Rusak',
            'description' => 'Lubang jalan cukup dalam.',
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        // Submit 100 meters away (approx 0.0009 degrees delta) with same category
        $response = $this->post(route('reports.store'), [
            'category_id' => $this->categoryRoad->id,
            'latitude' => -7.950900,
            'longitude' => 112.630000,
            'description' => 'Lubang jalan lain di persimpangan berbeda.',
            'device_token' => 'device-gamma-333',
            'photos' => [
                UploadedFile::fake()->image('lubang_jauh.jpg', 600, 600),
            ],
        ]);

        $this->assertEquals(2, Report::query()->count());
    }

    public function test_double_vote_from_same_device_or_ip_is_blocked(): void
    {
        $report = Report::query()->create([
            'ticket_id' => 'LT-ROAD-0001',
            'category_id' => $this->categoryRoad->id,
            'agency_id' => $this->agency->id,
            'title' => 'Jalan Rusak',
            'description' => 'Lubang jalan',
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
            'supports_count' => 0,
        ]);

        // First vote via API
        $res1 = $this->postJson(route('api.reports.support', ['ticketId' => 'LT-ROAD-0001']), [
            'device_token' => 'device-same-999',
            'support_only' => true,
        ]);
        $res1->assertOk()->assertJson(['supported' => true, 'already_supported' => false]);
        $this->assertEquals(1, $report->fresh()->supports_count);

        // Second vote with same device token but different IP
        $res2 = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->postJson(route('api.reports.support', ['ticketId' => 'LT-ROAD-0001']), [
                'device_token' => 'device-same-999',
                'support_only' => true,
            ]);
        $res2->assertOk()->assertJson(['supported' => true, 'already_supported' => true]);
        $this->assertEquals(1, $report->fresh()->supports_count);
    }

    public function test_original_reporter_cannot_support_own_report(): void
    {
        $ip = '127.0.0.1';
        $ipHash = hash('sha256', $ip . config('app.key'));

        $report = Report::query()->create([
            'ticket_id' => 'LT-OWN-0001',
            'category_id' => $this->categoryRoad->id,
            'agency_id' => $this->agency->id,
            'title' => 'Jalan Rusak Sendiri',
            'description' => 'Laporan saya sendiri',
            'latitude' => -7.950000,
            'longitude' => 112.630000,
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
            'supports_count' => 0,
            'reporter_ip_hash' => $ipHash,
        ]);

        // Attempting to support own report via API
        $res = $this->postJson(route('api.reports.support', ['ticketId' => 'LT-OWN-0001']), [
            'device_token' => 'dev-owner',
            'support_only' => true,
        ]);

        $res->assertOk()->assertJson([
            'already_supported' => true,
            'is_reporter' => true,
        ]);
        $this->assertEquals(0, $report->fresh()->supports_count);

        // Attempting to submit report on same spot with same category
        $response = $this->post(route('reports.store'), [
            'category_id' => $this->categoryRoad->id,
            'latitude' => -7.950100,
            'longitude' => 112.630000,
            'description' => 'Mencoba membuat laporan lagi di titik yang sama.',
            'photos' => [
                UploadedFile::fake()->image('coba.jpg', 600, 600),
            ],
        ]);

        $response->assertRedirect(route('reports.success', ['ticketId' => 'LT-OWN-0001', 'mode' => 'supported']));
        $this->assertEquals(0, $report->fresh()->supports_count);
    }
}

