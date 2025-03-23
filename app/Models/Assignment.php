<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pink_file_id',
        'inquiry_file_id',
        'assigned_by',
        'assigned_to',
        'assigned_at',
        'assignment_notes',
        'is_priority',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'is_priority' => 'boolean',
    ];

    /**
     * Get the pink file for this assignment
     */
    public function pinkFile(): BelongsTo
    {
        return $this->belongsTo(PinkFile::class);
    }

    /**
     * Get the inquiry file for this assignment
     */
    public function inquiryFile(): BelongsTo
    {
        return $this->belongsTo(InquiryFile::class);
    }

    /**
     * Get the user who made the assignment (OIC)
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who was assigned (Investigator)
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get case name based on file type
     */
    public function getCaseName()
    {
        if ($this->inquiryFile) {
            return $this->inquiryFile->if_number . ' - ' . $this->inquiryFile->complainant;
        } elseif ($this->pinkFile) {
            return $this->pinkFile->complainant_name;
        }

        return 'Unknown Case';
    }

    /**
     * Get the current status of the case
     */
    public function getCurrentStatus()
    {
        if ($this->inquiryFile && $this->inquiryFile->status) {
            return $this->inquiryFile->status->name;
        }

        return 'New Case';
    }

    /**
     * Get the latest OIC comment
     */
    public function getLatestComment()
    {
        if ($this->inquiryFile) {
            $latestStatus = CaseStatus::where('case_id', $this->inquiryFile->id)
                ->whereNotNull('oic_comment')
                ->latest()
                ->first();

            return $latestStatus ? $latestStatus->oic_comment : null;
        }

        return $this->assignment_notes;
    }
}
