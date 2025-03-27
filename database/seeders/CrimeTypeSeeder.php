<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrimeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('crime_types')->insert([
            [
                'name' => 'Cyber',
                'description' => 'All criminal matters relating to cyber'
            ],
            [
                'name' => 'Financial',
                'description' => 'Cases involving financial matters'
            ],
            [
                'name' => 'Lands',
                'description' => 'Cases involving land disputes'
            ],
            [
                'name' => 'DEC',
                'description' => 'Cases involving mines'
            ],
            [
                'name' => 'ACC',
                'description' => 'Cases involving mines'
            ],
            [
                'name' => 'OP',
                'description' => 'Cases involving mines'
            ]
        ]);
    }
}
