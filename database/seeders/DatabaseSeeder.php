<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ParticipantStatusSeeder::class);
        $this->call(TypeSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PersonSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ModalitySeeder::class);
        $this->call(CourseSeeder::class);
        $this->call(CapacitySeeder::class);
        $this->call(ContentSeeder::class);
        $this->call(CourseStatusSeeder::class);
        $this->call(FacilitatorSeeder::class);
        $this->call(ScheduledCourseSeeder::class);
    }
}
