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
        Schema::create('scheduled_course', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('course_id')->references('id')->on('courses');
            $table->foreignId('facilitator_id')->references('id')->on('facilitators');
            $table->date('start_time');
            $table->date('end_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scheduled_course');
    }
};
