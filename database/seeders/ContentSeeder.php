<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Content;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Content::create([
            'course_id' => 2,
            'text' => 'Fuentes de energía en Venezuela.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'Condiciones legales.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'Internacionalización de los hidrocarburos.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'La integración latinoamericana y caribeña.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'La producción y el consumo de la energía gestionando la preservación del ambiente.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'La diversificación productiva y la inclusión social.',
        ]);
        Content::create([
            'course_id' => 2,
            'text' => 'Planes socialistas de la nación.',
        ]);
    }
}
