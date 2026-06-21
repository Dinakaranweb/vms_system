<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractExtension extends Model
{
    use HasFactory;

    protected $table = 'contract_extensions';

    protected $fillable = [
        'contract_id', 'contract_number',
        'extension_number', 'previous_end_date', 'new_end_date', 'extension_days',
        'additional_value', 'previous_contract_value', 'new_contract_value',
        'reason_for_extension', 'terms_changes', 'modified_clauses',
        'extension_document', 'supporting_documents',
        'status', 'requested_by', 'requested_at',
        'approved_by', 'approved_at', 'approval_remarks', 'rejection_reason',
        'vendor_notified', 'vendor_notified_at', 'admin_notified',
    ];

    protected $casts = [
        'terms_changes' => 'array',
        'modified_clauses' => 'array',
        'supporting_documents' => 'array',
        'previous_end_date' => 'date',
        'new_end_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'vendor_notified_at' => 'datetime',
        'additional_value' => 'decimal:2',
        'previous_contract_value' => 'decimal:2',
        'new_contract_value' => 'decimal:2',
        'extension_days' => 'integer',
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    // Helper Methods
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

    public function approve($remarks = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_remarks' => $remarks,
        ]);

        // Update the contract
        $this->contract->update([
            'end_date' => $this->new_end_date,
            'contract_value' => $this->new_contract_value,
            'current_extension_count' => $this->extension_number,
        ]);

        return $this;
    }

    public function reject($reason)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    public function getExtensionDuration()
    {
        return $this->previous_end_date->diffInDays($this->new_end_date);
    }

    public function getValueIncrease()
    {
        return ($this->new_contract_value ?? 0) - ($this->previous_contract_value ?? 0);
    }

    public function getValueIncreasePercentage()
    {
        if (!$this->previous_contract_value || $this->previous_contract_value == 0) {
            return 0;
        }
        
        return ($this->getValueIncrease() / $this->previous_contract_value) * 100;
    }
}