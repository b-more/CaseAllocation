<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(CrimeTypeSeeder::class);
        $this->call(ComplainantTypeSeeder::class);
        $this->call(PinkFileTypeSeeder::class);
        $this->call(CourtTypeSeeder::class);
        $this->call(CourtStageSeeder::class);
        $this->call(IfStatusSeeder::class);

        $this->call(PinkFileSeeder::class);
        $this->call(InquiryFileSeeder::class);
    }
}
