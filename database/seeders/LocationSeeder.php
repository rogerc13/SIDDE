<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBuilding = Building::create(['name' => 'Edificio Principal']);
        $defaultFloor = Floor::create([
            'building_id' => $defaultBuilding->id,
            'name' => 'Piso 1',
        ]);

        Location::whereNull('floor_id')->update([
            'floor_id' => $defaultFloor->id,
        ]);
    }
}
