<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The Purchase Officer's on-record decision to award a tender to one applicant, together with
 * how the committee was consulted. See the create migration for why this exists.
 */
class AwardFinalization extends Model
{
    protected $fillable = [
        'tender_id', 'application_id', 'finalized_by', 'discussion_mode', 'discussion_held_at',
        'participants', 'committee_summary', 'decision_note', 'awarded_value',
        'revoked_at', 'revoked_by', 'revoked_reason',
    ];

    protected $casts = [
        'discussion_held_at' => 'datetime',
        'participants'       => 'array',
        'committee_summary'  => 'array',
        'awarded_value'      => 'decimal:2',
        'revoked_at'         => 'datetime',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /** Only one finalization is in force per tender at a time; revoked ones stay for the record. */
    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function wasOral(): bool
    {
        return $this->discussion_mode === 'oral';
    }

    public function getDiscussionModeLabel(): string
    {
        return $this->wasOral() ? 'Oral discussion' : 'Online discussion';
    }

    /**
     * The committee members the officer recorded as taking part, resolved to users.
     */
    public function participantUsers()
    {
        $ids = array_filter(array_map('intval', (array) $this->participants));

        return $ids ? User::whereIn('id', $ids)->orderBy('name')->get() : collect();
    }

    /** The finalization currently in force for a tender, if any. */
    public static function activeFor(int $tenderId): ?self
    {
        return static::active()->where('tender_id', $tenderId)->latest('id')->first();
    }

    /** The finalization currently in force for one application, if any. */
    public static function activeForApplication(int $applicationId): ?self
    {
        return static::active()->where('application_id', $applicationId)->latest('id')->first();
    }
}
