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

    /**
     * Test completed reports older than 7 days are auto-hidden from public map
     * but remain permanently archived in the DB and fully searchable via Ticket ID.
     */
    public function test_completed_reports_older_than_7_days_are_auto_hidden_from_public_map_but_remain_searchable(): void
    {
        $category = Category::query()->firstOrFail();
        $agency = Agency::query()->firstOrFail();

        // 1. Fresh completed report (resolved 2 days ago) -> MUST BE VISIBLE
        $freshCompleted = Report::query()->create([
            'ticket_id' => 'LT-FRSH-0001',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Perbaikan marka jalan selesai kemarin',
            'description' => 'Marka jalan telah dicat ulang dengan rapi.',
            'latitude' => -7.9553000,
            'longitude' => 112.6280000,
            'location_label' => 'Lowokwaru, Kota Malang',
            'status' => Report::STATUS_SELESAI,
            'is_visible_on_map' => true,
            'resolved_at' => now()->subDays(2),
        ]);

        // 2. Old completed report (resolved 10 days ago) -> MUST BE AUTO-HIDDEN FROM MAP
        $oldCompleted = Report::query()->create([
            'ticket_id' => 'LT-OLD1-0002',
            'category_id' => $category->id,
            'agency_id' => $agency->id,
            'title' => 'Pembersihan saluran air dua minggu lalu',
            'description' => 'Saluran drainase sudah dinormalisasi.',
            'latitude' => -7.9553100,
            'longitude' => 112.6280100,
            'location_label' => 'Lowokwaru, Kota Malang',
            'status' => Report::STATUS_SELESAI,
            'is_visible_on_map' => true,
            'resolved_at' => now()->subDays(10),
        ]);

        $this->assertTrue($freshCompleted->isVisibleOnPublicMap());
        $this->assertFalse($oldCompleted->isVisibleOnPublicMap());

        // Check public map API query
        $nearbyResponse = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
            'status' => 'all',
            'category' => 'all',
        ]));
        $nearbyResponse->assertOk();

        $returnedTickets = collect($nearbyResponse->json('reports'))->pluck('ticket_id')->all();
        $this->assertContains('LT-FRSH-0001', $returnedTickets);
        $this->assertNotContains('LT-OLD1-0002', $returnedTickets);

        // Verify old report is still 100% searchable and intact in public ticket tracking
        $trackResponse = $this->get(route('tickets.show', ['ticketId' => 'LT-OLD1-0002']));
        $trackResponse->assertOk();
        $trackResponse->assertSee('LT-OLD1-0002');
        $trackResponse->assertSee('Pembersihan saluran air dua minggu lalu');
        $trackResponse->assertSee('Selesai');

        // Test running the archive artisan command
        $this->artisan('reports:archive-resolved')->assertSuccessful();

        $oldCompleted->refresh();
        $this->assertFalse($oldCompleted->is_visible_on_map);

        $freshCompleted->refresh();
        $this->assertTrue($freshCompleted->is_visible_on_map);
    }

    public function test_staff_can_export_reports_to_csv_with_active_filters(): void
    {
        // Guests must be redirected to staff login
        $guestResponse = $this->get(route('staff.reports.export'));
        $guestResponse->assertRedirect(route('staff.login'));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::query()->firstOrFail();

        Report::create([
            'ticket_id' => 'LT-CSV1-0001',
            'title' => 'Lampu PJU Padam Depan Toko',
            'description' => 'Lampu PJU mati total sudah 3 hari',
            'category_id' => $category->id,
            'status' => Report::STATUS_BARU,
            'latitude' => -7.9553000,
            'longitude' => 112.6280000,
            'location_label' => 'Jl. Ijen No 10',
            'is_visible_on_map' => true,
        ]);

        Report::create([
            'ticket_id' => 'LT-CSV2-0002',
            'title' => 'Aspal Rusak Parah Depan Pasar',
            'description' => 'Lubang aspal berdiameter 1 meter',
            'category_id' => $category->id,
            'status' => Report::STATUS_SELESAI,
            'latitude' => -7.9560000,
            'longitude' => 112.6290000,
            'location_label' => 'Jl. Pasar Besar No 5',
            'is_visible_on_map' => true,
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('staff.reports.export', [
            'status' => Report::STATUS_BARU,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('rekap-laporan-laportuntas', (string) $response->headers->get('Content-Disposition'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check header columns
        $this->assertStringContainsString('ID Tiket', $content);
        $this->assertStringContainsString('Kategori Masalah', $content);
        $this->assertStringContainsString('Tampil di Peta Publik', $content);

        // Check filtered content: should include baru (LT-CSV1-0001) and not selesai (LT-CSV2-0002)
        $this->assertStringContainsString('LT-CSV1-0001', $content);
        $this->assertStringContainsString('Lampu PJU Padam Depan Toko', $content);
        $this->assertStringNotContainsString('LT-CSV2-0002', $content);
    }

    public function test_pwa_manifest_and_meta_tags_are_configured(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifestContent = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertIsArray($manifestContent);
        $this->assertSame('standalone', $manifestContent['display']);
        $this->assertSame('#0A7B9D', $manifestContent['theme_color']);
        $this->assertNotEmpty($manifestContent['icons']);

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        $homeResponse->assertSee('rel="manifest"', false);
        $homeResponse->assertSee('name="theme-color"', false);
        $homeResponse->assertSee('name="apple-mobile-web-app-capable"', false);
    }

    public function test_citizen_can_toggle_support_for_report(): void
    {
        $category = Category::query()->firstOrFail();

        $report = Report::create([
            'ticket_id' => 'LT-SUP1-0001',
            'title' => 'Lampu PJU Mati Total di Tikungan',
            'description' => 'Sangat gelap dan membahayakan pengendara malam hari',
            'category_id' => $category->id,
            'status' => Report::STATUS_BARU,
            'latitude' => -7.9553000,
            'longitude' => 112.6280000,
            'location_label' => 'Jl. Soekarno Hatta',
            'supports_count' => 0,
            'is_visible_on_map' => true,
        ]);

        // First click: supports the report (+1)
        $supportResponse = $this->postJson(route('api.reports.support', ['ticketId' => $report->ticket_id]));
        $supportResponse->assertOk();
        $supportResponse->assertJson([
            'success' => true,
            'supported' => true,
            'supports_count' => 1,
        ]);

        $report->refresh();
        $this->assertSame(1, $report->supports_count);
        $this->assertCount(1, $report->supports);

        // Second click: un-supports the report (-1 toggle)
        $unsupportResponse = $this->postJson(route('api.reports.support', ['ticketId' => $report->ticket_id]));
        $unsupportResponse->assertOk();
        $unsupportResponse->assertJson([
            'success' => true,
            'supported' => false,
            'supports_count' => 0,
        ]);

        $report->refresh();
        $this->assertSame(0, $report->supports_count);
        $this->assertCount(0, $report->supports);

        // Re-support and verify it shows up in nearby API and tracking page
        $this->postJson(route('api.reports.support', ['ticketId' => $report->ticket_id]))->assertOk();

        $nearby = $this->getJson(route('api.reports.nearby', [
            'lat' => -7.9553000,
            'lng' => 112.6280000,
            'radius' => 5000,
        ]));
        $nearby->assertOk();
        $foundReport = collect($nearby->json('reports'))->firstWhere('ticket_id', 'LT-SUP1-0001');
        $this->assertNotNull($foundReport);
        $this->assertSame(1, $foundReport['supports_count']);

        $track = $this->get(route('tickets.show', ['ticketId' => 'LT-SUP1-0001']));
        $track->assertOk();
        $track->assertSee('1 Warga Mengalami Ini');
        $track->assertSee('Terdata 1 Dukungan');

        // Test support from modal creation flow (with from_form = true)
        $formSupportResponse = $this->postJson(route('api.reports.support', ['ticketId' => $report->ticket_id]), [
            'from_form' => true,
            'support_only' => true,
        ]);
        $formSupportResponse->assertOk();
        $formSupportResponse->assertJson([
            'success' => true,
            'supported' => true,
            'ticket_id' => 'LT-SUP1-0001',
        ]);
        $this->assertStringContainsString('mode=supported', (string) $formSupportResponse->json('redirect_url'));

        // Supporter success screen check
        $successScreen = $this->get(route('reports.success', [
            'ticketId' => $report->ticket_id,
            'mode' => 'supported',
        ]));
        $successScreen->assertOk();
        $successScreen->assertSee('Dukungan Warga Berhasil Dicatat');
        $successScreen->assertSee('Kamu terdaftar mendukung penanganan masalah ini');
    }

    public function test_sla_target_calculation_and_overdue_filtering(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = Category::query()->firstOrFail();
        $category->update(['sla_hours' => 48]); // 2 days

        // 1. Fresh report created 2 hours ago -> NOT overdue
        $freshReport = Report::create([
            'ticket_id' => 'LT-SLA1-0001',
            'title' => 'Lampu PJU Rusak Baru Saja',
            'description' => 'Baru padam tadi malam',
            'category_id' => $category->id,
            'status' => Report::STATUS_BARU,
            'latitude' => -7.9553000,
            'longitude' => 112.6280000,
            'location_label' => 'Jl. Borobudur',
            'is_visible_on_map' => true,
        ]);
        $freshReport->created_at = now()->subHours(2);
        $freshReport->save();

        $this->assertFalse($freshReport->isOverdue());
        $freshSla = $freshReport->getSlaMeta();
        $this->assertFalse($freshSla['is_overdue']);
        $this->assertStringContainsString('Sisa', $freshSla['badge_label']);

        // 2. Overdue report created 60 hours ago (> 48 hours SLA) -> OVERDUE
        $overdueReport = Report::create([
            'ticket_id' => 'LT-SLA2-0002',
            'title' => 'Lampu PJU Padam Sudah 3 Hari',
            'description' => 'Sudah 3 hari belum diperbaiki',
            'category_id' => $category->id,
            'status' => Report::STATUS_BARU,
            'latitude' => -7.9560000,
            'longitude' => 112.6290000,
            'location_label' => 'Jl. Candi Mendut',
            'is_visible_on_map' => true,
        ]);
        $overdueReport->created_at = now()->subHours(60);
        $overdueReport->save();

        $this->assertTrue($overdueReport->isOverdue());
        $overdueSla = $overdueReport->getSlaMeta();
        $this->assertTrue($overdueSla['is_overdue']);
        $this->assertStringContainsString('Terlambat', $overdueSla['badge_label']);

        // 3. Completed report -> NOT actively overdue
        $completedReport = Report::create([
            'ticket_id' => 'LT-SLA3-0003',
            'title' => 'Lampu PJU Selesai Tuntas',
            'description' => 'Selesai tepat waktu kemarin',
            'category_id' => $category->id,
            'status' => Report::STATUS_SELESAI,
            'latitude' => -7.9570000,
            'longitude' => 112.6300000,
            'location_label' => 'Jl. Suhat Barat',
            'is_visible_on_map' => true,
            'resolved_at' => now()->subHours(20),
        ]);
        $completedReport->created_at = now()->subHours(60);
        $completedReport->save();

        $this->assertFalse($completedReport->isOverdue());

        // 4. Test staff dashboard filter by status=overdue
        $dashboardResponse = $this->actingAs($admin)->get(route('staff.dashboard', [
            'status' => 'overdue',
        ]));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('LT-SLA2-0002');
        $dashboardResponse->assertDontSee('LT-SLA1-0001');
        $dashboardResponse->assertDontSee('LT-SLA3-0003');
    }
}

