<?php

namespace App\Services\Documents;

use App\Services\Documents\Contracts\DocumentDeletionHandler;
use App\Services\Documents\Exceptions\DocumentDeletionBlockedException;
use App\Services\Documents\Support\DocumentDeletionSupport;

class DocumentDeletionGraph
{
    public function __construct(
        private DocumentDeletionSupport $support,
        /** @var array<string, DocumentDeletionHandler> */
        private array $handlers,
    ) {}

    /**
     * @return list<array{type: string, id: int, depth: int}>
     */
    public function collectCascade(string $rootType, int $rootId): array
    {
        $visited = [];
        $nodes = [];

        $this->walk($rootType, $rootId, 0, $visited, $nodes);

        usort($nodes, function (array $a, array $b) {
            if ($a['depth'] === $b['depth']) {
                return $a['id'] <=> $b['id'];
            }

            return $b['depth'] <=> $a['depth'];
        });

        $this->support->assertNoSharedSettlementDocuments($nodes);

        return $nodes;
    }

    /**
     * @return list<array{type: string, id: int, depth: int}>
     */
    public function descendants(string $rootType, int $rootId): array
    {
        $visited = [];
        $nodes = [];

        $this->walk($rootType, $rootId, 0, $visited, $nodes);

        return array_values(array_filter(
            $nodes,
            fn (array $node) => ! ($node['type'] === $rootType && $node['id'] === $rootId)
        ));
    }

    /**
     * @return list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>
     */
    public function preview(string $rootType, int $rootId): array
    {
        return $this->enrichNodesForPreview($this->collectCascade($rootType, $rootId));
    }

    /**
     * @return list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>
     */
    public function previewDescendants(string $rootType, int $rootId): array
    {
        return $this->enrichNodesForPreview($this->descendants($rootType, $rootId));
    }

    /**
     * @param  list<array{type: string, id: int, depth: int}>  $nodes
     * @return list<array{type: string, id: int, number: string, label: string, status: string|null, date: string|null, depth: int}>
     */
    private function enrichNodesForPreview(array $nodes): array
    {
        $preview = [];

        foreach ($nodes as $node) {
            $modelClass = DocumentDescriptor::modelClass($node['type']);
            $model = $modelClass::query()->find($node['id']);

            if (! $model) {
                continue;
            }

            $dateColumn = DocumentDescriptor::dateColumn($node['type']);
            $dateValue = $model->{$dateColumn} ?? null;
            $date = $dateValue ? (string) $dateValue : null;

            $preview[] = [
                'type' => $node['type'],
                'id' => $node['id'],
                'number' => $this->support->documentNumber($node['type'], $model),
                'label' => DocumentDescriptor::label($node['type']),
                'status' => $model->status ?? null,
                'date' => $date,
                'depth' => $node['depth'],
            ];
        }

        return $preview;
    }

    private function walk(string $type, int $id, int $depth, array &$visited, array &$nodes): void
    {
        $key = $type.':'.$id;
        if (isset($visited[$key])) {
            return;
        }

        $visited[$key] = true;
        $handler = $this->handlers[$type] ?? null;

        if (! $handler) {
            throw new DocumentDeletionBlockedException("No deletion handler registered for {$type}.");
        }

        $modelClass = DocumentDescriptor::modelClass($type);
        $model = $modelClass::query()->find($id);

        if (! $model) {
            throw new DocumentDeletionBlockedException("Document {$type} #{$id} was not found.");
        }

        $children = $handler->children($model);
        $maxChildDepth = $depth;

        foreach ($children as $child) {
            $this->walk($child['type'], $child['id'], $depth + 1, $visited, $nodes);
            $maxChildDepth = max($maxChildDepth, $depth + 1);
        }

        $nodes[] = [
            'type' => $type,
            'id' => $id,
            'depth' => $maxChildDepth,
        ];
    }
}
