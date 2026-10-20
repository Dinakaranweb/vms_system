<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'user_role',
        'action', 'module', 'record_id', 'record_identifier',
        'old_values', 'new_values', 'changes',
        'ip_address', 'user_agent', 'url', 'method',
        'description', 'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public static function log($data)
    {
        $request = request();
        
        return static::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? null,
            'user_email' => auth()->user()->email ?? null,
            'user_role' => auth()->user() ? auth()->user()->getRoleNames()->first() : null,
            'action' => $data['action'],
            'module' => $data['module'],
            'record_id' => $data['record_id'] ?? null,
            'record_identifier' => $data['record_identifier'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'changes' => $data['changes'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public static function logVendorAction($vendor, $action, $changes = null)
    {
        return self::log([
            'action' => $action,
            'module' => 'vendor',
            'record_id' => $vendor->id,
            'record_identifier' => $vendor->company_name,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'description' => "Vendor {$action}: {$vendor->company_name}",
        ]);
    }

    public static function logTenderAction($tender, $action, $changes = null)
    {
        return self::log([
            'action' => $action,
            'module' => 'tender',
            'record_id' => $tender->id,
            'record_identifier' => $tender->tender_number,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'description' => "Tender {$action}: {$tender->tender_number} - {$tender->title}",
        ]);
    }

    public static function logApplicationAction($application, $action, $changes = null)
    {
        return self::log([
            'action' => $action,
            'module' => 'application',
            'record_id' => $application->id,
            'record_identifier' => $application->application_number,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'description' => "Application {$action}: {$application->application_number}",
        ]);
    }

    public static function logContractAction($contract, $action, $changes = null)
    {
        return self::log([
            'action' => $action,
            'module' => 'contract',
            'record_id' => $contract->id,
            'record_identifier' => $contract->contract_number,
            'old_values' => $changes['old'] ?? null,
            'new_values' => $changes['new'] ?? null,
            'description' => "Contract {$action}: {$contract->contract_number}",
        ]);
    }

    public static function logLogin($user, $status = 'success')
    {
        return self::log([
            'action' => $status === 'success' ? 'login' : 'login_failed',
            'module' => 'auth',
            'record_id' => $user->id ?? null,
            'record_identifier' => $user->email ?? null,
            'description' => "User login {$status}: " . ($user->email ?? 'unknown'),
        ]);
    }

    public static function logLogout($user)
    {
        return self::log([
            'action' => 'logout',
            'module' => 'auth',
            'record_id' => $user->id,
            'record_identifier' => $user->email,
            'description' => "User logout: {$user->email}",
        ]);
    }

    // Get action badge color
    public function getActionColor()
    {
        $colors = [
            'create' => 'green',
            'update' => 'blue',
            'delete' => 'red',
            'approve' => 'success',
            'reject' => 'danger',
            'login' => 'info',
            'logout' => 'warning',
            'password_reset' => 'warning',
        ];
        
        return $colors[$this->action] ?? 'secondary';
    }

    // Get formatted action
    public function getFormattedAction()
    {
        $labels = [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'approve' => 'Approved',
            'reject' => 'Rejected',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'publish' => 'Published',
            'close' => 'Closed',
            'award' => 'Awarded',
        ];
        
        return $labels[$this->action] ?? ucfirst($this->action);
    }
}