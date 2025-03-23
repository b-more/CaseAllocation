<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplainantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the pink files for this complainant type.
     */
    public function pinkFiles(): HasMany
    {
        return $this->hasMany(PinkFile::class);
    }
}
