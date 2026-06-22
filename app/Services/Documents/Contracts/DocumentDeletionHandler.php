<?php

namespace App\Services\Documents\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DocumentDeletionHandler
{
    public function type(): string;

    /** @return list<array{type: string, id: int}> */
    public function children(Model $document): array;

    public function reverseAndDelete(Model $document): void;
}
