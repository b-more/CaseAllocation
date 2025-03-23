<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IfStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('if_statuses')->insert([
            [
                'name' => 'Inquiry File Opened',
                'description' => 'Initial stage where a complaint is received, and an inquiry file is opened to assess the matter.',
            ],
            [
                'name' => 'Under Investigation',
                'description' => 'Active stage where evidence is being gathered, witnesses are interviewed, and statements are recorded.',
            ],
            [
                'name' => 'Taken to NPA',
                'description' => 'Investigation findings are submitted to the National Prosecution Authority (NPA) for legal review and determination on whether to proceed with prosecution.',
            ],
            [
                'name' => 'Taken to Court',
                'description' => 'Case has been filed in court for trial after NPA’s approval.',
            ],
            [
                'name' => 'Case Closed',
                'description' => 'Investigation concluded, either due to insufficient evidence, withdrawal by complainant, or final judgment by the court.',
            ],
        ]);
    }
}
