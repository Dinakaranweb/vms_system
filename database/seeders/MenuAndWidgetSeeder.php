<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MenuAndWidgetSeeder extends Seeder
{
    public function run(): void
    {
        // ========== SECTION-LEVEL CATALOG (coarse hide/show for the 6 built-in roles) ==========
        $sections = [
            ['key' => 'vendor-management',     'label' => 'Vendor Management',     'roles' => ['admin', 'super-admin']],
            ['key' => 'tender-management',     'label' => 'Tender Management',     'roles' => ['admin', 'super-admin']],
            ['key' => 'applications',          'label' => 'Applications',          'roles' => ['admin', 'super-admin']],
            ['key' => 'contract-management',   'label' => 'Contract Management',   'roles' => ['admin', 'super-admin']],
            ['key' => 'vendor-tenders',        'label' => 'Tenders (Vendor)',      'roles' => ['vendor']],
            ['key' => 'tender-owner-portal',   'label' => 'Tender Owner Portal',   'roles' => ['tender-owner']],
            ['key' => 'committee-portal',      'label' => 'Committee Portal',      'roles' => ['committee-member']],
            ['key' => 'management-portal',     'label' => 'Management Portal',     'roles' => ['management']],
            ['key' => 'system-administration', 'label' => 'System Administration', 'roles' => ['admin', 'super-admin']],
            ['key' => 'super-admin-tools',     'label' => 'Super Admin Tools (Manage Admins / Audit Logs / Settings)', 'roles' => ['super-admin']],
        ];

        foreach ($sections as $i => $s) {
            $menuItem = MenuItem::updateOrCreate(
                ['key' => $s['key']],
                ['label' => $s['label'], 'scope_type' => 'section', 'sort_order' => $i]
            );

            foreach ($s['roles'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if (!$role) continue;

                DB::table('role_menu_item')->updateOrInsert(
                    ['role_id' => $role->id, 'menu_item_id' => $menuItem->id],
                    ['is_visible' => true, 'sort_order' => $i, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        // ========== ITEM-LEVEL CATALOG (fully dynamic Portal menu for custom roles) ==========
        $portalItems = [
            ['key' => 'portal.dashboard',     'label' => 'Dashboard',   'icon' => 'fa-chart-line',   'route_name' => 'portal.dashboard',     'section_label' => 'Portal'],
            ['key' => 'portal.tenders.index', 'label' => 'All Tenders', 'icon' => 'fa-layer-group',  'route_name' => 'portal.tenders.index', 'section_label' => 'Portal'],
        ];

        foreach ($portalItems as $i => $item) {
            MenuItem::updateOrCreate(
                ['key' => $item['key']],
                [
                    'label'          => $item['label'],
                    'icon'           => $item['icon'],
                    'route_name'     => $item['route_name'],
                    'section_label'  => $item['section_label'],
                    'scope_type'     => 'item',
                    'permission_name'=> 'view all tenders',
                    'sort_order'     => $i,
                ]
            );
        }

        // ========== DASHBOARD WIDGET CATALOG ==========
        $widgets = [
            ['key' => 'tender_stats',      'label' => 'Tender Stats',       'description' => 'Published/Closed/Online/Postal counts', 'widget_type' => 'stat_grid', 'data_source' => 'tender_stats',      'icon' => 'fa-layer-group'],
            ['key' => 'recent_tenders',    'label' => 'Recent Tenders',     'description' => 'Latest published/closed tenders',        'widget_type' => 'list',      'data_source' => 'recent_tenders',    'icon' => 'fa-clock'],
            ['key' => 'vendor_stats',      'label' => 'Vendor Stats',       'description' => 'Total/Approved/Pending vendor counts',   'widget_type' => 'stat_grid', 'data_source' => 'vendor_stats',      'icon' => 'fa-store'],
            ['key' => 'application_stats', 'label' => 'Application Stats',  'description' => 'Total/Submitted/Shortlisted/Awarded',    'widget_type' => 'stat_grid', 'data_source' => 'application_stats', 'icon' => 'fa-file-alt'],
        ];

        foreach ($widgets as $w) {
            DashboardWidget::updateOrCreate(['key' => $w['key']], $w);
        }

        $this->command->info('Menu items and dashboard widgets seeded.');
    }
}
