<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Type;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {   Type::create([
            'name' => 'manual_f',
    ]);
       Type::create([
            'name' => 'manual_p',
    ]);
       Type::create([
            'name' => 'guia',
    ]);
       Type::create([
            'name' => 'presentacion',
    ]);
        
    }
}
