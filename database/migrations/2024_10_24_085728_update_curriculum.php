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
        Schema::table('curricula', function (Blueprint $table) {
            // Modify existing columns to be nullable
            $table->string('title')->nullable()->change();
            $table->text('content')->nullable()->change();
            $table->string('lesson')->nullable()->change();
            $table->string('unit')->nullable()->change();

            // Add the new 'prompt' column, making it nullable as well
            $table->string('prompt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            // Revert the nullable changes (remove the `change()` method in rollback)
            $table->string('title')->nullable(false)->change();
            $table->text('content')->nullable(false)->change();
            $table->string('lesson')->nullable(false)->change();
            $table->string('unit')->nullable(false)->change();

            // Drop the 'prompt' column
            $table->dropColumn('prompt');
        });
    }
};