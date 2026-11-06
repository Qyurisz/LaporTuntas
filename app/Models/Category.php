<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'icon', 'default_agency_id', 'sla_hours'])]
class Category extends Model
{
    /**
     * Get human-readable SLA target description (e.g. '2 hari' or '48 jam').
     */
    public function slaTargetHuman(): string
    {
        $hours = (int) ($this->sla_hours ?? 72);
        if ($hours >= 24 && $hours % 24 === 0) {
            $days = $hours / 24;
            return "{$days} hari ({$hours} jam)";
        }

        return "{$hours} jam";
    }

    /**
     * Get the default agency responsible for this category.
     */
    public function defaultAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'default_agency_id');
    }

    /**
     * Get the reports under this category.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}

