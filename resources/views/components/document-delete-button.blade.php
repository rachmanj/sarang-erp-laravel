@props([
    'permission',
    'previewRoute',
    'destroyRoute',
    'documentLabel' => 'document',
])

@can($permission)
    <div class="btn-group">
        <button type="button"
            class="btn btn-sm btn-danger dropdown-toggle document-delete-trigger"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            data-preview-url="{{ $previewRoute }}"
            data-destroy-url="{{ $destroyRoute }}"
            data-document-label="{{ $documentLabel }}">
            <i class="fas fa-trash mr-1"></i> Delete
        </button>
        <div class="dropdown-menu dropdown-menu-right">
            <button type="button"
                class="dropdown-item document-delete-trigger"
                data-mode="single"
                data-preview-url="{{ $previewRoute }}"
                data-destroy-url="{{ $destroyRoute }}"
                data-document-label="{{ $documentLabel }}">
                Delete this document only
            </button>
            <button type="button"
                class="dropdown-item document-delete-trigger"
                data-mode="cascade"
                data-preview-url="{{ $previewRoute }}"
                data-destroy-url="{{ $destroyRoute }}"
                data-document-label="{{ $documentLabel }}">
                Delete with related documents
            </button>
        </div>
    </div>

    <div class="modal fade" id="documentDeleteModal" tabindex="-1" role="dialog" aria-labelledby="documentDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="documentDeleteModalLabel">Confirm delete</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="documentDeleteIntro">
                        The following documents will be deleted. Posted documents will have journals reversed first.
                    </p>
                    <div id="documentDeleteLoading" class="text-muted py-3">Loading delete preview...</div>
                    <div id="documentDeleteError" class="alert alert-danger d-none"></div>
                    <div id="documentDeleteTargetsWrap" class="d-none mb-3">
                        <p class="mb-2 font-weight-bold">Downstream documents that must be removed first:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Number</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="documentDeleteTargetsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="table-responsive d-none" id="documentDeleteTableWrap">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="documentDeleteTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="documentDeleteForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="mode" id="documentDeleteMode" value="cascade">
                        <button type="submit" id="documentDeleteConfirmBtn" class="btn btn-danger" disabled>
                            Confirm delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const modal = $('#documentDeleteModal');
                    const loadingEl = document.getElementById('documentDeleteLoading');
                    const errorEl = document.getElementById('documentDeleteError');
                    const introEl = document.getElementById('documentDeleteIntro');
                    const targetsWrap = document.getElementById('documentDeleteTargetsWrap');
                    const targetsBody = document.getElementById('documentDeleteTargetsBody');
                    const tableWrap = document.getElementById('documentDeleteTableWrap');
                    const tableBody = document.getElementById('documentDeleteTableBody');
                    const form = document.getElementById('documentDeleteForm');
                    const modeInput = document.getElementById('documentDeleteMode');
                    const confirmBtn = document.getElementById('documentDeleteConfirmBtn');
                    const modalTitle = document.getElementById('documentDeleteModalLabel');

                    function appendDocumentRow(tbody, doc) {
                        const row = document.createElement('tr');
                        row.innerHTML =
                            '<td>' + (doc.label || doc.type) + '</td>' +
                            '<td>' + (doc.number || '-') + '</td>' +
                            '<td>' + (doc.date || '-') + '</td>' +
                            '<td>' + (doc.status || '-') + '</td>';
                        tbody.appendChild(row);
                    }

                    document.querySelectorAll('.document-delete-trigger').forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            if (button.classList.contains('dropdown-toggle')) {
                                return;
                            }

                            event.preventDefault();

                            const previewUrl = button.dataset.previewUrl;
                            const destroyUrl = button.dataset.destroyUrl;
                            const label = button.dataset.documentLabel || 'document';
                            const mode = button.dataset.mode || 'cascade';

                            modalTitle.textContent = mode === 'single'
                                ? 'Delete ' + label + ' only'
                                : 'Delete ' + label + ' and related documents';

                            introEl.textContent = mode === 'single'
                                ? 'Only this document will be deleted. Posted documents will have journals reversed first. Base and downstream documents are kept.'
                                : 'The following documents will be deleted. Posted documents will have journals reversed first.';

                            confirmBtn.textContent = mode === 'single'
                                ? 'Delete this document only'
                                : 'Delete all listed documents';

                            form.action = destroyUrl;
                            modeInput.value = mode;
                            loadingEl.classList.remove('d-none');
                            errorEl.classList.add('d-none');
                            errorEl.textContent = '';
                            targetsWrap.classList.add('d-none');
                            targetsBody.innerHTML = '';
                            tableWrap.classList.add('d-none');
                            tableBody.innerHTML = '';
                            confirmBtn.disabled = true;
                            modal.modal('show');

                            const url = previewUrl + (previewUrl.indexOf('?') >= 0 ? '&' : '?') + 'mode=' + encodeURIComponent(mode);

                            fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(function (response) {
                                    if (!response.ok) {
                                        throw new Error('Unable to load delete preview.');
                                    }
                                    return response.json();
                                })
                                .then(function (data) {
                                    loadingEl.classList.add('d-none');
                                    tableWrap.classList.remove('d-none');

                                    if (mode === 'single' && data.blocked) {
                                        errorEl.classList.remove('d-none');
                                        errorEl.textContent = 'This document has downstream documents. Delete them first or use "Delete with related documents".';
                                        targetsWrap.classList.remove('d-none');
                                        (data.targets || []).forEach(function (doc) {
                                            appendDocumentRow(targetsBody, doc);
                                        });
                                        confirmBtn.disabled = true;
                                    } else {
                                        confirmBtn.disabled = false;
                                    }

                                    (data.documents || []).forEach(function (doc) {
                                        appendDocumentRow(tableBody, doc);
                                    });
                                })
                                .catch(function (error) {
                                    loadingEl.classList.add('d-none');
                                    errorEl.classList.remove('d-none');
                                    errorEl.textContent = error.message;
                                });
                        });
                    });
                });
            </script>
        @endpush
    @endonce
@endcan
