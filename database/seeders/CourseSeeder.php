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

        Course::create([
            'code' => 9462,
            'title' => 'Gestión y Análisis por Proceso',
            'objective' => 'Lograr que los participantes adquieran los conocimientos y desarrollen las habilidades para la aplicación de una herramienta metodológica práctica para diseñar o mejorar la organización y establecer, implementar y mantener un sistema de gestión, para potenciar su productividad, incrementar su desempeño y alinear sus esfuerzos y recursos con los objetivos corporativos.',
            'duration' => 40,
            'addressed' => 'Profesionales en busca de la identificación y gestión sistemática de los procesos que se realizan en la organización, responsables o coordinadores en las áreas de planificación, control y gestión, así como lideres/superintendentes en departamentos de calidad, documentación y procesos.',
            'category_id' =>6,
            'modality_id' =>1,
        ]);

        Course::create([
            'code' => 9394,
            'title'=> 'Áreas Clasificadas',
            'objective'=> 'Proveer y/o reforzar los conocimientos sobre áreas eléctricas clasificadas, métodos de reducción de riesgos y protección e instalaciones eléctricas en áreas clasificadas.',
            'duration'=>8,
            'addressed'=> 'Personal de la industria que labore en el área de automatización industrial, instrumentación y electricidad',
            'category_id'=>4,
            'modality_id'=>1,
        ]);

        Course::create([
            'code' => 2547,
            'title' => 'Inducción al Buen Uso del Computador en PDVSA',
            'objective' => 'El participante estará en condiciones de manejar adecuadamente las herramientas y aplicaciones en el uso del computador de la corporación, para fines del negocio, de conformidad con las normas y procedimientos relacionados con la Cuenta de Red, Data Departamental, Correo Electrónico, Mantenimiento de PC, Computador Central y los Canales de contacto al Centro de Servicios PDVSA.',
            'duration'=>8,
            'addressed'=> 'Personal que requiera manejar adecuadamente las herramientas y aplicaciones del computador en la organizacion',
            'category_id'=>3,
            'modality_id'=>1,
        ]);
    }
}
