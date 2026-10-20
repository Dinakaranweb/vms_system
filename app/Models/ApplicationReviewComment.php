<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationReviewComment extends Model
{
    protected $fillable = [
        'application_id', 'committee_member_id', 'comment', 'verdict',
    ];

    /** A member may leave a remark without taking a position, so this can be null. */
    public function hasVerdict(): bool
    {
        return $this->verdict !== null;
    }

    public function getVerdictLabel(): string
    {
        return match ($this->verdict) {
            'support' => 'Supports award',
            'oppose'  => 'Opposes award',
            'neutral' => 'No objection',
            default   => 'Remark only',
        };
    }

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function committeeMember()
    {
        return $this->belongsTo(User::class, 'committee_member_id');
    }
}
