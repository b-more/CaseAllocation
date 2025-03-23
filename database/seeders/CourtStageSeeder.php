<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourtStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('court_stages')->insert([
            [
                'name' => 'Mention',
                'description' => 'Initial court appearance where charges are read, and bail applications may be made.',
            ],
            [
                'name' => 'Pre-Trial',
                'description' => 'Case management stage where issues are clarified before trial begins.',
            ],
            [
                'name' => 'Trial',
                'description' => 'Stage where evidence is presented, witnesses testify, and legal arguments are made.',
            ],
            [
                'name' => 'Judgment',
                'description' => 'The court delivers its decision, either convicting or acquitting the accused.',
            ],
            [
                'name' => 'Sentencing',
                'description' => 'If convicted, the court imposes a penalty, which may include imprisonment, fines, or other sanctions.',
            ],
            [
                'name' => 'Appeal',
                'description' => 'If dissatisfied with the judgment, a party may seek a review from a higher court.',
            ],
        ]);
    }
}
