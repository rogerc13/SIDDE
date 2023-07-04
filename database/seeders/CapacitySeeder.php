<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Capacity;

class CapacitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Capacity::create([
            'min' => 16,
            'max' => 30,
            'course_id' => 1,
        ]);
        Capacity::create([
            'min' => 16,
            'max' => 30,
            'course_id' => 2,
        ]);

        Capacity::create([
            'min' => 12,
            'max' => 20,
            'course_id' => 3,
        ]);

        Capacity::create([
            'min' => 12,
            'max' => 20,
            'course_id' => 4,
        ]);

    }
}
