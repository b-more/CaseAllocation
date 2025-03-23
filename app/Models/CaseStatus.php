<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'old_status',
        'new_status',
        'reason',
        'oic_comment',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the case that owns the status change.
     */
    public function case(): BelongsTo
    {
        return $this->belongsTo(InquiryFile::class, 'case_id');
    }

    /**
     * Get the user that initiated the status change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
