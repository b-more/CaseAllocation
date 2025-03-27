<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PinkFileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pink_file_types')->insert([
            [
                'name' => 'Complaint General (P/S 5/1)',
                'description' => 'Criminal matters'
            ],
            [
                'name' => 'Criminal Investigations (P/S 3/26/1B)',
                'description' => 'General Criminal investigation'
            ]
        ]);
    }
}
