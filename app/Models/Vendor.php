<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_code', 'company_name', 'email', 'phone', 'address',
        'city', 'state', 'pincode', 'pan_number', 'gst_number', 'has_gst',
        'status', 'rejection_reason', 'approved_by', 'approved_at',
        'registration_documents', 'bank_details'
    ];

    protected $casts = [
        'registration_documents' => 'array',
        'bank_details' => 'array',
        'approved_at' => 'datetime',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
}