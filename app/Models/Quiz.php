<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz extends Model
{
    use HasFactory;

    protected $table = "quizzes";

    protected $fillable = [
        'question',
        'answers',
        'correct',
        'sub_content_id',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

}
