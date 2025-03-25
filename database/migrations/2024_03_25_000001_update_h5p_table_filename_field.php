<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            if (!Schema::hasColumn('h5_p_s', 'filename')) {
                $table->string('filename')->nullable();
            } else {
                $table->string('filename')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->string('filename')->nullable(false)->change();
        });
    }
}; 