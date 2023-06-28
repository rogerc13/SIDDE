<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rol;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Rol::create([
            'name' => 'Administrador',

        ]);
        Rol::create([
            'name' => 'Tecnología Educativa',

        ]);
        Rol::create([
            'name' => 'Programador',

        ]);
        Rol::create([
            'name' => 'Facilitador',

        ]);
        Rol::create([
            'name' => 'Participante',

        ]);
    }
}
