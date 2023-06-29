<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Course::create([
            'code' => 2546,
            'title' => 'Ética, Valores y Compromiso Organizacional',
            'objective' => 'Ilustrar  a  través  del  conocimiento  en  los  ámbitos  teórico  y  práctico  los  valores  éticos  y morales mediante la educación individual, familiar, y social.  Analizando la realidad actual del ser humano en sus actividades diarias como el ser Humano Nuevo en El Socialismodel Siglo XXI',
            'duration' => 8,
            'addressed' => 'Todos los trabajadores y trabajadoras de PDVSA y las comunidades participantes.',
            'category_id' => 1,
            'modality_id' => 1,
        ]);

        Course::create([
            'code' => 2603,
            'title' => 'VENEZUELA POTENCIA ENERGÉTICA',
            'objective' => 'Identificar las condiciones de Venezuela como un país predominantemente energético, lo cual le permite establecer estrategias soberanas de desarrollo nacional  establecidas en el Proyecto Simón Bolívar, con la intención de la integración regional y mundial, que convertirá a Venezuela en una Potencia Energética Mundial.',
            'duration' => 8,
            'addressed' => 'Todos los trabajadores y trabajadoras de PDVSA y las comunidades participante',
            'category_id' => 5,
            'modality_id' => 1,
        ]);
    }
}
