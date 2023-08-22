<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IdType;

class IdTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        IdType::create(['id' => 1,
        'name' => 'Venezolando',]);

        IdType::create(['id' => 2,
        'name' => 'Extranjero',]);
    }
}
