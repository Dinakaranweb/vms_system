<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class DashboardWidget extends Model
{
    protected $fillable = [
        'key', 'label', 'description', 'widget_type', 'data_source', 'icon',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_dashboard_widget', 'widget_id', 'role_id')
            ->withPivot('is_active', 'sort_order')
            ->withTimestamps();
    }

    public static function activeForRole(int $roleId)
    {
        return static::query()
            ->join('role_dashboard_widget', 'dashboard_widgets.id', '=', 'role_dashboard_widget.widget_id')
            ->where('role_dashboard_widget.role_id', $roleId)
            ->where('role_dashboard_widget.is_active', true)
            ->orderBy('role_dashboard_widget.sort_order')
            ->select('dashboard_widgets.*')
            ->get();
    }
}
