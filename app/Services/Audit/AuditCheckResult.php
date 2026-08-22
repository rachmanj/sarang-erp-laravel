<?php

namespace App\Services\Audit;

class AuditCheckResult
{
    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    public function __construct(
        public readonly string $status,
        public readonly array $issues = [],
    ) {}

    public static function pass(): self
    {
        return new self(self::STATUS_PASS);
    }

    public static function fail(array $issues): self
    {
        return new self(self::STATUS_FAIL, $issues);
    }

    public function isPass(): bool
    {
        return $this->status === self::STATUS_PASS;
    }
}
