# Smart Duplicate Detection, Supporter Evidence Gallery & Anonymous Vote Protection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent duplicate reports of the same category within a 30m radius by seamlessly converting them into citizen support with additional evidence photos and comments, while allowing new reports for different categories and enforcing anonymous double-voting protection via persistent device tokens and network IP hashes.

**Architecture:** A multi-layered defense combining client-side persistent device tokens (`localStorage` + cookie) and backend geospatial checks. Submissions within 30m of an active report with the exact same category automatically attach the uploaded photo and note as a `ReportSupport` record to the existing parent report, enriching the report's photo gallery and citizen timeline, while submissions with different categories create a new independent ticket.

**Tech Stack:** Laravel 11, PHP 8.3, Alpine.js, Tailwind CSS, Leaflet.js, SQLite/MySQL.

**Spec:** Chat design spec approved on 2026-11-06 (Section: Short Design Specification for Smart Duplicate Detection and Support Evidence).

## Global Constraints

- PHP 8.3 with strict types and explicit return types on methods.
- Follow existing codebase patterns and design system tokens (`#0A7B9D` primary).
- Zero mandatory user login / PII requirements for citizens.
- All tests must pass via `php artisan test`.

## Review Focus

1. Submitting a report with a different category at the exact same coordinates must create a new ticket, not merge.
2. Submitting a report with the same category within 30m must merge into support and award the existing ticket ID.
3. Supporter uploaded photos must be saved to public storage and visible in the ticket tracking gallery.
4. Second vote from the same device token or IP hash must be blocked without double-incrementing `supports_count`.
5. Mobile and desktop UI must provide immediate, clear feedback distinguishing same-category merge vs different-category creation.

---

### Task 1: Database Migration & Eloquent Model Enhancements for `ReportSupport`

**Files:**
- Create: `database/migrations/2026_11_06_000001_add_device_token_and_evidence_to_report_supports.php`
- Modify: `app/Models/ReportSupport.php`
- Modify: `app/Models/Report.php`
- Test: `tests/Feature/ReportSupportModelTest.php`

**Interfaces:**
- Consumes: Existing `ReportSupport` and `Report` models.
- Produces: 
  - `ReportSupport::$device_token`, `ReportSupport::$photo_path`, `ReportSupport::$comment`.
  - `ReportSupport::photoUrl(): ?string`
  - `Report::supportsWithPhotos(): HasMany`
  - `Report::supportsWithComments(): HasMany`

- [ ] **Step 1: Write the failing model & migration test**

Create `tests/Feature/ReportSupportModelTest.php`:
```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReportSupportModelTest`
Expected: FAIL due to missing columns `device_token`, `photo_path`, `comment`.

- [ ] **Step 3: Create migration and update models**

Create migration `database/migrations/2026_11_06_000001_add_device_token_and_evidence_to_report_supports.php`:
- Add `device_token` (string 64, nullable, indexed).
- Add `photo_path` (string 255, nullable).
- Add `comment` (text, nullable).
- Update unique index to include device token: `$table->unique(['report_id', 'device_token'], 'report_supports_unique_device');`

Update `app/Models/ReportSupport.php`:
- Add `device_token`, `photo_path`, `comment` to `#[Fillable]`.
- Implement `public function photoUrl(): ?string`.

Update `app/Models/Report.php`:
- Implement `public function supportsWithPhotos(): HasMany` (filtered by `whereNotNull('photo_path')`).
- Implement `public function supportsWithComments(): HasMany` (filtered by `whereNotNull('comment')`).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ReportSupportModelTest`
Expected: PASS

---

### Task 2: Backend Smart Proximity Check & Auto-Merge in `ReportController` & `ReportSupportController`

**Files:**
- Modify: `app/Http/Controllers/ReportController.php`
- Modify: `app/Http/Controllers/ReportSupportController.php`
- Test: `tests/Feature/ReportDuplicateDetectionTest.php`

**Interfaces:**
- Consumes: `ReportController::store()`, `ReportSupportController::toggle()`.
- Produces:
  - Smart merge: If active report with same `category_id` exists <= 30m, store photo and note into `ReportSupport`, increment supports count, redirect to `reports.success` with `mode=supported`.
  - Independent creation: If category is different or distance > 30m, proceed to create new report.
  - Double vote protection: Reject duplicate votes from matching `device_token` OR `ip_hash`.

- [ ] **Step 1: Write the failing feature tests**

Create `tests/Feature/ReportDuplicateDetectionTest.php`:
- Test 1: Submitting same category within 30m merges into existing ticket, creates `ReportSupport` with photo and comment, and does not create a new `Report`.
- Test 2: Submitting different category within 30m creates a brand new `Report` with unique ticket ID.
- Test 3: Submitting support with already used `device_token` returns `already_supported = true` and does not increment `supports_count`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReportDuplicateDetectionTest`
Expected: FAIL

- [ ] **Step 3: Implement proximity detection & auto-merge logic in `ReportController`**

In `app/Http/Controllers/ReportController.php`:
- Check for existing active report:
  ```php
  $existingReport = Report::query()
      ->where('category_id', $validated['category_id'])
      ->whereIn('status', [Report::STATUS_BARU, Report::STATUS_DITUGASKAN, Report::STATUS_DIPROSES])
      ->select('*')
      ->selectRaw('(6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance_meters', [
          $validated['latitude'],
          $validated['longitude'],
          $validated['latitude'],
      ])
      ->having('distance_meters', '<=', 30)
      ->orderBy('distance_meters')
      ->first();
  ```
- If `$existingReport` exists:
  - Extract `device_token` (`$request->input('device_token') ?? $request->cookie('lt_device_token') ?? Str::uuid()->toString()`).
  - Store uploaded photo (if any) to `reports/supports`.
  - Check if `device_token` or `ip_hash` already exists in `ReportSupport`.
  - If not, create `ReportSupport` record and increment `supports_count`.
  - Set cookie `lt_device_token` with 1-year duration.
  - Return redirect to `reports.success` with `mode=supported` and informative flash message.
- If `$existingReport` does NOT exist:
  - Proceed with standard new report creation as before.

In `app/Http/Controllers/ReportSupportController.php`:
- Add `device_token` check: query for existing record matching either `ip_hash` OR `device_token` (when provided).
- Support optional `comment` and uploaded `photo` in support request.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ReportDuplicateDetectionTest`
Expected: PASS

---

### Task 3: Detail Pages UI (Supporter Evidence Gallery & Citizen Notes Timeline)

**Files:**
- Modify: `resources/views/tickets/track.blade.php`
- Modify: `resources/views/staff/show.blade.php`
- Modify: `app/Http/Controllers/TicketController.php` (load `supportsWithPhotos` and `supportsWithComments`)
- Modify: `app/Http/Controllers/StaffDashboardController.php` (eager load supports with photos/comments)
- Test: `tests/Feature/ReportDetailGalleryTest.php`

**Interfaces:**
- Consumes: `$report->supportsWithPhotos`, `$report->supportsWithComments`.
- Produces:
  - Responsive gallery showing additional citizen photos under a dedicated badge "Foto Tambahan dari Warga".
  - Timeline list showing citizen notes under "Catatan & Suara Warga".

- [ ] **Step 1: Write test verifying supporter photos and comments render on tracking page**

Create `tests/Feature/ReportDetailGalleryTest.php`:
- Create report with 1 support having `photo_path` and `comment`.
- Assert tracking page `GET /tiket/{ticketId}` displays supporter comment and image source.
- Assert staff show page `GET /petugas/laporan/{id}` displays supporter comment and image.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReportDetailGalleryTest`
Expected: FAIL

- [ ] **Step 3: Update `TicketController`, `StaffDashboardController`, and Blade Views**

- In `TicketController.php`: Eager load `supportsWithPhotos` and `supportsWithComments`.
- In `resources/views/tickets/track.blade.php`:
  - Add "Galeri Bukti Tambahan dari Warga ({{ $report->supportsWithPhotos->count() }})" section.
  - Add "Suara & Catatan Warga ({{ $report->supportsWithComments->count() }})" section.
- In `resources/views/staff/show.blade.php`:
  - Display supporter photos grid in the evidence tab.
  - Display supporter comments in the incident details.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ReportDetailGalleryTest`
Expected: PASS

---

### Task 4: Home Page Form Integration (Client Device Token & Proximity Feedback)

**Files:**
- Modify: `resources/views/home.blade.php`
- Modify: `resources/views/reports/ticket-success.blade.php`
- Test: `tests/Feature/ReportFlowTest.php`

**Interfaces:**
- Consumes: `getOrCreateDeviceId()`, Alpine `createReportForm` state.
- Produces:
  - Form carries `<input type="hidden" name="device_token" :value="deviceId">`.
  - Dynamic alert in create report modal when selected coordinates & category match an active report within 30m:
    - Same category: Alert explains report will be merged as support + evidence, button dynamically labeled *"Kirim Bukti & Dukung Laporan"*.
    - Different category: Alert reassures citizen *"Kategori berbeda dari laporan terdekat. Laporan baru akan dibuat."*, button remains *"Kirim Laporan"*.

- [ ] **Step 1: Update `home.blade.php` with device token generator and dynamic category comparison**
- [ ] **Step 2: Update `ticket-success.blade.php` with clearer messaging when report was auto-merged**
- [ ] **Step 3: Run full test suite to verify no regressions**

Run: `php artisan test`
Expected: 100% tests passing.

