<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_id', 'device_token', 'ip_hash', 'user_agent_hash', 'photo_path', 'comment'])]
class ReportSupport extends Model
{
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the public URL of the uploaded supporter evidence photo.
     */
    public function photoUrl(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'data:') || str_starts_with($this->photo_path, 'http') || str_starts_with($this->photo_path, '/')) {
            return $this->photo_path;
        }

        return '/media/' . ltrim($this->photo_path, '/');
    }

    /**
     * Get the report supported by this citizen vote.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}

