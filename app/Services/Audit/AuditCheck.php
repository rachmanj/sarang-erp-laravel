<?php

namespace App\Services\Audit;

abstract class AuditCheck
{
    public string $key;

    public string $name;

    abstract public function run(): AuditCheckResult;
}
