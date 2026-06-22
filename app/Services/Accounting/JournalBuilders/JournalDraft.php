<?php

namespace App\Services\Accounting\JournalBuilders;

final class JournalDraft
{
    /**
     * @param  list<array{account_id: int, debit: float|int, credit: float|int, memo?: string|null, project_id?: int|null, dept_id?: int|null, fund_id?: int|null}>  $lines
     */
    public function __construct(
        public string $description,
        public array $lines,
        public ?string $date = null,
    ) {}
}
