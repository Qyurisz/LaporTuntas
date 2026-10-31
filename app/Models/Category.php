<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'icon', 'default_agency_id'])]
class Category extends Model
{
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

