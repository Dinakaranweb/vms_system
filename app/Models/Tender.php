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
    ];

    protected $casts = [
     //   'documents' => 'array',
        'pre_bid_queries' => 'array',
        'corrigendum' => 'array',
        'estimated_value' => 'decimal:2',
        'emda_amount' => 'decimal:2',
        'published_date' => 'date',
        'submission_deadline' => 'date',
        'opening_date' => 'date',
        'postal_receipt_deadline' => 'date',
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
        if ($this->isOnline()) {
            return '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Online</span>';
        }
        return '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Postal</span>';
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