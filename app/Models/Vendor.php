<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_code', 'company_name', 'email', 'phone', 'address',
        'city', 'state', 'pincode', 'pan_number', 'gst_number', 'has_gst',
        'status', 'rejection_reason', 'approved_by', 'approved_at',
        'officer_approved_by', 'officer_approved_at',
        'registration_documents', 'bank_details'
    ];

    protected $casts = [
        'registration_documents' => 'array',
        'bank_details' => 'array',
        'approved_at' => 'datetime',
        'officer_approved_at' => 'datetime',
        'has_gst' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($vendor) {
            $vendor->vendor_code = 'VEN' . str_pad(static::count() + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    /**
     * The final Purchase Committee member whose approval made this a real vendor — or, where
     * no committee was configured, the Purchase Officer who approved it alone.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** The Purchase Officer who cleared the registration and started the committee chain. */
    public function officerApprover()
    {
        return $this->belongsTo(User::class, 'officer_approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(VendorApproval::class)->orderBy('approval_order');
    }

    public function applications()
    {
        return $this->hasMany(TenderApplication::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_vendor')->withTimestamps();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * The address to write to, repairing the row on the way if it can.
     *
     * vendors.email has been blank on rows created before registration filled it in, and a
     * blank column silently dropped the approval mail — the one message a vendor most needs.
     * So fall back to the address they actually sign in with, and backfill the column while
     * we have it, since the next send would otherwise hit the same hole.
     *
     * Lives on the model because both the Purchase Officer's screens and the committee's
     * approval chain send vendor-facing mail, and the two must not drift apart.
     */
    public function resolveMailAddress(): ?string
    {
        $onVendor = trim((string) $this->email);
        if ($onVendor !== '') {
            return $onVendor;
        }

        $onLogin = trim((string) ($this->user->email ?? ''));
        if ($onLogin === '') {
            return null;
        }

        try {
            $this->forceFill(['email' => $onLogin])->save();
            \Log::info("Backfilled vendors.email for vendor {$this->id} from its login address.");
        } catch (\Throwable $e) {
            \Log::warning("Could not backfill vendors.email for vendor {$this->id}: " . $e->getMessage());
        }

        return $onLogin;
    }

    // ─── Committee approval chain ───────────────────────────────────────────

    public function scopeAwaitingCommittee($query)
    {
        return $query->where('status', 'committee_review');
    }

    /**
     * The Purchase Officer has cleared this registration and the committee is now working
     * through it. The vendor cannot sign in and is not yet a vendor to anyone else.
     */
    public function isAwaitingCommittee(): bool
    {
        return $this->status === 'committee_review';
    }

    /** Still somewhere short of approved: with the officer, or with the committee. */
    public function isAwaitingDecision(): bool
    {
        return $this->isPending() || $this->isAwaitingCommittee();
    }

    /**
     * The one step the chain is waiting on, or null when nothing is outstanding.
     *
     * Deliberately ignores approved and rejected steps rather than taking the first row, so a
     * member who has already decided is never asked twice.
     */
    public function currentApprovalStep(): ?VendorApproval
    {
        if (!$this->isAwaitingCommittee()) {
            return null;
        }

        return $this->approvals()->where('status', 'pending')->orderBy('approval_order')->first();
    }

    /** True when the step given is the last one standing — approving it approves the vendor. */
    public function isFinalApprovalStep(VendorApproval $step): bool
    {
        return !$this->approvals()
            ->where('status', 'pending')
            ->where('id', '!=', $step->id)
            ->exists();
    }

    /** "2 of 4" for the progress line on the officer's and the committee's screens. */
    public function approvalProgress(): array
    {
        $all  = $this->approvals;
        $done = $all->where('status', 'approved')->count();

        return ['decided' => $done, 'total' => $all->count()];
    }
}