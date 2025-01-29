<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->longText('prompt')->change();
        });
    }

    public function down(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->string('prompt')->change();
        });
    }
};