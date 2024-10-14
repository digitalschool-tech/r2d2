<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class H5P extends Model
{
    use HasFactory;

    protected $table = "h5_p_s";

    protected $fillable = [
        'prompt',
        'filename'
    ];
}
