<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Log an action for any entity
     */
    public function log($action, $entityType, $entityId, $oldValues = null, $newValues = null, $description = null, $userId = null)
    {
        return AuditLog::log($action, $entityType, $entityId, $oldValues, $newValues, $description, $userId);
    }

    /**
     * Log inventory item changes
     */
    public function logInventoryItem($action, $itemId, $oldValues = null, $newValues = null, $description = null)
    {
        return AuditLog::logInventoryItem($action, $itemId, $oldValues, $newValues, $description);
    }

    /**
     * Log inventory transaction changes
     */
    public function logInventoryTransaction($action, $transactionId, $oldValues = null, $newValues = null, $description = null)
    {
        return AuditLog::logInventoryTransaction($action, $transactionId, $oldValues, $newValues, $description);
    }

    /**
     * Log warehouse changes
     */
    public function logWarehouse($action, $warehouseId, $oldValues = null, $newValues = null, $description = null)
    {
        return AuditLog::logWarehouse($action, $warehouseId, $oldValues, $newValues, $description);
    }

    /**
     * Get audit trail for an entity
     */
    public function getAuditTrail($entityType, $entityId)
    {
        return AuditLog::getAuditTrail($entityType, $entityId);
    }

    /**
     * Get recent activity across the system
     */
    public function getRecentActivity($days = 7, $limit = 50)
    {
        return AuditLog::getRecentActivity($days, $limit);
    }

    /**
     * Get activity by user
     */
    public function getActivityByUser($userId, $days = 30)
    {
        return AuditLog::getActivityByUser($userId, $days);
    }

    /**
     * Get activity by action type
     */
    public function getActivityByAction($action, $days = 30)
    {
        return AuditLog::getActivityByAction($action, $days);
    }

    /**
     * Log model changes automatically (called by observers).
     */
    public function logModelChanges($model, $action, $description = null)
    {
        $entityType = $this->getEntityType($model);
        $entityId = $model->id;

        $oldValues = null;
        $newValues = null;

        if ($action === 'updated' && $model->wasChanged()) {
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();
            
            if (isset($model->auditLogIgnore)) {
                foreach ($model->auditLogIgnore as $field) {
                    unset($oldValues[$field]);
                    unset($newValues[$field]);
                }
            }
        } elseif ($action === 'created') {
            $newValues = $model->getAttributes();
            
            if (isset($model->auditLogIgnore)) {
                foreach ($model->auditLogIgnore as $field) {
                    unset($newValues[$field]);
                }
            }
        } elseif (in_array($action, ['deleted', 'force_deleted'])) {
            $oldValues = $model->getAttributes();
        }

        if (!$description) {
            $description = $this->generateDescription($model, $action, $oldValues, $newValues);
        }

        return $this->log($action, $entityType, $entityId, $oldValues, $newValues, $description);
    }

    /**
     * Generate description for model change.
     */
    protected function generateDescription($model, $action, $oldValues, $newValues)
    {
        $entityName = $this->getEntityName($model);
        $entityId = $model->id ?? 'N/A';

        switch ($action) {
            case 'created':
                return "{$entityName} #{$entityId} was created";
            case 'updated':
                $changedFields = $oldValues && $newValues 
                    ? array_keys(array_diff_assoc($newValues, $oldValues))
                    : [];
                $fieldList = implode(', ', array_slice($changedFields, 0, 5));
                $more = count($changedFields) > 5 ? ' and ' . (count($changedFields) - 5) . ' more' : '';
                return "{$entityName} #{$entityId} was updated: {$fieldList}{$more}";
            case 'deleted':
                return "{$entityName} #{$entityId} was deleted";
            default:
                return "{$entityName} #{$entityId} - {$action}";
        }
    }

    /**
     * Get entity name for display.
     */
    protected function getEntityName($model)
    {
        if (isset($model->name)) return $model->name;
        if (isset($model->code)) return $model->code;
        if (isset($model->title)) return $model->title;
        return $this->getEntityType($model);
    }

    /**
     * Get entity type from model
     */
    public function getEntityType($model)
    {
        if (isset($model->auditEntityType)) {
            return $model->auditEntityType;
        }

        $className = class_basename($model);
        return \Illuminate\Support\Str::snake($className);
    }
}
