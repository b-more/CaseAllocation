<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InquiryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'if_number',
        'time',
        'date',
        'cr_number',
        'police_station', //police station/post where cr number was obtained
        'complainant',
        'offence',
        'value_of_property_stolen',
        'value_of_property_recovered',
        'accused',
        'if_status_id', // under investigation, taken to NPA, Taken to court, case closed
        'case_close_reason',
        'court_type_id', //supreme, constitutional, court of Appeal, High court, subordinat court, local court and specialized courts and tribunals
        'court_stage_id', //Mention, pre-trial, Trial (opening statements, presentation of evidence and witness, cross-examination and closing arguments), Judgement, sentencing, Appeal
        'remarks',
        'dealing_officer',
        'meta_data',
        'pink_file_id',
        'acknowledged_at',
        'contacted_complainant',
        'recorded_statement',
        'apprehended_suspects',
        'warned_cautioned',
        'released_on_bond',
    ];

    protected $casts = [
        'time' => 'datetime',
        'date' => 'date',
        'value_of_property_stolen' => 'decimal:2',
        'value_of_property_recovered' => 'decimal:2',
        'meta_data' => 'json',
        'acknowledged_at' => 'datetime',
        'contacted_complainant' => 'boolean',
        'recorded_statement' => 'boolean',
        'apprehended_suspects' => 'boolean',
        'warned_cautioned' => 'boolean',
        'released_on_bond' => 'boolean',
    ];

    /**
     * Get the status that owns the inquiry file.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(IfStatus::class, 'if_status_id');
    }

    /**
     * Get the court type that owns the inquiry file.
     */
    public function courtType(): BelongsTo
    {
        return $this->belongsTo(CourtType::class);
    }

    /**
     * Get the court stage that owns the inquiry file.
     */
    public function courtStage(): BelongsTo
    {
        return $this->belongsTo(CourtStage::class);
    }

    /**
     * Get the dealing officer that owns the inquiry file.
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dealing_officer');
    }

    /**
     * Get the pink file that originated this inquiry file.
     */
    public function pinkFile(): BelongsTo
    {
        return $this->belongsTo(PinkFile::class);
    }

    /**
     * Get the accused persons for this inquiry file.
     */
    public function accused(): HasMany
    {
        return $this->hasMany(Accused::class, 'case_id');
    }

    /**
     * Get the status changes for this inquiry file.
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(CaseStatus::class, 'case_id');
    }

    /**
     * Check if the inquiry file has been acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Acknowledge the inquiry file.
     */
    public function acknowledge(): void
    {
        $this->acknowledged_at = now();
        $this->save();
    }

    /**
     * Generate a inquiry number based on sequence/month/year
     */
    public static function generateInquiryNumber(): string
    {
        $year = date('y');
        $month = date('n');

        // Get the last inquiry number for this month/year
        $lastInquiry = self::where('if_number', 'like', "%/$month/$year")
            ->orderBy(
                \DB::raw('CAST(SUBSTR(if_number, 1, INSTR(if_number, "/") - 1) AS INTEGER)'),
                'desc'
            )
            ->first();

        if ($lastInquiry) {
            // Extract the sequence number from the last inquiry number
            $parts = explode('/', $lastInquiry->if_number);
            $sequence = (int)$parts[0] + 1;
        } else {
            $sequence = 1;
        }

        return $sequence . '/' . $month . '/' . $year;
    }
}
