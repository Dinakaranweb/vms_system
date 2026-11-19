<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tender extends Model
{
    use HasFactory;

    protected $table = 'tenders';

    protected $fillable = [
        'tender_number',
        'title',
        'description',
        'eligibility_criteria',
        'technical_requirements',
        'financial_requirements',
        'estimated_value',
        'emda_amount',
        'published_date',
        'submission_deadline',
        'submission_time',
        'opening_date',
        'opening_time',
        'opening_venue',
        'tender_type',
        'is_private',
        'postal_address',
        'postal_receipt_deadline',
        'status',
       // 'documents',
        'pre_bid_queries',
        'corrigendum',
        'created_by',
        'owner_id',
        'approved_by',
        'approved_at',
        'is_owner_controlled',
        'require_bid_amount',
        'require_technical_bid',
        'require_financial_bid',
        'require_contract_signing',
        'notify_vendors_on_create',
        'notify_specific_vendors',
        'show_on_university',
        'university_description',
        'closed_reason',
        'require_emd',
        'require_estimated_value',
        'email_notifications_enabled',
        'start_date',
        'auto_publish',
        'bid_opening_at',
        'bid_opened_by',
        'review_opened_at',
        'review_opened_by',
    ];

    protected $casts = [
     //   'documents' => 'array',
        'pre_bid_queries' => 'array',
        'corrigendum' => 'array',
        'estimated_value' => 'decimal:2',
        'emda_amount' => 'decimal:2',
        'published_date' => 'date',
        'start_date' => 'date',
        'submission_deadline' => 'date',
        'opening_date' => 'date',
        'postal_receipt_deadline' => 'date',
        'auto_publish' => 'boolean',
        'approved_at' => 'datetime',
        'is_private'               => 'boolean',
        'is_owner_controlled'   => 'boolean',
        'require_bid_amount'       => 'boolean',
        'require_technical_bid'    => 'boolean',
        'require_financial_bid'    => 'boolean',
        'require_contract_signing' => 'boolean',
        'notify_vendors_on_create' => 'boolean',
        'notify_specific_vendors'  => 'array',
        'show_on_university'       => 'boolean',
        'require_emd'              => 'boolean',
        'require_estimated_value'  => 'boolean',
        'email_notifications_enabled' => 'boolean',
        'bid_opening_at' => 'datetime',
        'review_opened_at' => 'datetime',
    ];

    // Boot method to generate tender number automatically
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tender) {
            if (!$tender->tender_number) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $tender->tender_number = 'TDR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

   public function documents()
{
    return $this->hasMany(TenderDocument::class, 'tender_id')->orderBy('order', 'asc');
}

    public function applications()
    {
        return $this->hasMany(TenderApplication::class);
    }

    public function submittedApplications()
    {
        return $this->hasMany(TenderApplication::class)->where('status', 'submitted');
    }

    public function shortlistedApplications()
    {
        return $this->hasMany(TenderApplication::class)->where('status', 'shortlisted');
    }

    public function awardedApplication()
    {
        return $this->hasOne(TenderApplication::class)->where('status', 'awarded');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function eligibilityQuestions()
    {
        return $this->hasMany(EligibilityQuestion::class)->orderBy('order');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_tender')->withTimestamps();
    }

    public function committeeMembers()
    {
        return $this->belongsToMany(User::class, 'tender_committee', 'tender_id', 'user_id')
                    ->withPivot('role', 'designation', 'permissions', 'is_active', 'added_by')
                    ->withTimestamps();
    }

    public function queries()
    {
        return $this->hasMany(TenderQuery::class, 'tender_id')->orderBy('created_at');
    }

    public function bidOpenedBy()
    {
        return $this->belongsTo(User::class, 'bid_opened_by');
    }

    public function awardRecommendations()
    {
        return $this->hasMany(AwardRecommendation::class);
    }

    public function reviewOpenedBy()
    {
        return $this->belongsTo(User::class, 'review_opened_by');
    }

    public function reviewOtps()
    {
        return $this->hasMany(TenderReviewOtp::class);
    }

    // ==================== SCOPES ====================
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'published')
                     ->whereDate('submission_deadline', '>=', now()->toDateString());
    }

    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeOnline($query)
    {
        return $query->where('tender_type', 'online');
    }

    public function scopePostal($query)
    {
        return $query->where('tender_type', 'postal');
    }

    public function scopeHybrid($query)
    {
        return $query->where('tender_type', 'hybrid');
    }

    /**
     * Every tender that expects an online submission — online and hybrid alike.
     */
    public function scopeWithOnlineBids($query)
    {
        return $query->whereIn('tender_type', ['online', 'hybrid']);
    }

    /**
     * Every tender that expects physical documents by post — postal and hybrid alike.
     */
    public function scopeWithPostalBids($query)
    {
        return $query->whereIn('tender_type', ['postal', 'hybrid']);
    }

    /**
     * Filter by the submission-mode tab the UI shows.
     *
     * Each of these screens now offers three tabs - Online, Postal and Hybrid - so every tab
     * maps to exactly one tender_type. A hybrid tender is run as its own procedure rather
     * than as an online tender that also takes post, so it is listed on its own tab and not
     * mixed into the other two. withOnlineBids()/withPostalBids() remain for the places that
     * genuinely ask "does this tender involve online/postal bids at all", such as deciding
     * which fields the apply form renders.
     */
    public function scopeSubmissionTab($query, ?string $tab)
    {
        return match ($tab) {
            'postal' => $query->postal(),
            'hybrid' => $query->hybrid(),
            default  => $query->online(),
        };
    }

    // ==================== HELPER METHODS ====================
    
    public function isOpen(): bool
    {
        // Deadline is inclusive: if deadline is June 18, vendor can apply until end of June 18
        return $this->status === 'published' && $this->submission_deadline->endOfDay()->isFuture();
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || $this->submission_deadline->endOfDay()->isPast();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isOnline(): bool
    {
        return $this->tender_type === 'online';
    }

    public function isPostal(): bool
    {
        return $this->tender_type === 'postal';
    }

    public function isHybrid(): bool
    {
        return $this->tender_type === 'hybrid';
    }

    /**
     * A hybrid tender accepts BOTH an online submission and physical documents by post, so it
     * has to satisfy both code paths. Prefer these two over isOnline()/isPostal() anywhere the
     * question is "does this tender involve online/postal bids at all" rather than "is this
     * tender exclusively online/postal".
     */
    public function hasOnlineBids(): bool
    {
        return $this->tender_type === 'online' || $this->isHybrid();
    }

    public function hasPostalBids(): bool
    {
        return $this->tender_type === 'postal' || $this->isHybrid();
    }

    public function getSubmissionModeLabel(): string
    {
        return match ($this->tender_type) {
            'postal' => 'Postal',
            'hybrid' => 'Hybrid (Online + Postal)',
            default  => 'Online',
        };
    }

    public function isBidOpen(): bool
    {
        return $this->bid_opening_at !== null;
    }

    /**
     * The exact moment this tender is scheduled to be opened: opening_date, at opening_time
     * when one was recorded, otherwise the start of that day. Null when no opening date is on
     * record - opening_date is nullable and both create forms treat it as optional.
     */
    public function openingMoment(): ?\Carbon\Carbon
    {
        if ($this->opening_date === null) {
            return null;
        }

        $moment = \Carbon\Carbon::parse($this->opening_date)->startOfDay();

        if (!empty($this->opening_time)) {
            [$h, $m, $s] = array_pad(explode(':', (string) $this->opening_time), 3, '0');
            $moment->setTime((int) $h, (int) $m, (int) $s);
        }

        return $moment;
    }

    /**
     * A tender with no opening date on record falls back to the submission deadline alone, so
     * tenders created before an opening date was expected cannot be stranded unopenable.
     */
    public function hasOpeningMomentArrived(): bool
    {
        $moment = $this->openingMoment();

        return $moment === null || !$moment->isFuture();
    }

    /**
     * Whether the Purchase Officer may start an opening.
     *
     * The opening date is a published schedule, not a lock. Procurement regularly has to
     * open on or before the advertised moment - every bidder has withdrawn, only one
     * application came in, the committee is only in the room today, a correction has to be
     * issued - and a system that refuses leaves the officer with no lawful way through. So
     * the officer may open from the moment a tender exists, whatever its status, whatever its
     * opening date, and however few applications have arrived. There is no minimum: one
     * application is enough.
     *
     * The single remaining bar is a tender already opened, which cannot be opened twice.
     *
     * This widens *who may start the process*, and nothing else. Opening still issues a code
     * to every active committee member, and what the vendors submitted stays sealed until the
     * last of those codes is keyed in - see areSubmissionsSealed() and isBidOpen(). An early
     * click cannot reveal a single document on its own.
     */
    public function canBeOpened(): bool
    {
        return !$this->isBidOpen();
    }

    /**
     * Why opening is unavailable right now, so the API and every screen give one answer.
     * Null when the tender can in fact be opened.
     */
    public function openingBlockedReason(): ?string
    {
        if ($this->isBidOpen()) {
            return 'Bids for this tender have already been opened.';
        }

        return null;
    }

    /**
     * True when opening now would run ahead of the advertised opening moment.
     *
     * Permitted, but the officer is told before they commit, and the record shows it.
     */
    public function isOpeningAheadOfSchedule(): bool
    {
        return !$this->isBidOpen() && !$this->hasOpeningMomentArrived();
    }

    /**
     * True when starting an opening would also have to stop new submissions.
     *
     * Opening a tender that is still taking bids would let staff read what is already in
     * while more arrives behind it, so the two happen together - see
     * TenderController::openBidding(). Nothing can be submitted after the committee has seen
     * the field, which is the whole point of a sealed bid.
     */
    public function willCloseSubmissionsOnOpening(): bool
    {
        return !$this->isBidOpen() && !$this->isClosed();
    }

    /**
     * Whether what the vendors submitted is still sealed.
     *
     * Everything a vendor sent in - the bid amount and the uploaded documents alike - stays
     * unreadable to staff until the Purchase Committee has opened the tender by entering all
     * of their codes. The officer may *start* that process at any time, including ahead of
     * the advertised opening moment, but starting it reveals nothing on its own: this stays
     * true until the last code lands. Vendors always see their own submission; this governs
     * the staff-facing screens.
     */
    public function areSubmissionsSealed(): bool
    {
        return !$this->isBidOpen();
    }

    /**
     * Opening a tender requires a code from every active Purchase Committee member. The codes
     * are delivered to each member's own notifications, each member reads theirs out to the
     * Purchase Officer in person, and the officer keys them all in on one screen — the tender
     * only opens (bid_opening_at/review_opened_at set together) once the last code is accepted.
     * See TenderController::openBidding() and TenderReviewController::openingOtpPanel().
     */
    public function isOpeningOtpPending(): bool
    {
        return !$this->isBidOpen() && $this->reviewOtps()->exists();
    }

    public function openingOtpsIssuedCount(): int
    {
        return $this->reviewOtps()->count();
    }

    public function openingOtpsVerifiedCount(): int
    {
        return $this->reviewOtps()->whereNotNull('otp_verified_at')->count();
    }

    public function openingOtpsOutstandingCount(): int
    {
        return $this->reviewOtps()->whereNull('otp_verified_at')->count();
    }

    /**
     * Once opening is finalized, every active committee member can reach this tender's
     * submitted documents from their own login — the online uploads, the postal scans, or
     * both for a hybrid tender. Sharing is what review_opened_at records.
     */
    public function areDocumentsSharedWithCommittee(): bool
    {
        return $this->isReviewOpen();
    }

    public function allOpeningOtpsVerified(): bool
    {
        return $this->reviewOtps()->exists() && $this->reviewOtps()->whereNull('otp_verified_at')->doesntExist();
    }

    /**
     * Whether this specific committee member has already verified their own opening code —
     * usable even while the tender is still pending (isReviewUnlockedFor() requires the whole
     * tender to be open, which isn't true yet for the members still waiting on each other).
     */
    public function hasVerifiedOpeningOtp(int $userId): bool
    {
        return $this->reviewOtps()->where('committee_member_id', $userId)->whereNotNull('otp_verified_at')->exists();
    }

    public function isReviewOpen(): bool
    {
        return $this->review_opened_at !== null;
    }

    /**
     * Committee review can only start once bids are opened — otherwise members would be
     * looking at sealed/incomplete data.
     */
    public function canOpenForReview(): bool
    {
        return $this->isBidOpen() && !$this->isReviewOpen();
    }

    /**
     * A committee member can view/comment on this tender's applications once review has been
     * opened AND that specific member has verified their own OTP for it.
     */
    public function isReviewUnlockedFor(int $userId): bool
    {
        if (!$this->isReviewOpen()) return false;

        return $this->reviewOtps()
            ->where('committee_member_id', $userId)
            ->whereNotNull('otp_verified_at')
            ->exists();
    }

    public function isPrivate(): bool
    {
        return (bool) $this->is_private;
    }

    public function isAccessibleByVendor(int $vendorId): bool
    {
        if (!$this->isPrivate()) return true;
        $invited = array_map('intval', (array) ($this->notify_specific_vendors ?? []));
        return in_array($vendorId, $invited, true);
    }

    public function isOwner($userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        return $this->owner_id === $userId;
    }

    public function canUserAccess($userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $user = User::find($userId);
        
        if (!$user) return false;
        if ($user->hasRole('super-admin')) return true;
        if ($user->hasRole('admin')) return true;
        if ($this->isOwner($userId)) return true;
        if ($this->committeeMembers()->where('user_id', $userId)->wherePivot('is_active', true)->exists()) return true;
        
        return false;
    }

    public function getApplicationsCount(): int
    {
        return $this->applications()->count();
    }

    public function getSubmittedApplicationsCount(): int
    {
        return $this->applications()->where('status', 'submitted')->count();
    }

    public function getShortlistedApplicationsCount(): int
    {
        return $this->applications()->where('status', 'shortlisted')->count();
    }

    public function getAwardedApplicationsCount(): int
    {
        return $this->applications()->where('status', 'awarded')->count();
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->isOpen()) return null;
        return (int) now()->diffInDays($this->submission_deadline, false);
    }

    public function getFormattedEstimatedValue(): string
    {
        return '₹ ' . number_format($this->estimated_value ?? 0, 2);
    }

    public function getFormattedEmdaAmount(): string
    {
        return '₹ ' . number_format($this->emda_amount ?? 0, 2);
    }

    public function getCommitteeMembersCount(): int
    {
        return $this->committeeMembers()->count();
    }

    public function hasEligibilityQuestions(): bool
    {
        return $this->eligibilityQuestions()->count() > 0;
    }

    public function hasDocuments(): bool
    {
        return $this->documents()->count() > 0;
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'draft' => '<span class="badge badge-warning">Draft</span>',
            'published' => '<span class="badge badge-success">Published</span>',
            'closed' => '<span class="badge badge-danger">Closed</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>';
    }

    public function getTypeBadge(): string
    {
        if ($this->isHybrid()) {
            return '<span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">Hybrid</span>';
        }
        if ($this->isPostal()) {
            return '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Postal</span>';
        }
        return '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Online</span>';
    }

    // ==================== ACTION METHODS ====================
    
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_date' => now(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        
        return $this;
    }

    public function close()
    {
        $this->update(['status' => 'closed']);
        return $this;
    }

    public function addCommitteeMember($userId, $role, $designation = null, $permissions = [])
    {
        $this->committeeMembers()->attach($userId, [
            'role' => $role,
            'designation' => $designation,
            'permissions' => json_encode($permissions),
            'added_by' => auth()->id(),
            'is_active' => true,
        ]);
        
        return $this;
    }

    public function removeCommitteeMember($userId)
    {
        $this->committeeMembers()->detach($userId);
        return $this;
    }
}