<div class="modal fade" id="koranCellModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="koran-modal-title">Rekening Koran</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p id="koran-modal-subtitle" class="text-muted mb-3"></p>

                <div id="koran-modal-empty" style="display: none;">
                    @can('bank_reconciliation.import')
                        <form method="POST" action="{{ route('bank-reconciliation.store') }}" enctype="multipart/form-data" id="koran-upload-form">
                            @csrf
                            <input type="hidden" name="bank_account_id" id="koran-form-bank-account-id">
                            <input type="hidden" name="periode" id="koran-form-periode">
                            <input type="hidden" name="source_mode" value="ai">
                            <input type="hidden" name="redirect_to" value="koran">

                            <div class="form-group">
                                <label>Upload PDF Rekening Koran</label>
                                <input type="file" name="file" class="form-control-file" accept="application/pdf" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Upload &amp; Parse</button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('bank-reconciliation.store') }}" id="koran-manual-form">
                            @csrf
                            <input type="hidden" name="bank_account_id" id="koran-manual-bank-account-id">
                            <input type="hidden" name="periode" id="koran-manual-periode">
                            <input type="hidden" name="source_mode" value="manual">
                            <input type="hidden" name="redirect_to" value="koran">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Create Manual Session</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">No session for this month. You do not have permission to create one.</p>
                    @endcan
                </div>

                <div id="koran-modal-session" style="display: none;">
                    <p class="mb-2">Status: <span id="koran-session-status" class="badge badge-secondary"></span></p>
                    <div class="d-flex flex-wrap" style="gap: 0.35rem;">
                        <a href="#" id="koran-open-workbench" class="btn btn-primary btn-sm">Open Workbench</a>
                        <a href="#" id="koran-open-pdf" class="btn btn-outline-danger btn-sm" target="_blank" rel="noopener noreferrer" style="display: none;">
                            <i class="fas fa-file-pdf"></i> Preview PDF
                        </a>
                        <a href="#" id="koran-open-report" class="btn btn-secondary btn-sm" style="display: none;">View Report</a>
                    </div>
                    @can('bank_reconciliation.reconcile')
                        <form method="POST" id="koran-reparse-form" class="d-inline mt-2" style="display: none;">
                            @csrf
                            <button class="btn btn-outline-info btn-sm">Re-parse PDF</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
