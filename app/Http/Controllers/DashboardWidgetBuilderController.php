<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DashboardWidgetBuilderController extends Controller
{
    public function edit($roleId)
    {
        $role = Role::findOrFail($roleId);
        $widgets = DashboardWidget::orderBy('label')->get();
        $assignedIds = DB::table('role_dashboard_widget')->where('role_id', $roleId)->where('is_active', true)->pluck('widget_id')->toArray();

        return view('super-admin.widget-builder.edit', compact('role', 'widgets', 'assignedIds'));
    }

    public function update(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        DB::table('role_dashboard_widget')->where('role_id', $roleId)->delete();

        $selected = collect($request->input('widgets', []))->map(fn($v) => (int) $v);
        $rows = $selected->values()->map(fn($id, $i) => [
            'role_id' => $roleId, 'widget_id' => $id, 'is_active' => true, 'sort_order' => $i,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($rows->isNotEmpty()) {
            DB::table('role_dashboard_widget')->insert($rows->toArray());
        }

        return redirect()->route('super-admin.widget-builder.edit', $roleId)->with('success', "Dashboard widgets updated for the \"{$role->name}\" role!");
    }
}
