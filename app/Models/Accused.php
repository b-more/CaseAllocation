<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accused extends Model
{
    use HasFactory;

    protected $table = 'accuseds';

    protected $fillable = [
        'case_id',
        'name',
        'identification',
        'contact',
        'address',
        'details',
    ];

    /**
     * Get the case that owns the accused.
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(InquiryFile::class, 'case_id');
    }
}
