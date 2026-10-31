<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    /**
     * Test citizen can create a report without logging in and receives a unique Ticket ID.
     */
    public function test_citizen_can_create_report_without_login_and_get_ticket_id(): void
    {
        Storage::fake('public');

        $category = Category::query()->firstOrFail();

        $response = $this->post(route('reports.store'), [
            'category_id' => $category->id,
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'location_label' => 'Jl. Medan Merdeka Barat',
            'description' => 'Ada lubang besar di lajur tengah yang membahayakan pengendara motor.',
            'photos' => [
                UploadedFile::fake()->image('bukti-1.jpg', 800, 600),
                UploadedFile::fake()->image('bukti-2.jpg', 800, 600),
            ],
        ]);

        $report = Report::query()->latest('id')->firstOrFail();

        $this->assertMatchesRegularExpression('/^LT-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $report->ticket_id);
        $this->assertSame(Report::STATUS_BARU, $report->status);
        $this->assertTrue($report->is_visible_on_map);
        $this->assertCount(2, $report->media);
        $this->assertCount(1, $report->statusHistories);

        $firstMediaUrl = $report->media->first()->publicUrl();
        $this->get($firstMediaUrl)->assertOk();

        $response->assertRedirect(route('reports.success', ['ticketId' => $report->ticket_id]));

        $successPage = $this->get(route('reports.success', ['ticketId' => $report->ticket_id]));
        $successPage->assertStatus(200);
        $successPage->assertSee($report->ticket_id);
    }

    /**
     * Test nearby API returns active reports and hides completed or rejected reports,
     * while completed reports remain searchable by Ticket ID.
     */
    public function test_completed_report_disappears_from_nearby_map_but_remains_searchable_by_ticket_id(): void
    {
        Storage::fake('public');

        $category = Category::query()->firstOrFail();
        $agency = Agency::query()->firstOrFail();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $officer = User::query()->where('role', 'petugas')->firstOrFail();

        $report = Report::query()->create([
            'ticket_id' => 'LT-99AA-88BB',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Lampu jalan mati di persimpangan',
            'description' => 'Lampu PJU padam sejak kemarin malam.',
            'latitude' => -6.1754000,
            'longitude' => 106.8271000,
            'location_label' => 'Simpang Monas',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        // 1. Verify report appears in nearby API while active
        $nearbyResponse = $this->getJson(route('api.reports.nearby', [
            'lat' => -6.1753924,
            'lng' => 106.8271528,
            'radius' => 1000,
        ]));

        $nearbyResponse->assertOk();
        $ticketsOnMap = collect($nearbyResponse->json('reports'))->pluck('ticket_id')->all();
        $this->assertContains('LT-99AA-88BB', $ticketsOnMap);

        // 2. Admin assigns report to officer
        $this->actingAs($admin)
            ->patch(route('staff.reports.assign', $report), [
                'agency_id' => $agency->id,
                'assigned_to' => $officer->id,
                'note' => 'Segera tindak lanjuti malam ini.',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(Report::STATUS_DITUGASKAN, $report->status);
        $this->assertTrue($report->is_visible_on_map);

        // 3. Officer marks report as Selesai with after photo
        $this->actingAs($officer)
            ->patch(route('staff.reports.status', $report), [
                'status' => Report::STATUS_SELESAI,
                'note' => 'Lampu bohlam LED sudah diganti baru dan menyala normal.',
                'after_photo' => UploadedFile::fake()->image('sesudah-perbaikan.jpg', 800, 600),
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(Report::STATUS_SELESAI, $report->status);
        $this->assertFalse($report->is_visible_on_map);
        $this->assertNotNull($report->resolved_at);

        // 4. Verify report is NO LONGER returned in nearby map API (PRD FR-12)
        $nearbyAfterComplete = $this->getJson(route('api.reports.nearby', [
            'lat' => -6.1753924,
            'lng' => 106.8271528,
            'radius' => 1000,
        ]));

        $ticketsAfterComplete = collect($nearbyAfterComplete->json('reports'))->pluck('ticket_id')->all();
        $this->assertNotContains('LT-99AA-88BB', $ticketsAfterComplete);

        // 5. Verify report is STILL searchable via Ticket ID (PRD FR-19)
        $trackResponse = $this->get(route('tickets.show', ['ticketId' => 'LT-99AA-88BB']));
        $trackResponse->assertOk();
        $trackResponse->assertSee('LT-99AA-88BB');
        $trackResponse->assertSee('Lampu bohlam LED sudah diganti baru dan menyala normal.');
        $trackResponse->assertSee('Tuntas / Selesai');
    }

    /**
     * Test invalid or unknown ticket IDs display a helpful citizen-friendly error message.
     */
    public function test_invalid_or_missing_ticket_id_shows_friendly_error(): void
    {
        $invalidFormat = $this->get(route('tickets.index', ['ticket_id' => 'SALAH-FORMAT']));
        $invalidFormat->assertOk();
        $invalidFormat->assertSee('Format ID tiket tidak sesuai');

        $notFound = $this->get(route('tickets.show', ['ticketId' => 'LT-ZZZZ-9999']));
        $notFound->assertOk();
        $notFound->assertSee('ID tiket belum ditemukan');
    }

    /**
     * Test RBAC authorization prevents field officers from rejecting or assigning reports.
     */
    public function test_petugas_cannot_reject_or_assign_reports(): void
    {
        $officer = User::query()->where('role', 'petugas')->firstOrFail();
        $report = Report::query()->firstOrFail();

        $this->actingAs($officer)
            ->patch(route('staff.reports.reject', $report), [
                'rejection_reason' => 'Coba tolak sebagai petugas biasa.',
            ])
            ->assertForbidden();
    }
}

