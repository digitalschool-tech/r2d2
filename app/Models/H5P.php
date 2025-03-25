<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class H5P extends Model
{
    use HasFactory;

    protected $table = "h5_p_s";

    protected $fillable = [
        'prompt',
        'filename',
        'feedback',
        'rating',
        'curriculum_id',
        'course_id',
        'section_id',
        'gpt_response',
        'view_url',
        'cmid'
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }
}
