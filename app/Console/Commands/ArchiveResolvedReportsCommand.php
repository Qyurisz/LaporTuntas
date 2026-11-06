<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reports:archive-resolved')]
#[Description('Arsipkan penanda laporan berstatus selesai yang berusia lebih dari 7 hari dari peta publik')]
class ArchiveResolvedReportsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(Report::RESOLVED_MAP_RETENTION_DAYS);

        $affectedCount = Report::query()
            ->where('status', Report::STATUS_SELESAI)
            ->where('is_visible_on_map', true)
            ->where(function ($query) use ($cutoff): void {
                $query->where('resolved_at', '<', $cutoff)
                    ->orWhere(function ($nullQuery) use ($cutoff): void {
                        $nullQuery->whereNull('resolved_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            })
            ->update([
                'is_visible_on_map' => false,
            ]);

        $this->info("Berhasil mengarsipkan {$affectedCount} laporan selesai (> " . Report::RESOLVED_MAP_RETENTION_DAYS . ' hari) dari peta publik.');
        $this->comment('Catatan: Data laporan tetap tersimpan abadi di database dan dapat dilacak kapan pun via ID tiket.');

        return self::SUCCESS;
    }
}
