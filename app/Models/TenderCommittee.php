<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderCommittee extends Model
{
    protected $table = 'tender_committee';
    
    protected $fillable = [
        'tender_id', 'user_id', 'role', 'designation', 'permissions', 'is_active', 'added_by'
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];
    
    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
    
    public function hasPermission($permission)
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }
}