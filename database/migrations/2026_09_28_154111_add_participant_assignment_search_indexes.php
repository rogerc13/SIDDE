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
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['role_id', 'person_id'], 'users_role_person_index');
        });

        Schema::table('participants', function (Blueprint $table): void {
            $table->index(['person_id', 'scheduled_id', 'participant_status_id'], 'participants_person_schedule_status_index');
            $table->index(['scheduled_id', 'participant_status_id', 'person_id'], 'participants_schedule_status_person_index');
        });

        Schema::table('scheduled_course', function (Blueprint $table): void {
            $table->index(['course_status_id', 'start_date'], 'scheduled_status_start_index');
        });

        Schema::table('prerequisites', function (Blueprint $table): void {
            $table->index(['course_id', 'prerequisite'], 'prerequisites_course_code_index');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->index(['last_name', 'name', 'id'], 'people_assignment_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_person_index');
        });

        Schema::table('participants', function (Blueprint $table): void {
            $table->dropIndex('participants_person_schedule_status_index');
            $table->dropIndex('participants_schedule_status_person_index');
        });

        Schema::table('scheduled_course', function (Blueprint $table): void {
            $table->dropIndex('scheduled_status_start_index');
        });

        Schema::table('prerequisites', function (Blueprint $table): void {
            $table->dropIndex('prerequisites_course_code_index');
        });

        Schema::table('people', function (Blueprint $table): void {
            $table->dropIndex('people_assignment_order_index');
        });
    }
};
