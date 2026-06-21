<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    protected $fillable = [
    'contract_number',
    'tender_id',
    'vendor_id',
    'application_id',
    'start_date',
    'end_date',
    'contract_value',
    'payment_terms',
    'deliverables',
    'terms_and_conditions',
    'status',
    'signed_by_vendor',
    'vendor_signed_at',
    'signed_by_admin',
    'admin_signed_at',
    'contract_documents',
    'termination_reason',
    'extension_logs',
    'current_extension_count',
    // Work / Purchase Order fields
    'order_type',
    'order_number',
    'order_date',
    'vmrfdu_ref',
    'supplier_ref',
    'order_items',
    'gst_rate',
    'order_discount',
    'order_terms',
    'order_notes',
    'order_generated_at',
    'supporting_documents',
    'is_partial_order',
];

    protected $casts = [
        'terms_and_conditions' => 'array',
        'deliverables' => 'array',
        'payment_terms' => 'array',
        'extension_logs' => 'array',
        'contract_documents' => 'array',
        'contract_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'vendor_signed_at' => 'datetime',
        'admin_signed_at' => 'datetime',
        'order_items' => 'array',
        'order_terms' => 'array',
        'gst_rate' => 'decimal:2',
        'order_discount' => 'decimal:2',
        'order_date' => 'date',
        'order_generated_at'   => 'datetime',
        'supporting_documents' => 'array',
        'is_partial_order'     => 'boolean',
    ];

    // Boot method to generate contract number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            if (!$contract->contract_number) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $contract->contract_number = 'CTR-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================
    
    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function vendorSignatory()
    {
        return $this->belongsTo(User::class, 'signed_by_vendor');
    }

    public function adminSignatory()
    {
        return $this->belongsTo(User::class, 'signed_by_admin');
    }

    public function extensions()
    {
        return $this->hasMany(ContractExtension::class);
    }

    public function approvedExtensions()
    {
        return $this->hasMany(ContractExtension::class)->where('status', 'approved');
    }

    // ==================== SCOPES ====================
    
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('end_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
                     ->orWhere('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
                     ->where('end_date', '<=', now()->addDays($days))
                     ->where('end_date', '>=', now());
    }

    // ==================== HELPER METHODS ====================
    
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->end_date->isPast();
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public function isFullySigned(): bool
    {
        return !is_null($this->signed_by_vendor) && !is_null($this->signed_by_admin);
    }

    public function getRemainingDays(): int
    {
        if (!$this->isActive()) return 0;
        return now()->diffInDays($this->end_date, false);
    }

    public function signByVendor()
    {
        $this->update([
            'signed_by_vendor' => auth()->id(),
            'vendor_signed_at' => now(),
        ]);
        
        if ($this->isFullySigned()) {
            $this->update(['status' => 'active']);
        }
        
        return $this;
    }

    public function signByAdmin()
    {
        $this->update([
            'signed_by_admin' => auth()->id(),
            'admin_signed_at' => now(),
        ]);
        
        if ($this->isFullySigned()) {
            $this->update(['status' => 'active']);
        }
        
        return $this;
    }

    public function terminate($reason)
    {
        $this->update([
            'status' => 'terminated',
            'termination_reason' => $reason,
        ]);
        
        return $this;
    }

    public function extend($newEndDate, $additionalValue = null, $reason = null)
    {
        $extension = ContractExtension::create([
            'contract_id' => $this->id,
            'contract_number' => $this->contract_number,
            'extension_number' => $this->current_extension_count + 1,
            'previous_end_date' => $this->end_date,
            'new_end_date' => $newEndDate,
            'extension_days' => $this->end_date->diffInDays($newEndDate),
            'additional_value' => $additionalValue,
            'previous_contract_value' => $this->contract_value,
            'new_contract_value' => $this->contract_value + ($additionalValue ?? 0),
            'reason_for_extension' => $reason,
            'requested_by' => auth()->id(),
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        $logs = $this->extension_logs ?? [];
        $logs[] = [
            'extension_id' => $extension->id,
            'extension_number' => $extension->extension_number,
            'date' => now(),
            'new_end_date' => $newEndDate,
            'additional_value' => $additionalValue,
        ];

        $this->update([
            'extension_logs' => $logs,
        ]);

        return $extension;
    }
}