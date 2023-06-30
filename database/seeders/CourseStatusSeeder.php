<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CourseStatus;

class CourseStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CourseStatus::create([
            'name' => 'Por Dictar'
        ]);
        CourseStatus::create([
            'name' => 'En Curso'
        ]);
        CourseStatus::create([
            'name' => 'Culminado'
        ]);
        CourseStatus::create([
            'name' => 'Cancelado'
        ]);
    }
}
