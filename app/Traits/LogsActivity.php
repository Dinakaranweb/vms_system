<?php

namespace App\Traits;

use App\Models\AuditLog;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            AuditLog::log([
                'action' => 'create',
                'module' => $model->getTable(),
                'record_id' => $model->id,
                'record_identifier' => $model->getIdentifier(),
                'new_values' => $model->toArray(),
                'description' => "Created " . class_basename($model),
            ]);
        });

        static::updated(function ($model) {
            AuditLog::log([
                'action' => 'update',
                'module' => $model->getTable(),
                'record_id' => $model->id,
                'record_identifier' => $model->getIdentifier(),
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getChanges(),
                'description' => "Updated " . class_basename($model),
            ]);
        });

        static::deleted(function ($model) {
            AuditLog::log([
                'action' => 'delete',
                'module' => $model->getTable(),
                'record_id' => $model->id,
                'record_identifier' => $model->getIdentifier(),
                'old_values' => $model->toArray(),
                'description' => "Deleted " . class_basename($model),
            ]);
        });
    }

    protected function getIdentifier()
    {
        return $this->name ?? $this->title ?? $this->tender_number ?? $this->contract_number ?? $this->application_number ?? $this->id;
    }
}