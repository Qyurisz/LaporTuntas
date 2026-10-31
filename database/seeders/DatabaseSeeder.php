<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Category;
use App\Models\Region;
use App\Models\Report;
use App\Models\ReportMedia;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Region::query()->updateOrCreate(
            ['name' => 'Jakarta Pusat'],
            [
                'city' => 'DKI Jakarta',
                'center_lat' => -6.1753924,
                'center_lng' => 106.8271528,
            ]
        );

        $pu = Agency::query()->updateOrCreate(
            ['code' => 'DPU'],
            [
                'name' => 'Dinas Bina Marga & Pekerjaan Umum',
                'contact_info' => 'binamarga@pemkot.go.id | (021) 384-1001',
            ]
        );

        $dlh = Agency::query()->updateOrCreate(
            ['code' => 'DLH'],
            [
                'name' => 'Dinas Lingkungan Hidup (DLH)',
                'contact_info' => 'dlh@pemkot.go.id | (021) 384-1002',
            ]
        );

        $dishub = Agency::query()->updateOrCreate(
            ['code' => 'DISHUB'],
            [
                'name' => 'Dinas Perhubungan & PJU',
                'contact_info' => 'dishub@pemkot.go.id | (021) 384-1003',
            ]
        );

        $dsda = Agency::query()->updateOrCreate(
            ['code' => 'DSDA'],
            [
                'name' => 'Dinas Sumber Daya Air & Drainase',
                'contact_info' => 'dsda@pemkot.go.id | (021) 384-1004',
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@laportuntas.id'],
            [
                'name' => 'Moderator Lapor Tuntas',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'agency_id' => null,
            ]
        );

        $petugasPu = User::query()->updateOrCreate(
            ['email' => 'petugas.pu@laportuntas.id'],
            [
                'name' => 'Pak Heri (Petugas Lapangan Dinas PU)',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $pu->id,
            ]
        );

        $petugasDlh = User::query()->updateOrCreate(
            ['email' => 'petugas.dlh@laportuntas.id'],
            [
                'name' => 'Bu Sari (Petugas Lapangan DLH)',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $dlh->id,
            ]
        );

        $petugasDishub = User::query()->updateOrCreate(
            ['email' => 'petugas.dishub@laportuntas.id'],
            [
                'name' => 'Budi Santoso (Petugas Dishub)',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $dishub->id,
            ]
        );

        $categoriesData = [
            ['name' => 'Jalan rusak / berlubang', 'slug' => 'jalan-rusak', 'icon' => 'construction', 'agency_id' => $pu->id],
            ['name' => 'Lampu jalan mati', 'slug' => 'lampu-jalan', 'icon' => 'lightbulb', 'agency_id' => $dishub->id],
            ['name' => 'Sampah menumpuk', 'slug' => 'sampah-menumpuk', 'icon' => 'trash-2', 'agency_id' => $dlh->id],
            ['name' => 'Drainase / banjir', 'slug' => 'drainase-banjir', 'icon' => 'cloud-rain', 'agency_id' => $dsda->id],
            ['name' => 'Rambu / marka jalan', 'slug' => 'rambu-marka', 'icon' => 'traffic-cone', 'agency_id' => $dishub->id],
            ['name' => 'Fasilitas umum rusak', 'slug' => 'fasilitas-umum', 'icon' => 'wrench', 'agency_id' => $pu->id],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'icon' => 'map-pin', 'agency_id' => $pu->id],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'default_agency_id' => $cat['agency_id'],
                ]
            );
        }

        if (Report::query()->count() > 0) {
            return;
        }

        $demoReports = [
            [
                'ticket_id' => 'LT-7K9P-2M4X',
                'category' => $categories['jalan-rusak'],
                'agency_id' => $pu->id,
                'assigned_to' => $petugasPu->id,
                'title' => 'Lubang jalan cukup dalam di lajur kiri dekat penyeberangan',
                'description' => 'Ada lubang berdiameter sekitar 60 cm dengan kedalaman 15 cm di lajur kiri arah halte. Membahayakan pengendara motor terutama saat malam atau hujan.',
                'latitude' => -6.1738000,
                'longitude' => 106.8265000,
                'location_label' => 'Jl. Medan Merdeka Utara (300m dari titik pusat)',
                'status' => Report::STATUS_DIPROSES,
                'is_visible_on_map' => true,
                'svg_color' => '#F5B82E',
                'svg_title' => 'Foto Bukti: Jalan Berlubang',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan dikirim oleh warga melalui aplikasi web.', 'by' => null, 'mins_ago' => 180],
                    ['from' => 'baru', 'to' => 'ditugaskan', 'note' => 'Diteruskan ke Dinas Bina Marga & PU (Tim Reaksi Cepat).', 'by' => $admin->id, 'mins_ago' => 120],
                    ['from' => 'ditugaskan', 'to' => 'diproses', 'note' => 'Tim lapangan Pak Heri sedang melakukan penambalan aspal dingin (cold mix).', 'by' => $petugasPu->id, 'mins_ago' => 45],
                ],
            ],
            [
                'ticket_id' => 'LT-8F3K-92QX',
                'category' => $categories['lampu-jalan'],
                'agency_id' => $dishub->id,
                'assigned_to' => $petugasDishub->id,
                'title' => 'Tiga tiang lampu PJU padam total sepanjang trotoar',
                'description' => 'Lampu penerangan jalan umum mati sejak dua malam lalu sehingga trotoar sangat gelap bagi pejalan kaki yang pulang kerja.',
                'latitude' => -6.1769000,
                'longitude' => 106.8292000,
                'location_label' => 'Jl. Medan Merdeka Timur (Dekat Stasiun Gambir)',
                'status' => Report::STATUS_DITUGASKAN,
                'is_visible_on_map' => true,
                'svg_color' => '#7966D9',
                'svg_title' => 'Foto Bukti: Lampu PJU Padam',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan dikirim oleh warga tanpa akun.', 'by' => null, 'mins_ago' => 240],
                    ['from' => 'baru', 'to' => 'ditugaskan', 'note' => 'Ditugaskan ke teknisi PJU Dinas Perhubungan.', 'by' => $admin->id, 'mins_ago' => 90],
                ],
            ],
            [
                'ticket_id' => 'LT-4R8N-5V2W',
                'category' => $categories['sampah-menumpuk'],
                'agency_id' => $dlh->id,
                'assigned_to' => null,
                'title' => 'Tumpukan sampah liar meluber ke bahu jalan',
                'description' => 'Banyak kantong sampah rumah tangga menumpuk di dekat bak penampungan sementara dan mulai menimbulkan bau menyengat.',
                'latitude' => -6.1782000,
                'longitude' => 106.8241000,
                'location_label' => 'Jl. Kebon Sirih Barat',
                'status' => Report::STATUS_BARU,
                'is_visible_on_map' => true,
                'svg_color' => '#16A6D8',
                'svg_title' => 'Foto Bukti: Tumpukan Sampah',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan baru masuk dan menunggu verifikasi penugasan.', 'by' => null, 'mins_ago' => 35],
                ],
            ],
            [
                'ticket_id' => 'LT-6M2C-9T4L',
                'category' => $categories['drainase-banjir'],
                'agency_id' => $dsda->id,
                'assigned_to' => null,
                'title' => 'Saluran tali air tersumbat sedimen dan daun kering',
                'description' => 'Air hujan menggenang setinggi mata kaki karena mulut saluran drainase tertutup sampah plastik dan lumpur.',
                'latitude' => -6.1725000,
                'longitude' => 106.8298000,
                'location_label' => 'Jl. Perwira',
                'status' => Report::STATUS_BARU,
                'is_visible_on_map' => true,
                'svg_color' => '#16A6D8',
                'svg_title' => 'Foto Bukti: Drainase Tersumbat',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan warga berhasil tercatat di sistem.', 'by' => null, 'mins_ago' => 65],
                ],
            ],
            [
                'ticket_id' => 'LT-9H5B-3K7D',
                'category' => $categories['fasilitas-umum'],
                'agency_id' => $pu->id,
                'assigned_to' => $petugasPu->id,
                'title' => 'Paving block trotoar ambles dan besi penutup gorong-gorong renggang',
                'description' => 'Bagian trotoar ramah disabilitas (guiding block) terangkat dan berisiko membuat pejalan kaki tersandung.',
                'latitude' => -6.1761000,
                'longitude' => 106.8229000,
                'location_label' => 'Jl. Museum (Sisi Barat)',
                'status' => Report::STATUS_DIPROSES,
                'is_visible_on_map' => true,
                'svg_color' => '#F5B82E',
                'svg_title' => 'Foto Bukti: Trotoar Ambles',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan dikirim oleh warga.', 'by' => null, 'mins_ago' => 310],
                    ['from' => 'baru', 'to' => 'ditugaskan', 'note' => 'Ditugaskan ke Dinas Bina Marga.', 'by' => $admin->id, 'mins_ago' => 210],
                    ['from' => 'ditugaskan', 'to' => 'diproses', 'note' => 'Material paving baru sudah tiba di lokasi dan sedang dipasang.', 'by' => $petugasPu->id, 'mins_ago' => 50],
                ],
            ],
            [
                'ticket_id' => 'LT-2W9J-4P6Z',
                'category' => $categories['rambu-marka'],
                'agency_id' => $dishub->id,
                'assigned_to' => $petugasDishub->id,
                'title' => 'Tiang rambu dilarang parkir miring tertabrak kendaraan',
                'description' => 'Tiang rambu di tikungan miring ke arah badan jalan dan berpotensi mengenai kendaraan tinggi.',
                'latitude' => -6.1795000,
                'longitude' => 106.8278000,
                'location_label' => 'Simpang Jl. Medan Merdeka Selatan',
                'status' => Report::STATUS_DITUGASKAN,
                'is_visible_on_map' => true,
                'svg_color' => '#7966D9',
                'svg_title' => 'Foto Bukti: Rambu Miring',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan masuk dari warga.', 'by' => null, 'mins_ago' => 150],
                    ['from' => 'baru', 'to' => 'ditugaskan', 'note' => 'Diteruskan ke unit perlengkapan jalan Dishub.', 'by' => $admin->id, 'mins_ago' => 80],
                ],
            ],
            [
                'ticket_id' => 'LT-5X8Q-7N3M',
                'category' => $categories['jalan-rusak'],
                'agency_id' => $pu->id,
                'assigned_to' => $petugasPu->id,
                'title' => 'Perbaikan lubang aspal di depan halte busway telah selesai',
                'description' => 'Lubang jalan di depan halte sudah ditambal rata oleh petugas lapangan dan aman dilalui kembali.',
                'latitude' => -6.1749000,
                'longitude' => 106.8235000,
                'location_label' => 'Jl. Medan Merdeka Barat (Halte Monas)',
                'status' => Report::STATUS_SELESAI,
                'is_visible_on_map' => false,
                'resolved_at' => now()->subHours(2),
                'svg_color' => '#278653',
                'svg_title' => 'Foto Sebelum: Jalan Berlubang',
                'after_svg_title' => 'Foto Sesudah: Aspal Sudah Ditambal Rata',
                'histories' => [
                    ['from' => null, 'to' => 'baru', 'note' => 'Laporan dikirim oleh warga.', 'by' => null, 'mins_ago' => 540],
                    ['from' => 'baru', 'to' => 'ditugaskan', 'note' => 'Ditugaskan ke Pak Heri (Dinas PU).', 'by' => $admin->id, 'mins_ago' => 420],
                    ['from' => 'ditugaskan', 'to' => 'diproses', 'note' => 'Pengerjaan pengaspalan ulang titik berlubang.', 'by' => $petugasPu->id, 'mins_ago' => 240],
                    ['from' => 'diproses', 'to' => 'selesai', 'note' => 'Perbaikan telah selesai 100%, aspal sudah dipadatkan dan marka dirapikan.', 'by' => $petugasPu->id, 'mins_ago' => 120],
                ],
            ],
        ];

        foreach ($demoReports as $item) {
            $report = Report::query()->create([
                'ticket_id' => $item['ticket_id'],
                'category_id' => $item['category']->id,
                'agency_id' => $item['agency_id'],
                'assigned_to' => $item['assigned_to'],
                'title' => $item['title'],
                'description' => $item['description'],
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude'],
                'location_label' => $item['location_label'],
                'status' => $item['status'],
                'is_visible_on_map' => $item['is_visible_on_map'],
                'resolved_at' => $item['resolved_at'] ?? null,
                'created_at' => now()->subMinutes($item['histories'][0]['mins_ago']),
                'updated_at' => now()->subMinutes(end($item['histories'])['mins_ago']),
            ]);

            ReportMedia::query()->create([
                'report_id' => $report->id,
                'type' => 'before',
                'file_path' => self::makeDemoSvgDataUri($item['svg_title'], $item['location_label'], $item['svg_color']),
                'mime_type' => 'image/svg+xml',
                'file_size' => 2048,
            ]);

            if (! empty($item['after_svg_title'])) {
                ReportMedia::query()->create([
                    'report_id' => $report->id,
                    'type' => 'after',
                    'file_path' => self::makeDemoSvgDataUri($item['after_svg_title'], 'Kondisi Setelah Ditangani Petugas', '#278653'),
                    'mime_type' => 'image/svg+xml',
                    'file_size' => 2048,
                ]);
            }

            foreach ($item['histories'] as $history) {
                StatusHistory::query()->create([
                    'report_id' => $report->id,
                    'from_status' => $history['from'],
                    'to_status' => $history['to'],
                    'note' => $history['note'],
                    'changed_by' => $history['by'],
                    'created_at' => now()->subMinutes($history['mins_ago']),
                ]);
            }
        }
    }

    /**
     * Create a clean SVG data URI illustration for seeded demo reports.
     */
    public static function makeDemoSvgDataUri(string $title, string $subtitle, string $accentHex): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">
  <rect width="800" height="500" fill="#17324D"/>
  <circle cx="680" cy="110" r="140" fill="{$accentHex}" opacity="0.22"/>
  <circle cx="120" cy="420" r="180" fill="#16A6D8" opacity="0.18"/>
  <rect x="48" y="48" width="704" height="404" rx="24" fill="#204160" stroke="{$accentHex}" stroke-width="3"/>
  <rect x="80" y="84" width="210" height="38" rx="19" fill="{$accentHex}"/>
  <text x="185" y="109" text-anchor="middle" fill="#FFFFFF" font-family="sans-serif" font-weight="bold" font-size="15">DATA SIMULASI DEMO</text>
  <text x="80" y="220" fill="#FFFFFF" font-family="sans-serif" font-weight="bold" font-size="30">{$safeTitle}</text>
  <text x="80" y="268" fill="#DFF5FC" font-family="sans-serif" font-size="20">{$safeSubtitle}</text>
  <text x="80" y="395" fill="#9BB4C9" font-family="monospace" font-size="16">Lapor Tuntas — Dokumentasi Lapangan Terverifikasi GPS</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
