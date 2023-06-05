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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->integer('modalidad_id');
            $table->string('objetivo');
            $table->string('contenido');
            $table->integer('duracion');
            $table->string('dirigido');
            $table->integer('max');
            $table->integer('min');
            $table->timestamps();
            $table->integer('categoria_id');
            
            $table->string('manual_f');
            $table->string('manual_p');
            $table->string('guia');
            $table->string('presentacion');

            //relationships
            $table->foreign('modalidad_id')->references('id')->on('modalities')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('categoria_id')->references('id')->on('categories')->onDelete('cascade')->onUpdate('cascade');


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
};
