<?php

namespace App\Traits;

use App\Observers\AuditLogObserver;

trait Auditable
{
    public static function bootAuditable()
    {
        static::observe(AuditLogObserver::class);
    }

    public function disableAuditLog()
    {
        $this->auditLogEnabled = false;
        return $this;
    }

    public function enableAuditLog()
    {
        $this->auditLogEnabled = true;
        return $this;
    }

    public function auditTrail()
    {
        $entityType = $this->getEntityType();
        return app(\App\Services\AuditLogService::class)
            ->getAuditTrail($entityType, $this->id);
    }

    protected function getEntityType(): string
    {
        if (isset($this->auditEntityType)) {
            return $this->auditEntityType;
        }

        $className = class_basename($this);
        return \Illuminate\Support\Str::snake($className);
    }
}

