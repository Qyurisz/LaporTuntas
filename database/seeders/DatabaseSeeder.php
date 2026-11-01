<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed essential master operational data (agencies, categories, and RBAC staff accounts).
     * Citizen reports, media, and status histories start completely clean (0 records).
     */
    public function run(): void
    {
        $pu = Agency::query()->updateOrCreate(
            ['code' => 'DPU'],
            [
                'name' => 'Dinas Bina Marga & Pekerjaan Umum',
                'contact_info' => null,
            ]
        );

        $dlh = Agency::query()->updateOrCreate(
            ['code' => 'DLH'],
            [
                'name' => 'Dinas Lingkungan Hidup (DLH)',
                'contact_info' => null,
            ]
        );

        $dishub = Agency::query()->updateOrCreate(
            ['code' => 'DISHUB'],
            [
                'name' => 'Dinas Perhubungan & PJU',
                'contact_info' => null,
            ]
        );

        $dsda = Agency::query()->updateOrCreate(
            ['code' => 'DSDA'],
            [
                'name' => 'Dinas Sumber Daya Air & Drainase',
                'contact_info' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@laportuntas.id'],
            [
                'name' => 'Moderator Lapor Tuntas',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'agency_id' => null,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'petugas.pu@laportuntas.id'],
            [
                'name' => 'Petugas Dinas Bina Marga & PU',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $pu->id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'petugas.dlh@laportuntas.id'],
            [
                'name' => 'Petugas Dinas Lingkungan Hidup',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $dlh->id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'petugas.dishub@laportuntas.id'],
            [
                'name' => 'Petugas Dinas Perhubungan & PJU',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $dishub->id,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'petugas.dsda@laportuntas.id'],
            [
                'name' => 'Petugas Dinas SDA & Drainase',
                'password' => Hash::make('password'),
                'role' => 'petugas',
                'agency_id' => $dsda->id,
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

        foreach ($categoriesData as $cat) {
            Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'default_agency_id' => $cat['agency_id'],
                ]
            );
        }
    }
}
