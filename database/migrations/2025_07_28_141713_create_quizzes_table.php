<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('external_student_id');
            $table->foreignId('curriculum_id')->constrained()->onDelete('cascade');

            $table->json('quiz_data');
            $table->json('wrong_questions')->nullable();

            $table->unsignedInteger('ttc')->nullable();
            $table->decimal('completion_pct', 5, 2)->nullable();
            $table->decimal('performance', 5, 2)->nullable();

            $table->string('difficulty_level')->default('medium');
            $table->timestamps();
            $table->index('external_student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
