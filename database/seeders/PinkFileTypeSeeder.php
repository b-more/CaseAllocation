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
                'name' => 'Crime Register',
                'description' => 'All criminal matters register'
            ],
            [
                'name' => 'FIC',
                'description' => 'Financial intelligence cases'
            ]
        ]);
    }
}
