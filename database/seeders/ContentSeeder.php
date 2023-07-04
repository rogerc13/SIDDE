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

        $content3 = [
            'Ventajas de la aplicación del enfoque basado en proceso.',
            'Beneficios del enfoque basado en proceso.',
            '¿Qué es un proceso?.',
            'Referencia a procesos en la Norma ISO 9001:2008.',
            'Objetivo del proceso.',
            'Elementos que integran el proceso.',
            'Enfoque basado en procesos.',
            'Tipos de procesos.',
            'Procesos para la gestión de la organización.',
            'Implementación del enfoque basado en proceso.',
            'Ciclo de la mejora continúa de Deming-Método PHVA.',
            'Documentación en la gestión basada en procesos.',
            'Mapa de los procesos.',
            'Diagrama de proceso.',
            'Ficha de proceso.'
        ];
        foreach ($content3 as $key) {
            Content::create([
                'course_id' => 3,
                'text' => $key,
            ]);
        }

        $content4 = [
            'Definiciones y conceptos.',
            'Normas y regulaciones.',
            'Clasificación de lugares peligrosos - Método NFPA.',
            'Clasificación de lugares peligrosos - Método IEC.',
            'Principios de reducción de riesgos.',
            'Tipos de protección.',
            'Instalaciones eléctricas en áreas clasificadas.',
            'Seguridad intrínseca.',
            'Certificaciones.'
        ];

        foreach ($content4 as $key) {
            Content::create([
                'course_id' => 4,
                'text' => $key,
            ]);
        }
    }
}
