<?php

namespace App\Filament\Resources\InquiryFileResource\Pages;

use App\Filament\Resources\InquiryFileResource;
use App\Models\Accused;
use App\Models\CaseStatus;
use App\Models\InquiryFile;
use App\Models\PinkFile;
use App\Models\User;
use App\Services\SmsService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateInquiryFile extends CreateRecord
{
    protected static string $resource = InquiryFileResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Generate a unique inquiry file number
        $attempt = 0;
        $ifNumber = null;
        $record = null;

        Log::info('Creating inquiry file with data: ' . json_encode($data));

        // Try up to 5 times to create with a unique IF number
        while ($attempt < 5 && !$record) {
            try {
                DB::beginTransaction();

                // Generate a new IF number
                $ifNumber = InquiryFile::generateInquiryNumber();
                $data['if_number'] = $ifNumber;

                Log::info('Generated IF number: ' . $ifNumber);

                // Create the inquiry file
                $record = static::getModel()::create($data);
                Log::info('Created inquiry file with ID: ' . $record->id);

                // Handle accused persons repeater data if present
                if (isset($data['accused_persons']) && is_array($data['accused_persons'])) {
                    foreach ($data['accused_persons'] as $accusedData) {
                        Accused::create([
                            'case_id' => $record->id,
                            'name' => $accusedData['name'],
                            'identification' => $accusedData['identification'] ?? null,
                            'contact' => $accusedData['contact'] ?? null,
                            'address' => $accusedData['address'] ?? null,
                        ]);
                    }
                    Log::info('Added ' . count($data['accused_persons']) . ' accused persons');
                }

                // Create an initial status record
                CaseStatus::create([
                    'case_id' => $record->id,
                    'user_id' => Auth::id(),
                    'new_status' => $data['if_status_id'],
                    'reason' => 'Initial inquiry file creation',
                ]);

                // If there is an OIC comment, add it to the status record
                if (Auth::user()->role_id == 1 && !empty($data['oic_comment'])) {
                    // Create a separate status entry for the OIC comment
                    CaseStatus::create([
                        'case_id' => $record->id,
                        'user_id' => Auth::id(),
                        'old_status' => $data['if_status_id'],
                        'new_status' => $data['if_status_id'],
                        'oic_comment' => $data['oic_comment'],
                        'reason' => 'OIC initial comment',
                    ]);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to create inquiry file: " . $e->getMessage());
                $attempt++;

                // If it's not a unique constraint issue, re-throw the exception
                if (!str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                    throw $e;
                }
            }
        }

        if (!$record) {
            throw new \Exception("Failed to create inquiry file after several attempts. Please try again later.");
        }

        return $record;
    }

    protected function afterCreate(): void
    {
        // Create a notification for the assigned officer
        $record = $this->record;
        Log::info('Created inquiry file: ID=' . $record->id . ', IF number=' . $record->if_number);

        if ($record->dealing_officer) {
            Log::info('Dealing officer ID: ' . $record->dealing_officer);

            // Get the user model
            $dealingOfficer = User::find($record->dealing_officer);

            if ($dealingOfficer) {
                Log::info('Found dealing officer: ' . $dealingOfficer->name);

                Notification::make()
                    ->title('New Inquiry File Assigned')
                    ->body("You have been assigned inquiry file: {$record->if_number}")
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(InquiryFileResource::getUrl('view', ['record' => $record->id]))
                    ])
                    ->sendToDatabase($dealingOfficer);

                Log::info('Notification sent to dealing officer: ' . $dealingOfficer->id);

                // Send SMS if officer has a phone number
                if ($dealingOfficer->phone) {
                    $message = "New case assignment: You have been assigned to inquiry file {$record->if_number}. Please login to the system to acknowledge.";
                    SmsService::sendMessage($message, $dealingOfficer->phone);

                    Log::info('SMS sent to dealing officer at: ' . $dealingOfficer->phone);
                }
            } else {
                Log::error('Failed to find dealing officer with ID: ' . $record->dealing_officer);
            }
        } else {
            Log::warning('Inquiry file created without dealing_officer value');
        }

        // If this was created from a pink file, update that relationship
        if ($record->pink_file_id) {
            Log::info('Inquiry file was created from pink file ID: ' . $record->pink_file_id);

            // Get the pink file
            $pinkFile = PinkFile::find($record->pink_file_id);

            if ($pinkFile) {
                Log::info('Found pink file with complainant: ' . $pinkFile->complainant_name);

                // If the dealing officer wasn't explicitly set, use the one from the pink file
                if (!$record->dealing_officer && $pinkFile->assigned_to) {
                    $record->dealing_officer = $pinkFile->assigned_to;
                    $record->save();

                    Log::info('Updated dealing officer from pink file: ' . $pinkFile->assigned_to);

                    // Send notification to the assigned officer if it was added from the pink file
                    $dealingOfficer = User::find($pinkFile->assigned_to);
                    if ($dealingOfficer) {
                        Notification::make()
                            ->title('New Inquiry File Assigned')
                            ->body("You have been assigned inquiry file: {$record->if_number}")
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->button()
                                    ->url(InquiryFileResource::getUrl('view', ['record' => $record->id]))
                            ])
                            ->sendToDatabase($dealingOfficer);

                        // Send SMS if officer has a phone number
                        if ($dealingOfficer->phone) {
                            $message = "New case assignment: You have been assigned to inquiry file {$record->if_number}. Please login to the system to acknowledge.";
                            SmsService::sendMessage($message, $dealingOfficer->phone);
                        }
                    }
                }

                // Notify the OIC that an inquiry file has been created for this pink file
                $oicUsers = User::where('role_id', 1)->get(); // OIC role
                foreach ($oicUsers as $oicUser) {
                    Notification::make()
                        ->title('Inquiry File Created')
                        ->body("An inquiry file ({$record->if_number}) has been created for pink file case: {$pinkFile->complainant_name}")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->url(InquiryFileResource::getUrl('view', ['record' => $record->id]))
                        ])
                        ->sendToDatabase($oicUser);

                    // Send SMS if OIC has a phone number
                    if ($oicUser->phone) {
                        $message = "New inquiry file created: {$record->if_number} has been created for case {$pinkFile->complainant_name} by " . Auth::user()->name;
                        SmsService::sendMessage($message, $oicUser->phone);
                    }
                }
            } else {
                Log::error('Failed to find pink file with ID: ' . $record->pink_file_id);
            }
        }
    }
}
