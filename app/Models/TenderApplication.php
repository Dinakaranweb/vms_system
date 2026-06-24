<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class TenderApplication extends Model
{
    use HasFactory;

    protected $table = 'tender_applications';

    protected $fillable = [
        'application_number',
        'tender_id',
        'vendor_id',
        'bid_amount',
        'emda_paid_amount',
        'emda_reference_number',
        'technical_proposal',
        'technical_proposal_file',
        'financial_proposal',
        'financial_proposal_file',
        'documents',
        'status',
        'evaluation_scores',
        'admin_remarks',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'evaluation_scores' => 'array',
        'bid_amount' => 'decimal:2',
        'emda_paid_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // Boot method to generate application number
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($application) {
            if (!$application->application_number) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $application->application_number = 'APP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            }
        });
        
        // Auto-set submitted_at when status changes to submitted
        static::updating(function ($application) {
            if ($application->isDirty('status') && $application->status === 'submitted' && !$application->submitted_at) {
                $application->submitted_at = now();
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

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function eligibilityAnswers()
    {
        return $this->hasMany(EligibilityAnswer::class, 'tender_application_id');
    }

    // ==================== SCOPES ====================
    
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeAwarded($query)
    {
        return $query->where('status', 'awarded');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByTender($query, $tenderId)
    {
        return $query->where('tender_id', $tenderId);
    }

    public function scopeHighestBid($query)
    {
        return $query->orderBy('bid_amount', 'desc');
    }

    public function scopeLowestBid($query)
    {
        return $query->orderBy('bid_amount', 'asc');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    // ==================== HELPER METHODS ====================
    
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isShortlisted(): bool
    {
        return $this->status === 'shortlisted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isAwarded(): bool
    {
        return $this->status === 'awarded';
    }

    public function isWithdrawn(): bool
    {
        return $this->status === 'withdrawn';
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeShortlisted(): bool
    {
        return $this->status === 'under_review';
    }

    public function canBeAwarded(): bool
    {
        return $this->status === 'shortlisted';
    }

    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ['submitted', 'draft']);
    }

    public function getEvaluationScore(): ?float
    {
        return $this->evaluation_scores['total_score'] ?? null;
    }

    public function getTechnicalScore(): ?float
    {
        return $this->evaluation_scores['technical_score'] ?? null;
    }

    public function getFinancialScore(): ?float
    {
        return $this->evaluation_scores['financial_score'] ?? null;
    }

    public function getStatusBadge(): string
    {
        $badges = [
            'submitted' => '<span class="badge badge-warning">Submitted</span>',
            'under_review' => '<span class="badge badge-info">Under Review</span>',
            'shortlisted' => '<span class="badge badge-success">Shortlisted</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
            'awarded' => '<span class="badge badge-primary">Awarded</span>',
            'withdrawn' => '<span class="badge badge-secondary">Withdrawn</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>';
    }

    public function getFormattedBidAmount(): string
    {
        return '₹ ' . number_format($this->bid_amount, 2);
    }

    public function getFormattedEmdaAmount(): string
    {
        return '₹ ' . number_format($this->emda_paid_amount ?? 0, 2);
    }

    // ==================== ACTION METHODS ====================
    
    public function shortlist($remarks = null)
    {
        $this->update([
            'status' => 'shortlisted',
            'admin_remarks' => $remarks,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return $this;
    }

    public function reject($reason)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return $this;
    }

    public function award()
    {
        $this->update([
            'status' => 'awarded',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return $this;
    }

    public function withdraw()
    {
        $this->update([
            'status' => 'withdrawn',
        ]);
        
        return $this;
    }

    public function submitEvaluation($scores)
    {
        $this->update([
            'evaluation_scores' => $scores,
            'status' => 'under_review',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        
        return $this;
    }

    // ==================== DOCUMENT METHODS ====================
    
    public function getDocumentUrls(): array
    {
        $urls = [];
        foreach ($this->documents ?? [] as $document) {
            $urls[] = [
                'name' => $document['name'] ?? 'Document',
                'url' => Storage::url($document['path']),
                'size' => $this->formatFileSize($document['size'] ?? 0),
                'type' => pathinfo($document['name'] ?? '', PATHINFO_EXTENSION),
            ];
        }
        
        return $urls;
    }

    public function getDocumentsCount(): int
    {
        return count($this->documents ?? []);
    }

    public function hasDocuments(): bool
    {
        return !empty($this->documents) && count($this->documents) > 0;
    }

    private function formatFileSize($bytes): string
    {
        if ($bytes <= 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // ==================== COMPARISON METHODS ====================
    
    public function isHigherBidThan($otherApplication): bool
    {
        return $this->bid_amount > $otherApplication->bid_amount;
    }

    public function isLowerBidThan($otherApplication): bool
    {
        return $this->bid_amount < $otherApplication->bid_amount;
    }

    public function getBidRank(): int
    {
        return TenderApplication::where('tender_id', $this->tender_id)
            ->where('bid_amount', '>', $this->bid_amount)
            ->count() + 1;
    }

    public function getTotalBidsCount(): int
    {
        return TenderApplication::where('tender_id', $this->tender_id)->count();
    }
}