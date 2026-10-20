<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseCommitteeMember extends Model
{
    protected $fillable = [
        'user_id', 'designation', 'approval_order', 'is_active', 'added_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_order');
    }
}
