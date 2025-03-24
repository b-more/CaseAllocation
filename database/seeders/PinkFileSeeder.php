<?php

namespace Database\Seeders;

use App\Models\PinkFile;
use App\Models\User;
use App\Models\CrimeType;
use App\Models\ComplainantType;
use App\Models\PinkFileType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PinkFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing pink files to avoid duplicates
        DB::table('pink_files')->truncate();

        // Get IDs for relationships
        $crimeTypeIds = CrimeType::pluck('id')->toArray();
        $complainantTypeIds = ComplainantType::pluck('id')->toArray();
        $pinkFileTypeIds = PinkFileType::pluck('id')->toArray();

        // Get investigator IDs (role_id = 2)
        $investigatorIds = User::where('role_id', 2)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($investigatorIds)) {
            $this->command->error('No active investigators found in the database. Please run UserSeeder first.');
            return;
        }

        // Sample complainant names and priorities
        $complainantNames = [
            'John Mumba', 'Charity Banda', 'Joseph Phiri', 'Mary Tembo',
            'Faith Mwanza', 'David Zimba', 'Elizabeth Mulenga', 'Kenneth Zulu',
            'Bwalya Mining Corp.', 'Zambia Financial Ltd.', 'First National Bank',
            'Copperbelt Energy Company', 'University of Zambia', 'Ministry of Finance',
            'Lusaka City Council', 'Patricia Chanda', 'Robert Mwila', 'Helen Ngosa',
            'Emmanuel Lungu', 'Victoria Falls Trust', 'Zambeef Products PLC',
            'Madison Insurance', 'National Pension Scheme Authority', 'Zamtel',
            'Elias Chipimo', 'Sandra Mwila', 'Zesco Limited', 'Zambia Revenue Authority',
            'Grace Mwamba', 'Peter Kaunda', 'Lusaka Water & Sewerage Company',
            'Konkola Copper Mines', 'James Mbewe', 'Susan Tembo', 'Bank of Zambia'
        ];

        $priorities = ['very_high', 'high', 'normal', 'low'];

        // Generate 35 pink files
        $pinkFiles = [];
        $now = now();

        for ($i = 0; $i < 35; $i++) {
            // Calculate a random date between 6 months ago and today
            $randomDate = Carbon::now()->subDays(rand(0, 180))->format('Y-m-d H:i:s');

            // Randomly select IDs from available options
            $crimeTypeId = $crimeTypeIds[array_rand($crimeTypeIds)];
            $complainantTypeId = $complainantTypeIds[array_rand($complainantTypeIds)];
            $pinkFileTypeId = $pinkFileTypeIds[array_rand($pinkFileTypeIds)];
            $investigatorId = $investigatorIds[array_rand($investigatorIds)];

            // Randomly select a complainant name
            $complainantName = $complainantNames[array_rand($complainantNames)];

            // Randomly select a priority
            $priority = $priorities[array_rand($priorities)];

            // Determine if this case should be acknowledged - increase to 90% to ensure enough acknowledged files
            $acknowledgedAt = null;
            if (rand(0, 100) / 100 < 0.9) { // 90% of cases will be acknowledged
                $acknowledgedAt = Carbon::parse($randomDate)->addDays(rand(1, 3))->format('Y-m-d H:i:s');
            }

            $pinkFiles[] = [
                'pink_file_type_id' => $pinkFileTypeId,
                'ig_folio' => 'IG-' . rand(1000, 9999) . '/' . date('Y'),
                'commissioner_cid_folio' => 'CID-' . rand(1000, 9999) . '/' . date('Y'),
                'director_c2_folio' => 'C2-' . rand(1000, 9999) . '/' . date('Y'),
                'assistant_director_c2_comment' => 'Please investigate this case thoroughly.',
                'oic_comment' => $this->generateRandomComment(),
                'complainant_type_id' => $complainantTypeId,
                'complainant_name' => $complainantName,
                'date_time_of_occurrence' => Carbon::parse($randomDate)->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
                'crime_type_id' => $crimeTypeId,
                'priority' => $priority,
                'assigned_to' => $investigatorId,
                'acknowledged_at' => $acknowledgedAt,
                'created_at' => $randomDate,
                'updated_at' => $randomDate
            ];
        }

        // Insert the data in chunks
        foreach (array_chunk($pinkFiles, 10) as $chunk) {
            DB::table('pink_files')->insert($chunk);
        }

        $this->command->info('Pink files seeded successfully.');

        // Count and report acknowledged files
        $acknowledgedCount = DB::table('pink_files')->whereNotNull('acknowledged_at')->count();
        $this->command->info("Created {$acknowledgedCount} acknowledged pink files ready for inquiry files.");
    }

    /**
     * Generate a random OIC comment
     */
    private function generateRandomComment()
    {
        $comments = [
            'Please follow up on this case urgently.',
            'Ensure all witnesses are interviewed promptly.',
            'Coordinate with the local police station for assistance.',
            'This case requires immediate attention due to its sensitive nature.',
            'Gather all relevant financial documents for analysis.',
            'Please provide a preliminary report within 48 hours.',
            'Contact the complainant for additional details on the transaction.',
            'Cross-reference with similar cases from last quarter.',
            'This case may be connected to the ongoing investigation in Central Province.',
            'Verify all account details mentioned in the complaint.',
            'Interview all parties involved as soon as possible.',
            'Keep this investigation confidential.',
            'Check with bank for CCTV footage of the transaction.',
            'Liaise with cyber security team for digital evidence.',
            'Check if the suspect has any prior criminal record.',
            'Request assistance from forensic accounting if needed.',
            null, // Some cases will have no comments
            null
        ];

        return $comments[array_rand($comments)];
    }
}
