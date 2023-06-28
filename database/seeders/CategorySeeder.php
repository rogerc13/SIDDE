<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::create(['name' => 'Autocontrol']);
        Category::create(['name' => 'Automatización, Informática, Telecomunicación']);
        Category::create(['name' => 'Ingeniería, Proyecto y Mantenimiento.']);
        Category::create(['name' => 'Sociopolitica']);
        Category::create(['name' => 'Planificación']);
        Category::create(['name' => 'Química']);
        Category::create(['name' => 'Quimica 1']);
        Category::create(['name' => 'Area De Prueba']);
    }
}
