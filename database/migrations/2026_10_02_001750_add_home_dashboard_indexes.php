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
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->index(['session_date', 'status'], 'course_sessions_date_status_index');
        });

        Schema::table('scheduled_course', function (Blueprint $table) {
            $table->index('start_date', 'scheduled_start_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropIndex('course_sessions_date_status_index');
        });

        Schema::table('scheduled_course', function (Blueprint $table) {
            $table->dropIndex('scheduled_start_index');
        });
    }
};
