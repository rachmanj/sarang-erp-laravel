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
     * Log model changes automatically
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
        } elseif ($action === 'created') {
            $newValues = $model->getAttributes();
        }

        return $this->log($action, $entityType, $entityId, $oldValues, $newValues, $description);
    }

    /**
     * Get entity type from model
     */
    private function getEntityType($model)
    {
        $className = class_basename($model);
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
    }
}
