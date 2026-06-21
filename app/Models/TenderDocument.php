<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class TenderDocument extends Model
{
    use HasFactory;

    protected $table = 'tender_documents';

   protected $fillable = [
    'tender_id', 'tender_number',
    'title', 'description',
    'file_name', 'file_path', 'file_type', 'file_size', 'file_extension', 'document_hash',
    'document_category', 'is_mandatory', 'is_confidential', 'is_public',
    'version', 'language', 'previous_version_id', 'change_notes', 'is_latest',
    'order', // Add this line
    'uploaded_by', 'uploaded_at', 'approved_by', 'approved_at',
    'requires_signature', 'is_signed', 'signature_path',
];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_confidential' => 'boolean',
        'is_public' => 'boolean',
        'is_latest' => 'boolean',
        'requires_signature' => 'boolean',
        'is_signed' => 'boolean',
        'file_size' => 'integer',
        'version' => 'integer',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function previousVersion()
    {
        return $this->belongsTo(TenderDocument::class, 'previous_version_id');
    }

    public function nextVersions()
    {
        return $this->hasMany(TenderDocument::class, 'previous_version_id');
    }

    // Scopes
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeLatest($query)
    {
        return $query->where('is_latest', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('document_category', $category);
    }

    // Helper Methods
    public function getFileUrl()
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getCategoryLabel()
    {
        $labels = [
            'tender_notice' => 'Tender Notice',
            'bidding_document' => 'Bidding Document',
            'corrigendum' => 'Corrigendum',
            'annexure' => 'Annexure',
            'specification' => 'Specification',
            'terms_conditions' => 'Terms & Conditions',
            'boq' => 'Bill of Quantities',
            'drawing' => 'Drawing',
            'clarification' => 'Clarification',
            'pre_bid_meeting_minutes' => 'Pre-bid Meeting Minutes',
            'other' => 'Other',
        ];
        
        return $labels[$this->document_category] ?? ucfirst($this->document_category);
    }

    public function isPdf()
    {
        return $this->file_type === 'application/pdf';
    }

    public function isImage()
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function isWord()
    {
        return in_array($this->file_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    public function approve()
    {
        $this->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        
        return $this;
    }

    public function newVersion($data)
    {
        // Mark current version as not latest
        $this->update(['is_latest' => false]);
        
        // Create new version
        $newVersion = $this->replicate();
        $newVersion->version = $this->version + 1;
        $newVersion->previous_version_id = $this->id;
        $newVersion->is_latest = true;
        $newVersion->fill($data);
        $newVersion->save();
        
        return $newVersion;
    }

    public function canDownload($user)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }
        
        if ($this->is_public) {
            return true;
        }
        
        if ($user->hasRole('vendor')) {
            // Vendors can download if tender is published
            return $this->tender->status === 'published';
        }
        
        return false;
    }
}