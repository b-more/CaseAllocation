<?php

namespace Database\Seeders;

use App\Models\Accused;
use App\Models\CaseStatus;
use App\Models\CourtStage;
use App\Models\CourtType;
use App\Models\IfStatus;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InquiryFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get pink files that have been acknowledged but don't have inquiry files yet
        $pinkFilesQuery = PinkFile::whereNotNull('acknowledged_at')
            ->whereDoesntHave('inquiryFile')
            ->orderBy('acknowledged_at');

        $pinkFilesCount = $pinkFilesQuery->count();

        if ($pinkFilesCount === 0) {
            $this->command->error('No acknowledged pink files found without inquiry files. Please run PinkFileSeeder first.');
            return;
        }

        $this->command->info("Found {$pinkFilesCount} acknowledged pink files without inquiry files.");

        // Get all pink files
        $pinkFiles = $pinkFilesQuery->get();

        // Get IDs for relationships
        $ifStatusIds = IfStatus::pluck('id')->toArray();
        $courtTypeIds = CourtType::pluck('id')->toArray();
        $courtStageIds = CourtStage::pluck('id')->toArray();

        // Get OIC IDs (role_id = 1) for status changes
        $oicIds = User::where('role_id', 1)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (empty($oicIds)) {
            $this->command->error('No active OICs found in the database. Please run UserSeeder first.');
            return;
        }

        // Sample offence descriptions
        $offences = [
            'Fraud by false representation',
            'Obtaining money by false pretences',
            'Computer fraud',
            'Identity theft',
            'Money laundering',
            'Embezzlement',
            'Forgery of documents',
            'Online banking fraud',
            'Investment fraud',
            'Pension fraud',
            'Insurance fraud',
            'Credit card fraud',
            'Tax evasion',
            'Counterfeit currency',
            'Pyramid scheme',
            'Land fraud',
            'Charity fraud',
            'Cyber fraud',
            'Fraudulent bank transactions',
            'Cryptocurrency scam',
        ];

        $inquiryFilesCreated = 0;

        // Generate inquiry files for each pink file
        foreach ($pinkFiles as $index => $pinkFile) {
            // Calculate IF creation date (a few days after pink file acknowledgment)
            $creationDate = Carbon::parse($pinkFile->acknowledged_at)->addDays(rand(1, 5));

            // Skip if creation date would be in the future
            if ($creationDate->isAfter(now())) {
                continue;
            }

            // Generate IF number
            $ifNumber = ($index + 1) . '/' . $creationDate->format('n') . '/' . $creationDate->format('y');

            // Randomly select status - weighted toward "Under Investigation" for more realistic distribution
            $statusWeights = [1 => 10, 2 => 50, 3 => 15, 4 => 15, 5 => 10]; // Status ID => weight
            $selectedStatusId = $this->weightedRandom($statusWeights);

            // Default court-related fields to null
            $courtTypeId = null;
            $courtStageId = null;
            $caseCloseReason = null;
            $crNumber = null;
            $policeStation = null;

            // Populate court-related fields based on status
            if ($selectedStatusId == 3 || $selectedStatusId == 4) { // Taken to NPA or Court
                $courtTypeId = $courtTypeIds[array_rand($courtTypeIds)];
                $crNumber = 'CR' . rand(1000, 9999) . '/' . $creationDate->format('Y');
                $policeStation = $this->getRandomPoliceStation();
            }

            if ($selectedStatusId == 4) { // Taken to Court
                $courtStageId = $courtStageIds[array_rand($courtStageIds)];
            }

            if ($selectedStatusId == 5) { // Case Closed
                $caseCloseReason = $this->getRandomCloseReason();
            }

            // Financial values
            $stolenValue = rand(100000, 10000000) / 100; // 1,000 - 100,000 ZMW
            $recoveryRate = rand(0, 100) / 100; // 0-100% recovery rate
            $recoveredValue = round($stolenValue * $recoveryRate, 2);

            // Investigation progress - more likely to be completed for cases that have progressed further
            $progressProbability = min(0.3 + ($selectedStatusId * 0.15), 1.0);

            // Create the inquiry file record
            $inquiryFile = InquiryFile::create([
                'if_number' => $ifNumber,
                'time' => $creationDate->format('H:i:s'),
                'date' => $creationDate->format('Y-m-d'),
                'cr_number' => $crNumber,
                'police_station' => $policeStation,
                'complainant' => $pinkFile->complainant_name,
                'offence' => $offences[array_rand($offences)],
                'value_of_property_stolen' => $stolenValue,
                'value_of_property_recovered' => $recoveredValue,
                'if_status_id' => $selectedStatusId,
                'case_close_reason' => $caseCloseReason,
                'contacted_complainant' => rand(0, 100) / 100 < $progressProbability,
                'recorded_statement' => rand(0, 100) / 100 < $progressProbability * 0.9,
                'apprehended_suspects' => rand(0, 100) / 100 < $progressProbability * 0.8,
                'warned_cautioned' => rand(0, 100) / 100 < $progressProbability * 0.7,
                'released_on_bond' => rand(0, 100) / 100 < $progressProbability * 0.6,
                'court_type_id' => $courtTypeId,
                'court_stage_id' => $courtStageId,
                'remarks' => rand(0, 1) ? $this->getRandomRemarks() : null,
                'dealing_officer' => $pinkFile->assigned_to,
                'pink_file_id' => $pinkFile->id,
                'acknowledged_at' => $creationDate->addDays(rand(1, 3))->format('Y-m-d H:i:s'),
                'created_at' => $creationDate->format('Y-m-d H:i:s'),
                'updated_at' => $creationDate->format('Y-m-d H:i:s')
            ]);

            // Initial status creation - always "Inquiry File Opened"
            $initialStatusDate = Carbon::parse($inquiryFile->created_at);

            // Insert the initial status record
            DB::table('case_statuses')->insert([
                'case_id' => $inquiryFile->id,
                'user_id' => $inquiryFile->dealing_officer,
                'old_status' => null,
                'new_status' => 1, // Inquiry File Opened
                'reason' => 'Initial inquiry file creation',
                'oic_comment' => null,
                'is_read' => false,
                'read_at' => null,
                'created_at' => $initialStatusDate->format('Y-m-d H:i:s'),
                'updated_at' => $initialStatusDate->format('Y-m-d H:i:s')
            ]);

            // If current status is not "Inquiry File Opened", create additional status changes
            if ($inquiryFile->if_status_id > 1) {
                $this->createStatusChangesForFile(
                    $inquiryFile->id,
                    $inquiryFile->if_status_id,
                    $inquiryFile->dealing_officer,
                    $oicIds,
                    $initialStatusDate
                );
            }

            // Generate random number of accused persons (0-3)
            $numAccused = rand(0, 3);
            for ($i = 0; $i < $numAccused; $i++) {
                DB::table('accuseds')->insert([
                    'case_id' => $inquiryFile->id,
                    'name' => $this->getRandomName(),
                    'identification' => rand(0, 1) ? $this->getRandomID() : null,
                    'contact' => rand(0, 1) ? $this->getRandomPhone() : null,
                    'address' => rand(0, 1) ? $this->getRandomAddress() : null,
                    'created_at' => $inquiryFile->created_at,
                    'updated_at' => $inquiryFile->created_at
                ]);
            }

            $inquiryFilesCreated++;
        }

        $this->command->info("Successfully created {$inquiryFilesCreated} inquiry files with related data.");
    }

    /**
     * Create sequential status changes for a specific inquiry file
     */
    private function createStatusChangesForFile($caseId, $currentStatusId, $dealingOfficerId, $oicIds, $startDate)
    {
        $statusDate = clone $startDate;

        // For each status level between initial (1) and current
        for ($status = 2; $status <= $currentStatusId; $status++) {
            // Add random days between status changes (1-30 days)
            $statusDate->addDays(rand(1, 30));

            // Skip if date would be in the future
            if ($statusDate->isAfter(now())) {
                break;
            }

            // Randomly select who made the status change (officer or OIC)
            $changeMadeByOIC = rand(0, 1);
            $userId = $changeMadeByOIC ? $oicIds[array_rand($oicIds)] : $dealingOfficerId;

            // Insert the status change record directly
            DB::table('case_statuses')->insert([
                'case_id' => $caseId,
                'user_id' => $userId,
                'old_status' => ($status - 1),
                'new_status' => $status,
                'reason' => $this->getStatusChangeReason($status),
                'oic_comment' => null, // Keep this null for status changes
                'is_read' => false,
                'read_at' => null,
                'created_at' => $statusDate->format('Y-m-d H:i:s'),
                'updated_at' => $statusDate->format('Y-m-d H:i:s')
            ]);

            // Randomly add OIC comments (30% chance)
            if (rand(1, 100) <= 30) {
                $commentDate = clone $statusDate;
                $commentDate->addDays(rand(1, 5));

                // Skip if date would be in the future
                if (!$commentDate->isAfter(now())) {
                    DB::table('case_statuses')->insert([
                        'case_id' => $caseId,
                        'user_id' => $oicIds[array_rand($oicIds)],
                        'old_status' => $status,
                        'new_status' => $status,
                        'reason' => 'OIC comment added',
                        'oic_comment' => $this->getRandomOICComment(),
                        'is_read' => false,
                        'read_at' => null,
                        'created_at' => $commentDate->format('Y-m-d H:i:s'),
                        'updated_at' => $commentDate->format('Y-m-d H:i:s')
                    ]);
                }
            }
        }
    }

    /**
     * Get a random name for accused persons
     */
    private function getRandomName()
    {
        $firstNames = ['John', 'David', 'Michael', 'Robert', 'Daniel', 'Joseph', 'Charles', 'Thomas', 'Christopher', 'Paul',
            'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan', 'Jessica', 'Sarah', 'Karen',
            'Mwamba', 'Mulenga', 'Banda', 'Phiri', 'Tembo', 'Zulu', 'Mutale', 'Chanda', 'Mumba', 'Mwila'];

        $lastNames = ['Mumba', 'Banda', 'Phiri', 'Tembo', 'Zulu', 'Mulenga', 'Chanda', 'Daka', 'Ngosa', 'Bwalya',
            'Mwanza', 'Sinkala', 'Musonda', 'Mbewe', 'Mulilo', 'Chileshe', 'Lungu', 'Mwila', 'Kaunda', 'Chishimba'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Get a random ID number
     */
    private function getRandomID()
    {
        $types = ['NRC', 'PP', 'DL'];
        $type = $types[array_rand($types)];

        if ($type === 'NRC') {
            return rand(100000, 999999) . '/' . rand(10, 99) . '/' . rand(1, 9);
        } elseif ($type === 'PP') {
            return strtoupper(Str::random(2)) . rand(100000, 999999);
        } else {
            return rand(10000, 99999) . strtoupper(Str::random(2));
        }
    }

    /**
     * Get a random phone number
     */
    private function getRandomPhone()
    {
        $prefixes = ['095', '096', '097', '098', '076', '077', '099'];
        return $prefixes[array_rand($prefixes)] . rand(1000000, 9999999);
    }

    /**
     * Get a random address
     */
    private function getRandomAddress()
    {
        $areas = ['Lusaka', 'Ndola', 'Kitwe', 'Kabwe', 'Livingstone', 'Chingola', 'Mufulira', 'Luanshya', 'Solwezi', 'Kasama'];
        $streets = ['Great East Road', 'Cairo Road', 'Independence Avenue', 'Freedom Way', 'Kafue Road', 'Church Road', 'Addis Ababa Drive', 'Dedan Kimathi Road', 'Cha Cha Cha Road', 'Lumumba Road'];

        return 'Plot ' . rand(1, 9999) . ', ' . $streets[array_rand($streets)] . ', ' . $areas[array_rand($areas)];
    }

    /**
     * Get a random police station
     */
    private function getRandomPoliceStation()
    {
        $stations = [
            'Lusaka Central Police Station',
            'Woodlands Police Station',
            'Kabwata Police Station',
            'Matero Police Station',
            'Chelstone Police Station',
            'Emmasdale Police Station',
            'Kabulonga Police Station',
            'Chilenje Police Station',
            'Ndola Central Police Station',
            'Kitwe Central Police Station',
            'Livingstone Central Police Station',
            'Kabwe Central Police Station',
            'Chingola Central Police Station',
            'Chipata Central Police Station',
            'Kasama Central Police Station'
        ];

        return $stations[array_rand($stations)];
    }

    /**
     * Get a random reason for status change
     */
    private function getStatusChangeReason($statusId)
    {
        $reasons = [
            2 => [ // Under Investigation
                'Investigation commenced after initial assessment',
                'Evidence collection in progress',
                'Witnesses identified for interview',
                'Initial leads being followed',
                'Assigned for active investigation',
            ],
            3 => [ // Taken to NPA
                'Sufficient evidence gathered for prosecution',
                'Case file prepared for NPA review',
                'Suspects identified and case ready for prosecution',
                'Investigation complete, referred to NPA',
                'Prosecutor requested case file',
            ],
            4 => [ // Taken to Court
                'NPA approved prosecution, case filed in court',
                'Court date assigned after NPA review',
                'Case has been entered into court registry',
                'Prosecution commenced after NPA approval',
                'Trial process initiated',
            ],
            5 => [ // Case Closed
                'Investigation concluded, case resolved',
                'All legal proceedings completed',
                'Case closed after court judgment',
                'Investigation completed with resolution',
                'Case closure approved by OIC',
            ]
        ];

        $statusReasons = $reasons[$statusId] ?? ['Status updated by officer'];
        return $statusReasons[array_rand($statusReasons)];
    }

    /**
     * Get a random reason for case closure
     */
    private function getRandomCloseReason()
    {
        $reasons = [
            'Accused found guilty and sentenced. Case fully resolved.',
            'Complainant withdrew the case after reconciliation with accused.',
            'Case dismissed by court due to lack of sufficient evidence.',
            'Case closed after full recovery of stolen property.',
            'Prosecution discontinued by NPA due to technicalities.',
            'Matter resolved through out-of-court settlement agreed by all parties.',
            'Court found the accused not guilty after full trial.',
            'Case resolved through alternative dispute resolution methods.',
            'Investigation found the initial complaint to be unsubstantiated.',
            'Statute of limitations expired for the alleged offense.',
            'The accused is deceased, prosecution discontinued.',
            'Case closed after successful mediation between parties.',
            'All suspects exonerated after thorough investigation.',
            'Complaint withdrawn by complainant, no further action required.'
        ];

        return $reasons[array_rand($reasons)];
    }

    /**
     * Get random remarks from investigator
     */
    private function getRandomRemarks()
    {
        $remarks = [
            'Case involves multiple jurisdictions and will require coordination.',
            'Complainant has provided extensive documentation to support allegations.',
            'Case is connected to several other fraud complaints in the same area.',
            'Digital forensics analysis is required for electronic evidence.',
            'Suspect has previous convictions for similar offenses.',
            'Financial records show a complex pattern of transactions requiring expert analysis.',
            'Witnesses are reluctant to provide formal statements due to fear of retaliation.',
            'Case has attracted media attention and should be handled with discretion.',
            'Physical evidence has been collected and secured at headquarters.',
            'Case requires additional technical expertise from cyber crime division.',
            'Complainant is a prominent business person with significant influence.',
            'Multiple suspects operating as an organized network across provinces.',
            'Evidence suggests this may be part of a larger money laundering scheme.',
            'Several bank accounts identified for possible freezing orders.',
            'Case documentation is extensive and requires careful indexing.'
        ];

        return $remarks[array_rand($remarks)];
    }

    /**
     * Get random OIC comments
     */
    private function getRandomOICComment()
    {
        $comments = [
            'Expedite witness interviews to prevent loss of evidence.',
            'Ensure all digital evidence is properly authenticated and secured.',
            'Coordinate with financial crimes team for specialized assistance.',
            'Request bank statement analysis for the last 12 months from all accounts involved.',
            'Submit weekly progress reports until further notice.',
            'Prioritize this investigation due to high-profile nature of complainant.',
            'Verify all property ownership documents with Lands Registry.',
            'Consider obtaining search warrants for additional premises.',
            'Consult with forensic accounting for detailed financial analysis.',
            'Ensure proper evidence chain of custody for all items seized.',
            'Interview additional witnesses identified in preliminary statements.',
            'Consider surveillance operation to monitor suspect activities.',
            'Prepare detailed chronology of events for case presentation.',
            'Check for connections to other pending fraud cases in the database.',
            'Expedite preparation of case file for early submission to NPA.'
        ];

        return $comments[array_rand($comments)];
    }

    /**
     * Select a random item based on weights
     */
    private function weightedRandom(array $weights)
    {
        $sum = array_sum($weights);
        $rand = mt_rand(1, $sum);

        foreach ($weights as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $key;
            }
        }

        return array_key_first($weights); // Fallback
    }
}
