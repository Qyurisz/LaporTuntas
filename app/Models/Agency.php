<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'contact_info'])]
class Agency extends Model
{
    /**
     * Get the staff members belonging to this agency.
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    /**
     * Get the reports routed to this agency.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}

