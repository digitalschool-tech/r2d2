<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            // Make prompt nullable if it doesn't exist
            if (!Schema::hasColumn('h5_p_s', 'prompt')) {
                $table->text('prompt')->nullable();
            } else {
                // If it exists, modify it to be nullable
                $table->text('prompt')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->text('prompt')->nullable(false)->change();
        });
    }
}; 