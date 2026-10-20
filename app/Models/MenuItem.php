<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class MenuItem extends Model
{
    protected $fillable = [
        'key', 'label', 'icon', 'route_name', 'section_label',
        'scope_type', 'permission_name', 'sort_order',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu_item', 'menu_item_id', 'role_id')
            ->withPivot('is_visible', 'sort_order')
            ->withTimestamps();
    }

    /**
     * Menu items assigned+visible for a given role id, ordered.
     */
    public static function visibleForRole(int $roleId, string $scopeType = 'item')
    {
        return static::query()
            ->join('role_menu_item', 'menu_items.id', '=', 'role_menu_item.menu_item_id')
            ->where('role_menu_item.role_id', $roleId)
            ->where('role_menu_item.is_visible', true)
            ->where('menu_items.scope_type', $scopeType)
            ->orderBy('role_menu_item.sort_order')
            ->select('menu_items.*')
            ->get();
    }

    /**
     * Section keys explicitly hidden for a role (used to gate the hardcoded sidebar sections).
     */
    public static function hiddenSectionKeysForRole(int $roleId): array
    {
        return static::query()
            ->join('role_menu_item', 'menu_items.id', '=', 'role_menu_item.menu_item_id')
            ->where('role_menu_item.role_id', $roleId)
            ->where('role_menu_item.is_visible', false)
            ->where('menu_items.scope_type', 'section')
            ->pluck('menu_items.key')
            ->toArray();
    }
}
