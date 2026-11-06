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
    'supports_count',
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
     * Number of days a completed ('selesai') report remains visible on the public map
     * before being automatically hidden to prevent marker clutter.
     * The report remains permanently archived in the database and searchable via Ticket ID.
     */
    public const RESOLVED_MAP_RETENTION_DAYS = 7;

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
            'supports_count' => 'integer',
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
     * Check whether this report should be visible on the public map.
     * Active reports (baru, ditugaskan, diproses) are visible as long as is_visible_on_map is true.
     * Completed reports ('selesai') are only visible within the 7-day retention grace period.
     */
    public function isVisibleOnPublicMap(): bool
    {
        if (! $this->is_visible_on_map) {
            return false;
        }

        if ($this->status === self::STATUS_DITOLAK) {
            return false;
        }

        if ($this->status === self::STATUS_SELESAI) {
            $resolvedDate = $this->resolved_at ?? $this->updated_at;
            if ($resolvedDate === null) {
                return false;
            }

            return $resolvedDate->isAfter(now()->subDays(self::RESOLVED_MAP_RETENTION_DAYS));
        }

        return true;
    }

    /**
     * Scope reports that are visible on the public map.
     * Reports with 'selesai' status are auto-hidden after 7 days from completion
     * to prevent map clutter, while remaining permanently archived in the DB.
     */
    public function scopeActiveOnMap(Builder $query): Builder
    {
        $cutoff = now()->subDays(self::RESOLVED_MAP_RETENTION_DAYS);

        return $query
            ->where('is_visible_on_map', true)
            ->where(function (Builder $q) use ($cutoff): void {
                $q->whereIn('status', [
                    self::STATUS_BARU,
                    self::STATUS_DITUGASKAN,
                    self::STATUS_DIPROSES,
                ])->orWhere(function (Builder $sub) use ($cutoff): void {
                    $sub->where('status', self::STATUS_SELESAI)
                        ->where(function (Builder $dateQuery) use ($cutoff): void {
                            $dateQuery->where('resolved_at', '>=', $cutoff)
                                ->orWhere(function (Builder $nullResolved) use ($cutoff): void {
                                    $nullResolved->whereNull('resolved_at')
                                        ->where('updated_at', '>=', $cutoff);
                                });
                        });
                });
            });
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
     * Get citizen community supports for this report.
     */
    public function supports(): HasMany
    {
        return $this->hasMany(ReportSupport::class);
    }

    /**
     * Get citizen community supports that include uploaded photo evidence.
     */
    public function supportsWithPhotos(): HasMany
    {
        return $this->hasMany(ReportSupport::class)->whereNotNull('photo_path')->latest('id');
    }

    /**
     * Get citizen community supports that include witness notes/comments.
     */
    public function supportsWithComments(): HasMany
    {
        return $this->hasMany(ReportSupport::class)->whereNotNull('comment')->latest('id');
    }

    /**
     * Get the status change timeline histories ordered chronologically.
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Get SLA target hours for this report based on its category (fallback 72 hours).
     */
    public function getSlaHours(): int
    {
        return (int) ($this->category?->sla_hours ?? 72);
    }

    /**
     * Get the SLA completion deadline datetime.
     */
    public function getSlaDeadline(): \Carbon\CarbonInterface
    {
        return $this->created_at ? $this->created_at->copy()->addHours($this->getSlaHours()) : now();
    }

    /**
     * Determine whether an active report has exceeded its SLA resolution deadline.
     * Resolved or rejected reports are not actively overdue.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_SELESAI, self::STATUS_DITOLAK], true)) {
            return false;
        }

        return now()->isAfter($this->getSlaDeadline());
    }

    /**
     * For resolved reports, check whether it was resolved overdue.
     */
    public function wasResolvedOverdue(): ?bool
    {
        if ($this->status !== self::STATUS_SELESAI) {
            return null;
        }

        $resolvedAt = $this->resolved_at ?? $this->updated_at;
        if (! $resolvedAt) {
            return false;
        }

        return $resolvedAt->isAfter($this->getSlaDeadline());
    }

    /**
     * Get SLA metadata including remaining time, deadline, and human labels.
     *
     * @return array{target_hours: int, target_label: string, deadline: \Carbon\CarbonInterface, deadline_label: string, is_overdue: bool, badge_label: string, badge_type: string}
     */
    public function getSlaMeta(): array
    {
        $targetHours = $this->getSlaHours();
        $targetDays = $targetHours >= 24 && $targetHours % 24 === 0 ? ($targetHours / 24) . ' hari' : "{$targetHours} jam";
        $deadline = $this->getSlaDeadline();
        $deadlineLabel = $deadline->translatedFormat('d M Y, H:i');

        if ($this->status === self::STATUS_SELESAI) {
            $overdue = $this->wasResolvedOverdue();
            return [
                'target_hours' => $targetHours,
                'target_label' => $targetDays,
                'deadline' => $deadline,
                'deadline_label' => $deadlineLabel,
                'is_overdue' => false,
                'badge_label' => $overdue ? 'Selesai (Terlambat SLA)' : 'Selesai tepat waktu',
                'badge_type' => $overdue ? 'warning' : 'success',
            ];
        }

        if ($this->status === self::STATUS_DITOLAK) {
            return [
                'target_hours' => $targetHours,
                'target_label' => $targetDays,
                'deadline' => $deadline,
                'deadline_label' => $deadlineLabel,
                'is_overdue' => false,
                'badge_label' => 'Ditolak',
                'badge_type' => 'neutral',
            ];
        }

        $now = now();
        $isOverdue = $now->isAfter($deadline);

        if ($isOverdue) {
            $diffHours = (int) $deadline->diffInHours($now);
            $diffDays = (int) $deadline->diffInDays($now);
            $lateText = $diffDays >= 1 ? "Terlambat {$diffDays} hari" : "Terlambat {$diffHours} jam";

            return [
                'target_hours' => $targetHours,
                'target_label' => $targetDays,
                'deadline' => $deadline,
                'deadline_label' => $deadlineLabel,
                'is_overdue' => true,
                'badge_label' => $lateText,
                'badge_type' => 'danger',
            ];
        }

        $remainingHours = (int) $now->diffInHours($deadline, false);
        $remainingDays = (int) $now->diffInDays($deadline, false);
        $remainingText = $remainingDays >= 1 ? "Sisa {$remainingDays} hari" : "Sisa {$remainingHours} jam";

        return [
            'target_hours' => $targetHours,
            'target_label' => $targetDays,
            'deadline' => $deadline,
            'deadline_label' => $deadlineLabel,
            'is_overdue' => false,
            'badge_label' => $remainingText,
            'badge_type' => $remainingHours <= 12 ? 'warning' : 'info',
        ];
    }

    /**
     * Scope query to find active reports that have exceeded their SLA resolution deadline.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        static $cachedCategories = null;
        if ($cachedCategories === null || app()->runningUnitTests()) {
            $cachedCategories = Category::query()->get(['id', 'sla_hours']);
        }

        return $query
            ->whereIn('status', [
                self::STATUS_BARU,
                self::STATUS_DITUGASKAN,
                self::STATUS_DIPROSES,
            ])
            ->where(function (Builder $q) use ($cachedCategories): void {
                if ($cachedCategories->isEmpty()) {
                    $q->where('created_at', '<', now()->subHours(72));
                    return;
                }

                foreach ($cachedCategories as $index => $cat) {
                    $cutoff = now()->subHours((int) ($cat->sla_hours ?? 72));
                    $closure = function (Builder $sub) use ($cat, $cutoff): void {
                        $sub->where('category_id', $cat->id)
                            ->where('created_at', '<', $cutoff);
                    };

                    if ($index === 0) {
                        $q->where($closure);
                    } else {
                        $q->orWhere($closure);
                    }
                }
            });
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
                'label' => 'Belum diproses',
                'color' => '#0A7B9D',
                'bg' => '#DFF5FC',
                'text' => '#075F79',
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
                'label' => 'Diproses',
                'color' => '#F5B82E',
                'bg' => '#FEF5DC',
                'text' => '#8A5D00',
                'icon' => 'hammer',
                'description' => 'Petugas sedang menangani perbaikan di lokasi laporan.',
            ],
            self::STATUS_SELESAI => [
                'label' => 'Selesai',
                'color' => '#278653',
                'bg' => '#E1F5EA',
                'text' => '#185B37',
                'icon' => 'check-circle-2',
                'description' => 'Penanganan laporan telah selesai ditindaklanjuti hingga tuntas oleh petugas.',
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
                'color' => '#0A7B9D',
                'bg' => '#DFF5FC',
                'text' => '#17324D',
                'icon' => 'info',
                'description' => 'Status laporan publik.',
            ],
        };
    }
}
