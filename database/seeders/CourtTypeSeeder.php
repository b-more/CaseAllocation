<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourtTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('court_types')->insert([
            [
                'name' => 'Supreme Court',
                'description' => 'Final authority on legal matters, except for constitutional issues. Handles appeals from the Court of Appeal.',
            ],
            [
                'name' => 'Constitutional Court',
                'description' => 'Handles constitutional matters, including interpretation and presidential election petitions.',
            ],
            [
                'name' => 'Court of Appeal',
                'description' => 'Reviews appeals from the High Court and specialized tribunals.',
            ],
            [
                'name' => 'High Court',
                'description' => 'Handles serious civil and criminal cases and supervises lower courts.',
            ],
            [
                'name' => 'Magistrates Court',
                'description' => 'Handles most criminal and civil cases at the first instance, categorized into Class I, II, and III Magistrates.',
            ],
            [
                'name' => 'Local Court',
                'description' => 'Deals with customary law cases, minor civil disputes, and some criminal matters.',
            ],
            [
                'name' => 'Industrial Relations Court',
                'description' => 'Handles labor and employment disputes.',
            ],
            [
                'name' => 'Small Claims Court',
                'description' => 'Deals with minor financial disputes with simplified procedures.',
            ],
            [
                'name' => 'Lands Tribunal',
                'description' => 'Handles land-related disputes.',
            ],
            [
                'name' => 'Financial and Economic Crimes Court',
                'description' => 'Deals with corruption, money laundering, and economic crimes.',
            ],
        ]);
    }
}
