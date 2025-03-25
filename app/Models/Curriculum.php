<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'lesson',
        'unit',
        'file_path',
        'prompt',
        'pdf_content'
    ];

    public function h5ps()
    {
        return $this->hasMany(H5P::class);
    }
}
