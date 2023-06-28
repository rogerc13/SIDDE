<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'email' => 'admin@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '1',
            'person_id' => '1',
        ]);
        User::create([
            'email' => 'tecnologia@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '2',
            'person_id' => '2',
        ]);
        User::create([
            'email' => 'programador@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '3',
            'person_id' => '3',
        ]);
        User::create([
            'email' => 'facilitador@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '4',
            'person_id' => '4',
        ]);
        User::create([
            'email' => 'participante@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '5',
            'person_id' => '5',
        ]);

    }
}
