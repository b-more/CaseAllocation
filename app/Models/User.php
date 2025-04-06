<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'image',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the cases assigned to this user.
     */
    public function assignedCases(): HasMany
    {
        return $this->hasMany(CaseAssignment::class, 'officer_id');
    }

    /**
     * Get pink files assigned to this user.
     */
    public function assignedPinkFiles(): HasMany
    {
        return $this->hasMany(PinkFile::class, 'assigned_to');
    }

    /**
     * Get inquiry files where this user is dealing officer.
     */
    public function inquiryFiles(): HasMany
    {
        return $this->hasMany(InquiryFile::class, 'dealing_officer');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(int $roleId): bool
    {
        return $this->role_id === $roleId;
    }

    /**
     * Check if user is OIC
     */
    public function isOIC(): bool
    {
        return $this->role_id === Role::OIC; // 1
    }

    /**
     * Check if user is Admin
     */
    public function isAdmin(): bool
    {
        return $this->role_id === Role::ADMIN; // 3
    }

    /**
     * Check if user is Investigating Officer
     */
    public function isInvestigatingOfficer(): bool
    {
        return $this->role_id === Role::INVESTIGATOR; // 2
    }

    public function welfareContributions(): HasMany
    {
        return $this->hasMany(WelfareContribution::class);
    }

    /**
     * Get welfare contributions recorded by this user.
     */
    public function recordedContributions(): HasMany
    {
        return $this->hasMany(WelfareContribution::class, 'recorded_by');
    }
}
