<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IfStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the inquiry files for this status.
     */
    public function inquiryFiles(): HasMany
    {
        return $this->hasMany(InquiryFile::class);
    }
}
