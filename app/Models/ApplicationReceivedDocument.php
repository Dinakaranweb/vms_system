<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * One physical document received against a postal/hybrid application and recorded after the
 * tender was opened. See the create migration for how this differs from the vendor's own
 * portal uploads.
 */
class ApplicationReceivedDocument extends Model
{
    protected $fillable = [
        'tender_id', 'application_id', 'file_name', 'file_path', 'file_type', 'file_size',
        'source', 'batch_id', 'page_number', 'remarks', 'uploaded_by', 'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'file_size'   => 'integer',
        'page_number' => 'integer',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeForApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function isScan(): bool
    {
        return $this->source === 'scan';
    }

    public function getUrl(): string
    {
        // Storage::url() follows whichever driver the public disk is on, so this keeps
        // working when PUBLIC_DISK_DRIVER flips to s3. asset() would always build a
        // local /storage URL and 404 against the bucket.
        return Storage::url($this->file_path);
    }

    /**
     * Whether the file is still on disk. A row can outlive its file — storage cleaned up by
     * hand, a restore from a partial backup — and a reviewer needs to see that plainly rather
     * than click a dead link.
     */
    public function fileExists(): bool
    {
        return (bool) $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    public function getFormattedSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) return '—';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';

        return round($bytes / 1048576, 2) . ' MB';
    }

    /**
     * Removes the file from disk as well, so deleting a mis-scanned page does not leave
     * orphans behind in storage.
     */
    public function deleteWithFile(): void
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }

        $this->delete();
    }
}
