<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_id', 'type', 'file_path', 'mime_type', 'file_size'])]
class ReportMedia extends Model
{
    protected $table = 'report_media';

    /**
     * Get the report that owns the media.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the public URL for displaying the image.
     */
    public function publicUrl(): string
    {
        if (str_starts_with($this->file_path, 'data:') || str_starts_with($this->file_path, 'http') || str_starts_with($this->file_path, '/')) {
            return $this->file_path;
        }

        return '/media/'.ltrim($this->file_path, '/');
    }
}

