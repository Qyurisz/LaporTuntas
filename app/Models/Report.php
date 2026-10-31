<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ticket_id',
    'category_id',
    'agency_id',
    'assigned_to',
    'title',
    'description',
    'latitude',
    'longitude',
    'location_label',
    'status',
    'rejection_reason',
    'is_visible_on_map',
    'reporter_ip_hash',
    'resolved_at',
])]
class Report extends Model
{
    public const STATUS_BARU = 'baru';
    public const STATUS_DITUGASKAN = 'ditugaskan';
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITOLAK = 'ditolak';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_visible_on_map' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Generate a random non-sequential Ticket ID (e.g. LT-8F3K-92QX).
     */
    public static function generateTicketId(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;

        do {
            $partA = '';
            $partB = '';
            for ($i = 0; $i < 4; $i++) {
                $partA .= $alphabet[random_int(0, $maxIndex)];
                $partB .= $alphabet[random_int(0, $maxIndex)];
            }
            $ticketId = "LT-{$partA}-{$partB}";
        } while (self::query()->where('ticket_id', $ticketId)->exists());

        return $ticketId;
    }

    /**
     * Calculate Haversine distance in meters between two coordinates.
     */
    public static function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    /**
     * Scope reports that are currently active and visible on the public map.
     */
    public function scopeActiveOnMap(Builder $query): Builder
    {
        return $query
            ->where('is_visible_on_map', true)
            ->whereIn('status', [
                self::STATUS_BARU,
                self::STATUS_DITUGASKAN,
                self::STATUS_DIPROSES,
            ]);
    }

    /**
     * Scope reports within a bounding box for fast spatial pre-filtering.
     */
    public function scopeWithinBoundingBox(Builder $query, float $lat, float $lng, int $radiusMeters): Builder
    {
        $latDelta = $radiusMeters / 111320;
        $cosLat = max(0.01, abs(cos(deg2rad($lat))));
        $lngDelta = $radiusMeters / (111320 * $cosLat);

        return $query
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
    }

    /**
     * Get the category of the report.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the agency handling the report.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Get the staff officer assigned to the report.
     */
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all media attachments (before and after photos).
     */
    public function media(): HasMany
    {
        return $this->hasMany(ReportMedia::class);
    }

    /**
     * Get the status change timeline histories ordered chronologically.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Get visual status metadata conforming to the Style Guide v1.0.
     *
     * @return array{label: string, color: string, bg: string, text: string, icon: string, description: string}
     */
    public function statusMeta(): array
    {
        return self::getStatusMetadata($this->status);
    }

    /**
     * Return status metadata by status key.
     *
     * @return array{label: string, color: string, bg: string, text: string, icon: string, description: string}
     */
    public static function getStatusMetadata(string $status): array
    {
        return match ($status) {
            self::STATUS_BARU => [
                'label' => 'Baru',
                'color' => '#16A6D8',
                'bg' => '#DFF5FC',
                'text' => '#0B5E7C',
                'icon' => 'sparkles',
                'description' => 'Laporan baru masuk dan menunggu penugasan ke instansi terkait.',
            ],
            self::STATUS_DITUGASKAN => [
                'label' => 'Ditugaskan',
                'color' => '#7966D9',
                'bg' => '#EFE9FC',
                'text' => '#4D3BA8',
                'icon' => 'user-check',
                'description' => 'Laporan telah diteruskan ke instansi dan petugas lapangan.',
            ],
            self::STATUS_DIPROSES => [
                'label' => 'Sedang diproses',
                'color' => '#F5B82E',
                'bg' => '#FEF5DC',
                'text' => '#8A5D00',
                'icon' => 'hammer',
                'description' => 'Petugas sedang menangani perbaikan di lokasi laporan.',
            ],
            self::STATUS_SELESAI => [
                'label' => 'Tuntas / Selesai',
                'color' => '#278653',
                'bg' => '#E1F5EA',
                'text' => '#185B37',
                'icon' => 'check-circle-2',
                'description' => 'Penanganan selesai dan penanda telah ditutup dari peta aktif.',
            ],
            self::STATUS_DITOLAK => [
                'label' => 'Ditolak',
                'color' => '#697782',
                'bg' => '#E9EEF1',
                'text' => '#3A464E',
                'icon' => 'x-circle',
                'description' => 'Laporan tidak dapat diteruskan karena tidak valid atau di luar kewenangan.',
            ],
            default => [
                'label' => ucfirst($status),
                'color' => '#16A6D8',
                'bg' => '#DFF5FC',
                'text' => '#17324D',
                'icon' => 'info',
                'description' => 'Status laporan publik.',
            ],
        };
    }
}
