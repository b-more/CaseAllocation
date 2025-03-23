<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplainantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('complainant_types')->insert([
            [
                'name' => 'Individual',
                'description' => 'Complainant from an individual'
            ],
            [
                'name' => 'company',
                'description' => 'Complaints from a registered firm/company'
            ],
            [
                'name' => 'cooperative',
                'description' => 'Complaints from a Cooperative'
            ],
            [
                'name' => 'PIP',
                'description' => 'Prominent and influential persons report'
            ]
        ]);
    }
}
