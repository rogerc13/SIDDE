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
        function createUser($user){
            $i = 6;
            foreach($user as $key => $value){
                User::create([
                    'email' => $key['email'],
                    'password' => bcrypt('123456'),
                    'role_id' => '5',
                    'person_id' => $i,
                ]);
            $i = $i + 1;
            }
        }

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
        User::create([
            'email' => 'manuelalbani@pdvsa.com',
            'password' => bcrypt('123456'),
            'role_id' => '5',
            'person_id' => '6',
        ]);
        //person -> user
        /* $user = ['email' => 'luisbastudillo@gmail.com',
                'email' => 'krmniz777@hotmail.com',
                'email' => 'jjsr20092009@hotmail.com',
                'email' => 'arelyls@hotmail.com',
                'email' => 'cordovaismeria410@gmail.com',
                'email' => 'cira20631@hotmail.com',
                'email' => 'marianela@hotmail.com',
                'email' =>  'alisandemartinez@gmail.com',
                'email' => 'angelmalave1412@gmail.com',
                'email' =>  'winston91@outlook.es',
                'email' => 'luijo@live.com.ar',
                'email' =>  'rosalesjose1978@gmail.com',
                'email' =>  'adolfo.marcano.908@gmail.com',
                'email' => 'noel-j-enriquezm@hotmail.com',
                'email' => 'holy_spire@hotmail.com',
                'email' =>  'luisgarcia@gmail.com',
                'email' =>  'pedroelbae@gmail.com',
                'email' => 'felipevtin1310tkd@gmail.com',
                'email' => 'lenarjurbaneja1@hotmail.com',
                'email' => 'djmiguelfermin@gmail.com',
                'email' =>  'angelalejandro2012@hotmail.com',
                'email' => 'keryin_princess@hotmail.com',
                'email' =>  'mirtzaseijas@hotmail.com',
                'email' => 'tu_beba_maurelys@hotmail.',
                'email' => 'isairissalazar.rincones@gmail.com',
                'email' => 'canelones63@gmail.com',
                'email' => 'sofialanena159@gmail.com',
                'email' => 'zoykorn@gmail.com',
                'email' => 'zapatagabriel1976@hotmail.com',
                'email' => 'jimenezjesus101197@gmail.com'];

        createUser($user); */

    }
}
