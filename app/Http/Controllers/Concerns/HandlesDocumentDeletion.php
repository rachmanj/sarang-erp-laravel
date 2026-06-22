<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Documents\DocumentDeletionService;
use App\Services\Documents\DocumentDescriptor;
use App\Services\Documents\Exceptions\DocumentDeletionBlockedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait HandlesDocumentDeletion
{
    abstract protected function documentDeletionType(): string;

    public function deletePreview(int $id): JsonResponse
    {
        $type = $this->documentDeletionType();
        $service = app(DocumentDeletionService::class);
        $mode = request()->query('mode', 'cascade');

        if ($mode === 'single') {
            return response()->json(array_merge(
                [
                    'root' => [
                        'type' => $type,
                        'id' => $id,
                    ],
                ],
                $service->previewSingle($type, $id)
            ));
        }

        return response()->json([
            'mode' => 'cascade',
            'root' => [
                'type' => $type,
                'id' => $id,
            ],
            'documents' => $service->previewCascade($type, $id),
        ]);
    }

    public function destroyDocument(int $id): RedirectResponse
    {
        $type = $this->documentDeletionType();
        $service = app(DocumentDeletionService::class);
        $mode = request()->input('mode', 'cascade');

        try {
            if ($mode === 'single') {
                $service->deleteSingle($type, $id);

                return redirect()
                    ->route($service->redirectRoute($type))
                    ->with('success', DocumentDescriptor::label($type).' deleted successfully (related documents kept).');
            }

            $service->delete($type, $id);

            return redirect()
                ->route($service->redirectRoute($type))
                ->with('success', DocumentDescriptor::label($type).' and related documents deleted successfully.');
        } catch (DocumentDeletionBlockedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Delete failed: '.$e->getMessage());
        }
    }
}
