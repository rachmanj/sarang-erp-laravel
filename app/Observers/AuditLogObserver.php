<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogObserver
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function created(Model $model)
    {
        $this->logChange($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model)
    {
        if ($model->wasChanged() && $this->hasSignificantChanges($model)) {
            $this->logChange(
                $model,
                'updated',
                $model->getOriginal(),
                $model->getChanges()
            );
        }
    }

    public function deleted(Model $model)
    {
        // Check if model uses soft deletes and if it's a force delete
        if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
            return; // Force delete is handled by forceDeleted method
        }
        $this->logChange($model, 'deleted', $model->getAttributes(), null);
    }

    public function restored(Model $model)
    {
        $this->logChange($model, 'restored', null, $model->getAttributes());
    }

    public function forceDeleted(Model $model)
    {
        $this->logChange($model, 'force_deleted', $model->getAttributes(), null);
    }

    protected function logChange(Model $model, string $action, ?array $oldValues, ?array $newValues)
    {
        if (isset($model->auditLogEnabled) && $model->auditLogEnabled === false) {
            return;
        }

        $entityType = $this->getEntityType($model);
        $entityId = $model->getKey();

        if (!$entityId) {
            return;
        }

        $oldValues = $this->filterSensitiveFields($model, $oldValues);
        $newValues = $this->filterSensitiveFields($model, $newValues);

        $description = $this->generateDescription($model, $action, $oldValues, $newValues);

        $this->auditLogService->log(
            $action,
            $entityType,
            $entityId,
            $oldValues,
            $newValues,
            $description
        );
    }

    protected function getEntityType(Model $model): string
    {
        if (isset($model->auditEntityType)) {
            return $model->auditEntityType;
        }

        $className = class_basename($model);
        return Str::snake($className);
    }

    protected function hasSignificantChanges(Model $model): bool
    {
        $changes = $model->getChanges();
        
        $ignoredFields = ['updated_at', 'created_at'];
        
        foreach ($ignoredFields as $field) {
            unset($changes[$field]);
        }

        if (isset($model->auditLogIgnore)) {
            foreach ($model->auditLogIgnore as $field) {
                unset($changes[$field]);
            }
        }

        return !empty($changes);
    }

    protected function filterSensitiveFields(Model $model, ?array $values): ?array
    {
        if (!$values) {
            return null;
        }

        $sensitiveFields = $model->auditLogSensitive ?? ['password', 'api_token', 'remember_token'];

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '***REDACTED***';
            }
        }

        return $values;
    }

    protected function generateDescription(Model $model, string $action, ?array $oldValues, ?array $newValues): string
    {
        $entityName = $this->getEntityName($model);
        $entityId = $model->getKey();

        switch ($action) {
            case 'created':
                return "{$entityName} #{$entityId} was created";
            
            case 'updated':
                $changedFields = $this->getChangedFieldNames($oldValues, $newValues);
                $fieldList = implode(', ', array_slice($changedFields, 0, 5));
                $more = count($changedFields) > 5 ? ' and ' . (count($changedFields) - 5) . ' more' : '';
                return "{$entityName} #{$entityId} was updated: {$fieldList}{$more}";
            
            case 'deleted':
                return "{$entityName} #{$entityId} was deleted";
            
            case 'restored':
                return "{$entityName} #{$entityId} was restored";
            
            case 'force_deleted':
                return "{$entityName} #{$entityId} was permanently deleted";
            
            default:
                return "{$entityName} #{$entityId} - {$action}";
        }
    }

    protected function getEntityName(Model $model): string
    {
        if (isset($model->name)) {
            return $model->name;
        }

        if (isset($model->code)) {
            return $model->code;
        }

        if (isset($model->title)) {
            return $model->title;
        }

        return $this->getEntityType($model);
    }

    protected function getChangedFieldNames(?array $oldValues, ?array $newValues): array
    {
        if (!$oldValues || !$newValues) {
            return [];
        }

        $changed = [];
        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}
