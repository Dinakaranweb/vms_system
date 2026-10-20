<?php

namespace App\Services;

use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Vendor;

/**
 * Reusable, read-only data sources for the dynamic dashboard widget system.
 * Every widget's `data_source` key must have a matching method here.
 */
class WidgetDataProvider
{
    public function get(string $dataSource): array
    {
        return method_exists($this, $dataSource) ? $this->$dataSource() : [];
    }

    protected function tender_stats(): array
    {
        $base = Tender::whereIn('status', ['published', 'closed']);
        return [
            'type' => 'stat_grid',
            'items' => [
                ['label' => 'Published', 'value' => (clone $base)->where('status', 'published')->count(), 'icon' => 'fa-bullhorn', 'color' => 'green'],
                ['label' => 'Closed', 'value' => (clone $base)->where('status', 'closed')->count(), 'icon' => 'fa-flag-checkered', 'color' => 'gray'],
                ['label' => 'Online', 'value' => (clone $base)->where('tender_type', 'online')->count(), 'icon' => 'fa-globe', 'color' => 'blue'],
                ['label' => 'Postal', 'value' => (clone $base)->where('tender_type', 'postal')->count(), 'icon' => 'fa-envelope', 'color' => 'amber'],
                ['label' => 'Hybrid', 'value' => (clone $base)->where('tender_type', 'hybrid')->count(), 'icon' => 'fa-shuffle', 'color' => 'purple'],
            ],
        ];
    }

    protected function recent_tenders(): array
    {
        $tenders = Tender::whereIn('status', ['published', 'closed'])
            ->with('owner')->withCount('applications')
            ->orderByDesc('created_at')->take(8)->get();

        return [
            'type' => 'list',
            'title' => 'Recent Tenders',
            'items' => $tenders->map(fn($t) => [
                'title' => $t->title,
                'subtitle' => $t->tender_number . ' · ' . ($t->owner->name ?? 'Unassigned'),
                'badge' => ucfirst($t->status),
                'meta' => $t->applications_count . ' applications',
                'route' => ['name' => 'portal.tenders.show', 'param' => $t->id],
            ])->all(),
        ];
    }

    protected function vendor_stats(): array
    {
        return [
            'type' => 'stat_grid',
            'items' => [
                ['label' => 'Total Vendors', 'value' => Vendor::count(), 'icon' => 'fa-store', 'color' => 'blue'],
                ['label' => 'Approved', 'value' => Vendor::where('status', 'approved')->count(), 'icon' => 'fa-check-circle', 'color' => 'green'],
                ['label' => 'Pending', 'value' => Vendor::where('status', 'pending')->count(), 'icon' => 'fa-clock', 'color' => 'amber'],
            ],
        ];
    }

    protected function application_stats(): array
    {
        $base = TenderApplication::query();
        return [
            'type' => 'stat_grid',
            'items' => [
                ['label' => 'Total', 'value' => (clone $base)->count(), 'icon' => 'fa-file-alt', 'color' => 'blue'],
                ['label' => 'Submitted', 'value' => (clone $base)->where('status', 'submitted')->count(), 'icon' => 'fa-paper-plane', 'color' => 'amber'],
                ['label' => 'Shortlisted', 'value' => (clone $base)->where('status', 'shortlisted')->count(), 'icon' => 'fa-star', 'color' => 'purple'],
                ['label' => 'Awarded', 'value' => (clone $base)->where('status', 'awarded')->count(), 'icon' => 'fa-trophy', 'color' => 'green'],
            ],
        ];
    }
}
