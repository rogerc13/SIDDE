<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('scheduled_course_id')->references('id')->on('scheduled_course')->onDelete('cascade');
            $table->foreignId('location_id')->references('id')->on('locations');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('course_sessions');
    }
};
