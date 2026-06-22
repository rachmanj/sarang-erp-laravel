<?php

namespace App\Services\Documents\Handlers;

use App\Services\Documents\Contracts\DocumentDeletionHandler;
use App\Services\Documents\Support\DocumentDeletionSupport;

abstract class AbstractDocumentDeletionHandler implements DocumentDeletionHandler
{
    public function __construct(
        protected DocumentDeletionSupport $support,
    ) {}

    /** @return list<array{type: string, id: int}> */
    protected function uniqueChildren(array $children): array
    {
        $unique = [];
        foreach ($children as $child) {
            $key = $child['type'].':'.$child['id'];
            $unique[$key] = $child;
        }

        return array_values($unique);
    }
}
