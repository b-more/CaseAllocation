<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // Define role constants for easier reference
    // Match these to your seeder values
    const OIC = 1;
    const INVESTIGATOR = 2;
    const ADMIN = 3;

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
