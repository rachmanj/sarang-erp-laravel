{{-- Document Navigation Component --}}
@php($showPreviewJournal = $showPreviewJournal ?? true)
<div class="document-navigation-container">
    {{-- Document Navigation Buttons --}}
    <div id="documentNavigationButtons"></div>

    {{-- Preview Journal Button --}}
    @if ($showPreviewJournal)
        <div id="previewJournalButton" class="mt-2"></div>
    @endif
</div>

{{-- Include CSS --}}
<link rel="stylesheet" href="{{ asset('css/document-navigation.css') }}">

{{-- Include JavaScript Components --}}
<script src="{{ asset('js/components/DocumentNavigationButtons.js') }}"></script>
<script src="{{ asset('js/components/PreviewJournalButton.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Document Navigation Buttons
    const documentType = '{{ $documentType ?? "goods-receipt-po" }}';
    const documentId = {{ $documentId ?? 1 }};
    
    // Initialize navigation buttons
    const navigationButtons = new DocumentNavigationButtons(
        'documentNavigationButtons',
        documentType,
        documentId
    );

    const showPreviewJournal = @json($showPreviewJournal);
    let previewJournalButton = null;
    if (showPreviewJournal) {
        previewJournalButton = new PreviewJournalButton(
            'previewJournalButton',
            documentType,
            documentId,
            'post' // Action type: post, approve, etc.
        );
    }

    // Optional: Refresh navigation data when document status changes
    window.refreshDocumentNavigation = function() {
        navigationButtons.refresh();
        if (previewJournalButton) {
            previewJournalButton.refresh();
        }
    };
});
</script>
