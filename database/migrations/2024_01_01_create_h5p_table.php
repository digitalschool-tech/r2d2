<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->text('feedback')->nullable();
            $table->integer('rating')->nullable();
            $table->foreignId('curriculum_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('course_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->text('gpt_response')->nullable();
            $table->string('view_url')->nullable();
            $table->integer('cmid')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('h5_p_s', function (Blueprint $table) {
            $table->dropColumn([
                'feedback',
                'rating',
                'curriculum_id',
                'course_id',
                'section_id',
                'gpt_response',
                'view_url',
                'cmid'
            ]);
        });
    }
}; 