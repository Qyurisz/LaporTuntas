<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Report;
use App\Models\ReportSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSupportModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_support_can_store_device_token_photo_and_comment(): void
    {
        $category = Category::query()->create([
            'name' => 'Jalan Rusak',
            'slug' => 'jalan-rusak',
            'icon' => 'alert-triangle',
            'sla_hours' => 72,
        ]);

        $report = Report::query()->create([
            'ticket_id' => 'LT-TEST-0001',
            'category_id' => $category->id,
            'title' => 'Jalan Rusak',
            'description' => 'Lubang di tengah jalan',
            'latitude' => -7.95000,
            'longitude' => 112.63000,
            'location_label' => 'Jl. Test',
            'status' => Report::STATUS_BARU,
            'is_visible_on_map' => true,
        ]);

        $support = ReportSupport::query()->create([
            'report_id' => $report->id,
            'device_token' => 'device-uuid-12345',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'photo_path' => 'reports/supports/test-evidence.jpg',
            'comment' => 'Kondisi lubang makin dalam setelah hujan.',
        ]);

        $this->assertDatabaseHas('report_supports', [
            'id' => $support->id,
            'device_token' => 'device-uuid-12345',
            'comment' => 'Kondisi lubang makin dalam setelah hujan.',
        ]);

        $this->assertStringContainsString('test-evidence.jpg', (string) $support->photoUrl());
        $this->assertCount(1, $report->supportsWithPhotos);
        $this->assertCount(1, $report->supportsWithComments);
    }
}
