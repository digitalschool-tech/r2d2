<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudioRequest extends Model
{
    use HasFactory;

    protected $fillable = ['text', 'character', 'file_path'];
}
