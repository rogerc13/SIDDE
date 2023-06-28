<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Modality;

class ModalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Modality::create([
            'name' => 'Presencial'
        ]);
        Modality::create([
            'name' => 'Remoto'
        ]);
        Modality::create([
            'name' => 'Mixto'
        ]);
    }
}
