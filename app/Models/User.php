<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'vendor_id', 'is_active', 'last_login_at'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    public function ownedTenders()
    {
        return $this->hasMany(Tender::class, 'owner_id');
    }
    
    public function committeeTenders()
    {
        return $this->belongsToMany(Tender::class, 'tender_committee', 'user_id', 'tender_id')
                    ->withPivot('role', 'designation', 'permissions', 'is_active', 'added_by')
                    ->withTimestamps();
    }
    
    // ==================== ROLE CHECK METHODS ====================
    
    public function isSuperAdmin()
    {
        return $this->hasRole('super-admin');
    }
    
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
    
    public function isVendor()
    {
        return $this->hasRole('vendor');
    }
    
    public function isTenderOwner()
    {
        return $this->hasRole('tender-owner');
    }
    
    // ==================== TENDER ACCESS METHODS ====================
    
    /**
     * Check if user is the owner of a specific tender
     */
    public function isTenderOwnerOf($tenderId = null)
    {
        if ($tenderId) {
            return $this->ownedTenders()->where('id', $tenderId)->exists();
        }
        return $this->hasRole('tender-owner') || $this->ownedTenders()->exists();
    }
    
    /**
     * Check if user is a committee member for a specific tender
     */
    public function isCommitteeMember($tenderId)
    {
        return $this->committeeTenders()
            ->where('tender_id', $tenderId)
            ->wherePivot('is_active', true)
            ->exists();
    }
    
    /**
     * Check if user is a committee member for any tender
     */
    public function isCommitteeMemberForAnyTender()
    {
        return $this->committeeTenders()->wherePivot('is_active', true)->exists();
    }
    
    /**
     * Get committee permissions for a specific tender
     */
    public function getCommitteePermissions($tenderId)
    {
        $committee = $this->committeeTenders()
            ->where('tender_id', $tenderId)
            ->first();
        
        if ($committee) {
            return json_decode($committee->pivot->permissions, true) ?? [];
        }
        
        return [];
    }
    
    /**
     * Check if committee member has specific permission for a tender
     */
    public function hasCommitteePermission($tenderId, $permission)
    {
        $permissions = $this->getCommitteePermissions($tenderId);
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user can manage a specific tender
     */
    public function canManageTender($tenderId)
    {
        // Super admin can manage all
        if ($this->isSuperAdmin()) return true;
        
        // Admin can manage all
        if ($this->isAdmin()) return true;
        
        // Owner can manage their own tenders
        if ($this->isTenderOwnerOf($tenderId)) return true;
        
        // Committee member can manage based on permissions
        if ($this->isCommitteeMember($tenderId)) return true;
        
        return false;
    }
    
    /**
     * Check if user can access a specific tender
     */
    public function canAccessTender($tenderId)
    {
        // Super admin can access all
        if ($this->isSuperAdmin()) return true;
        
        // Admin can access all
        if ($this->isAdmin()) return true;
        
        // Tender owner can access their own tenders
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        // Committee member can access tenders they are assigned to
        if ($this->isCommitteeMember($tenderId)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all tenders accessible by the user
     */
    public function getAccessibleTenders()
    {
        // Super admin and admin can see all tenders
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return Tender::query();
        }
        
        // Tender owner sees their own tenders
        if ($this->isTenderOwner()) {
            return Tender::where('owner_id', $this->id);
        }
        
        // Committee member sees tenders they are assigned to
        if ($this->isCommitteeMemberForAnyTender()) {
            $committeeTenderIds = $this->committeeTenders()
                ->wherePivot('is_active', true)
                ->pluck('tender_id');
            return Tender::whereIn('id', $committeeTenderIds);
        }
        
        // Vendors see only published tenders
        return Tender::where('status', 'published');
    }
    
    /**
     * Get all applications accessible by the user
     */
    public function getAccessibleApplications()
    {
        // Super admin and admin can see all applications
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return TenderApplication::query();
        }
        
        // Tender owner sees applications for their own tenders
        if ($this->isTenderOwner()) {
            $tenderIds = $this->ownedTenders()->pluck('id');
            return TenderApplication::whereIn('tender_id', $tenderIds);
        }
        
        // Committee member sees applications for tenders they are assigned to
        if ($this->isCommitteeMemberForAnyTender()) {
            $committeeTenderIds = $this->committeeTenders()
                ->wherePivot('is_active', true)
                ->pluck('tender_id');
            return TenderApplication::whereIn('tender_id', $committeeTenderIds);
        }
        
        // Vendor sees their own applications
        if ($this->isVendor() && $this->vendor) {
            return TenderApplication::where('vendor_id', $this->vendor->id);
        }
        
        return TenderApplication::whereRaw('1 = 0'); // Return empty query
    }
    
    // ==================== DOCUMENT ACCESS METHODS ====================
    
    /**
     * Check if user can delete a document
     */
    public function canDeleteDocument($document)
    {
        // Super admin can delete any document
        if ($this->isSuperAdmin()) return true;
        
        // Admin can delete any document
        if ($this->isAdmin()) return true;
        
        // Tender owner can delete documents from their own tenders
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($document->tender_id)) {
            return true;
        }
        
        // Committee member with delete permission
        if ($this->isCommitteeMember($document->tender_id) && 
            $this->hasCommitteePermission($document->tender_id, 'delete_documents')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can upload documents to a tender
     */
    public function canUploadDocument($tenderId)
    {
        // Super admin can upload
        if ($this->isSuperAdmin()) return true;
        
        // Admin can upload
        if ($this->isAdmin()) return true;
        
        // Tender owner can upload to their own tenders
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        // Committee member with upload permission
        if ($this->isCommitteeMember($tenderId) && 
            $this->hasCommitteePermission($tenderId, 'upload_documents')) {
            return true;
        }
        
        return false;
    }
    
    // ==================== APPLICATION ACTION METHODS ====================
    
    /**
     * Check if user can shortlist applications for a tender
     */
    public function canShortlistApplications($tenderId)
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) return true;
        
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        if ($this->isCommitteeMember($tenderId) && 
            $this->hasCommitteePermission($tenderId, 'shortlist_applications')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can reject applications for a tender
     */
    public function canRejectApplications($tenderId)
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) return true;
        
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        if ($this->isCommitteeMember($tenderId) && 
            $this->hasCommitteePermission($tenderId, 'reject_applications')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can award applications for a tender
     */
    public function canAwardApplications($tenderId)
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) return true;
        
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        if ($this->isCommitteeMember($tenderId) && 
            $this->hasCommitteePermission($tenderId, 'award_applications')) {
            return true;
        }
        
        return false;
    }
    
    // ==================== COMMITTEE MANAGEMENT METHODS ====================
    
    /**
     * Check if user can manage committee for a tender
     */
    public function canManageCommittee($tenderId)
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) return true;
        
        if ($this->isTenderOwner() && $this->isTenderOwnerOf($tenderId)) {
            return true;
        }
        
        if ($this->isCommitteeMember($tenderId) && 
            $this->hasCommitteePermission($tenderId, 'manage_committee')) {
            return true;
        }
        
        return false;
    }
}