<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourtStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the inquiry files for this court stage.
     */
    public function inquiryFiles(): HasMany
    {
        return $this->hasMany(InquiryFile::class);
    }
}
