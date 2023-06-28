<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Person;
class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Person::create([
            'name' => 'Roger',
            'last_name' => 'Canache',
            'id_number' => '20634220',
            'phone' => '123456789',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Claudio',
            'last_name' => 'Cortinez',
            'id_number' => '20634222',
            'phone' => '123456782',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Marcos',
            'last_name' => 'Capriles',
            'id_number' => '20634223',
            'phone' => '123456783',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Camila',
            'last_name' => 'Maita',
            'id_number' => '20634224',
            'phone' => '123456784',
            'sex' => 'Femenino',
        ]);
        Person::create([
            'name' => 'Jhonny',
            'last_name' => 'Prato',
            'id_number' => '20634225',
            'phone' => '123456785',
            'sex' => 'Masculino',
        ]);
    }
}
