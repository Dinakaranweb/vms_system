<?php

namespace App\Traits;

use App\Models\DashboardWidget;
use App\Services\WidgetDataProvider;

/**
 * Lets any dashboard controller pull in the Super-Admin-configured widgets for the current
 * user's role, on top of whatever that dashboard already renders natively.
 */
trait HasDashboardWidgets
{
    protected function getDashboardWidgetData()
    {
        $roleId = auth()->user()?->roles->first()?->id;
        if (!$roleId) {
            return collect();
        }

        $provider = app(WidgetDataProvider::class);

        return DashboardWidget::activeForRole($roleId)->map(fn($widget) => [
            'widget' => $widget,
            'data'   => $provider->get($widget->data_source),
        ]);
    }
}
