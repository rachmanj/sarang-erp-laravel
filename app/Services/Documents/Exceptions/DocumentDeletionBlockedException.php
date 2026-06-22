<?php

namespace App\Services\Documents\Exceptions;

use RuntimeException;

class DocumentDeletionBlockedException extends RuntimeException
{
    /** @param list<string> $messages */
    public function __construct(
        string $message = '',
        public readonly array $blockers = [],
    ) {
        parent::__construct($message !== '' ? $message : implode(' ', $blockers));
    }
}
