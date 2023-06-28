<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'Administrador',

        ]);
        Role::create([
            'name' => 'Tecnología Educativa',

        ]);
        Role::create([
            'name' => 'Programador',

        ]);
        Role::create([
            'name' => 'Facilitador',

        ]);
        Role::create([
            'name' => 'Participante',

        ]);
    }
}
