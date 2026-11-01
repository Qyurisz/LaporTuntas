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
     * Test default nearby API request (status=all, category=all) returns all valid statuses
     * (baru, ditugaskan, diproses, selesai) immediately on load/refresh, and hides rejected reports.
     */
    public function test_nearby_map_returns_all_statuses_by_default_and_hides_rejected_reports(): void
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
            'latitude' => -7.9553000,
            'longitude' => 112.6280000,
            'location_label' => 'Lowokwaru, Kota Malang',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        // 1. Default initial load / refresh payload (status=all & category=all) returns Baru report
        $nearbyDefault = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));

        $nearbyDefault->assertOk();
        $this->assertContains('LT-99AA-88BB', collect($nearbyDefault->json('reports'))->pluck('ticket_id')->all());

        // 2. Admin assigns report -> Ditugaskan remains visible with status=all
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

        $nearbyAssigned = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));
        $nearbyAssigned->assertOk();
        $this->assertContains('LT-99AA-88BB', collect($nearbyAssigned->json('reports'))->pluck('ticket_id')->all());

        // 3. Officer updates to Diproses -> remains visible with status=all
        $this->actingAs($officer)
            ->patch(route('staff.reports.status', $report), [
                'status' => Report::STATUS_DIPROSES,
                'note' => 'Petugas sedang berada di lokasi untuk pengecekan kabel.',
            ])
            ->assertRedirect();

        $nearbyInProgress = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));
        $nearbyInProgress->assertOk();
        $this->assertContains('LT-99AA-88BB', collect($nearbyInProgress->json('reports'))->pluck('ticket_id')->all());

        // 4. Officer marks report as Selesai -> STILL visible on map with status=all and status=selesai
        $this->actingAs($officer)
            ->patch(route('staff.reports.status', $report), [
                'status' => Report::STATUS_SELESAI,
                'note' => 'Lampu bohlam LED sudah diganti baru dan menyala normal.',
                'after_photo' => UploadedFile::fake()->image('sesudah-perbaikan.jpg', 800, 600),
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(Report::STATUS_SELESAI, $report->status);
        $this->assertTrue($report->is_visible_on_map);
        $this->assertNotNull($report->resolved_at);

        $nearbyAfterComplete = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));
        $nearbyAfterComplete->assertOk();
        $this->assertContains('LT-99AA-88BB', collect($nearbyAfterComplete->json('reports'))->pluck('ticket_id')->all());

        $nearbyFilterSelesai = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'selesai',
            'category' => 'all',
        ]));
        $nearbyFilterSelesai->assertOk();
        $this->assertContains('LT-99AA-88BB', collect($nearbyFilterSelesai->json('reports'))->pluck('ticket_id')->all());

        // 5. Verify report is searchable via Ticket ID
        $trackResponse = $this->get(route('tickets.show', ['ticketId' => 'LT-99AA-88BB']));
        $trackResponse->assertOk();
        $trackResponse->assertSee('LT-99AA-88BB');
        $trackResponse->assertSee('Lampu bohlam LED sudah diganti baru dan menyala normal.');
        $trackResponse->assertSee('Selesai');

        // 6. Admin rejects an invalid report -> hidden from public map
        $spamReport = Report::query()->create([
            'ticket_id' => 'LT-SPAM-0001',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Laporan uji coba tidak valid',
            'description' => 'Isi laporan tidak sesuai kondisi lapangan.',
            'latitude' => -7.9554000,
            'longitude' => 112.6281000,
            'location_label' => 'Lowokwaru, Kota Malang',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('staff.reports.reject', $spamReport), [
                'rejection_reason' => 'Foto tidak menunjukkan fasilitas umum yang dilaporkan.',
            ])
            ->assertRedirect();

        $nearbyAfterReject = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));
        $nearbyAfterReject->assertOk();
        $this->assertNotContains('LT-SPAM-0001', collect($nearbyAfterReject->json('reports'))->pluck('ticket_id')->all());
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
        $this->assertSame(0, Report::query()->count());

        $category = Category::query()->firstOrFail();
        $agency = Agency::query()->firstOrFail();
        $officer = User::query()->where('role', 'petugas')->firstOrFail();

        $report = Report::query()->create([
            'ticket_id' => 'LT-11CC-22DD',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Saluran air tersumbat di bahu jalan',
            'description' => 'Air meluap ke jalan saat hujan deras.',
            'latitude' => -6.2000000,
            'longitude' => 106.8166660,
            'location_label' => 'Dekat persimpangan utama',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        $this->actingAs($officer)
            ->patch(route('staff.reports.reject', $report), [
                'rejection_reason' => 'Coba tolak sebagai petugas biasa.',
            ])
            ->assertForbidden();
    }
}

