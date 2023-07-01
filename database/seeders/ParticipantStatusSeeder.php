<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParticipantStatus;

class ParticipantStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ParticipantStatus::create([
            'name' => 'En Curso'
        ]);
        ParticipantStatus::create([
            'name' => 'Suspendido/Reprobado'
        ]);
        ParticipantStatus::create([
            'name' => 'Aprobado'
        ]);
    }
}
