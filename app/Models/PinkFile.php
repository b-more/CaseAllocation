<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PinkFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'pink_file_type_id',
        'ig_folio',
        'commissioner_cid_folio',
        'director_c2_folio',
        'assistant_director_c2_comment',
        'oic_comment',
        'complainant_type_id',
        'complainant_name',
        'date_time_of_occurrence',
        'crime_type_id',
        'priority', //very high, high, normal, low
        'assigned_to'
    ];

    protected $casts = [
        'date_time_of_occurrence' => 'datetime',
    ];

    /**
     * Get the file type that owns the pink file.
     */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(PinkFileType::class, 'pink_file_type_id');
    }

    /**
     * Get the complainant type that owns the pink file.
     */
    public function complainantType(): BelongsTo
    {
        return $this->belongsTo(ComplainantType::class);
    }

    /**
     * Get the crime type that owns the pink file.
     */
    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    /**
     * Get the officer assigned to the pink file.
     */
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the inquiry file created from this pink file.
     */
    public function inquiryFile(): HasOne
    {
        return $this->hasOne(InquiryFile::class);
    }

    /**
     * Get priority options
     */
    public static function getPriorityOptions(): array
    {
        return [
            'very_high' => 'Very High',
            'high' => 'High',
            'normal' => 'Normal',
            'low' => 'Low',
        ];
    }
}
