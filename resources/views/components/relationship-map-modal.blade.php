{{-- Relationship Map Modal Component --}}
<div class="modal fade" id="relationshipMapModal" tabindex="-1" role="dialog" aria-labelledby="relationshipMapModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="relationshipMapModalLabel">
                    <i class="fas fa-sitemap mr-2"></i>
                    Document Relationship Map
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Loading State --}}
                <div id="relationshipMapLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading relationship map...</p>
                </div>

                {{-- Error State --}}
                <div id="relationshipMapError" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="relationshipMapErrorMessage"></span>
                </div>

                {{-- Document Info --}}
                <div id="relationshipMapInfo" class="row mb-3 d-none">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-file-alt mr-1"></i>
                                    Current Document
                                </h6>
                                <p class="card-text mb-1">
                                    <strong>Number:</strong> <span id="currentDocumentNumber"></span>
                                </p>
                                <p class="card-text mb-1">
                                    <strong>Type:</strong> <span id="currentDocumentType"></span>
                                </p>
                                <p class="card-text mb-1">
                                    <strong>Status:</strong>
                                    <span id="currentDocumentStatus" class="badge badge-secondary"></span>
                                </p>
                                <p class="card-text mb-0">
                                    <strong>Amount:</strong> <span id="currentDocumentAmount"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Relationship Summary
                                </h6>
                                <p class="card-text mb-1">
                                    <strong>Base Documents:</strong> <span id="baseDocumentsCount">0</span>
                                </p>
                                <p class="card-text mb-1">
                                    <strong>Target Documents:</strong> <span id="targetDocumentsCount">0</span>
                                </p>
                                <p class="card-text mb-0">
                                    <strong>Total Related:</strong> <span id="totalDocumentsCount">0</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mermaid Diagram Container --}}
                <div id="relationshipMapContainer" class="d-none">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-project-diagram mr-1"></i>
                                Document Workflow
                            </h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="zoomInBtn">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="zoomOutBtn">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="resetZoomBtn">
                                    <i class="fas fa-expand-arrows-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="mermaidDiagram" class="mermaid-container"
                                style="min-height: 400px; overflow: auto;">
                                {{-- Mermaid diagram will be rendered here --}}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Document List --}}
                <div id="relationshipMapList" class="d-none">
                    <div class="row">
                        {{-- Base Documents --}}
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-arrow-up mr-1"></i>
                                        Base Documents (Sources)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="baseDocumentsList">
                                        {{-- Base documents will be listed here --}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Target Documents --}}
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-arrow-down mr-1"></i>
                                        Target Documents (Created)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="targetDocumentsList">
                                        {{-- Target documents will be listed here --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>
                    Close
                </button>
                <button type="button" class="btn btn-primary" id="refreshRelationshipMapBtn">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Include Mermaid.js --}}
<script src="https://cdn.jsdelivr.net/npm/mermaid@10.6.1/dist/mermaid.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Mermaid with modern configuration
        mermaid.initialize({
            startOnLoad: false,
            theme: 'base',
            themeVariables: {
                primaryColor: '#007bff',
                primaryTextColor: '#ffffff',
                primaryBorderColor: '#0056b3',
                lineColor: '#6c757d',
                secondaryColor: '#f8f9fa',
                tertiaryColor: '#e9ecef',
                background: '#ffffff',
                mainBkg: '#ffffff',
                secondBkg: '#f8f9fa',
                tertiaryBkg: '#e9ecef'
            },
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true,
                curve: 'basis',
                padding: 20
            },
            securityLevel: 'loose'
        });

        let currentDocumentType = '';
        let currentDocumentId = '';
        let currentZoomLevel = 1;

        // Show relationship map modal
        window.showRelationshipMap = function(documentType, documentId) {
            currentDocumentType = documentType;
            currentDocumentId = documentId;

            $('#relationshipMapModal').modal('show');
            loadRelationshipMap();
        };

        // Load relationship map data
        function loadRelationshipMap() {
            showLoading();

            $.ajax({
                url: `/api/documents/${currentDocumentType}/${currentDocumentId}/relationship-map`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('Relationship Map API Response:', response);
                    if (response.success) {
                        displayRelationshipMap(response);
                    } else {
                        showError(response.error || 'Failed to load relationship map');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to load relationship map';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    }
                    showError(errorMessage);
                }
            });
        }

        // Display relationship map
        async function displayRelationshipMap(data) {
            hideLoading();

            // Debug: Log debug information to console only
            if (data.debug) {
                console.log('Debug Info:', data.debug);
            }

            // Update document info
            updateDocumentInfo(data.document);

            // Update relationship summary
            updateRelationshipSummary(data.relationships);

            // Render Mermaid diagram
            await renderMermaidDiagram(data.mermaid);

            // Render document lists
            renderDocumentLists(data.relationships);

            // Show containers
            $('#relationshipMapInfo').removeClass('d-none');
            $('#relationshipMapContainer').removeClass('d-none');
            $('#relationshipMapList').removeClass('d-none');
        }

        // Update document info
        function updateDocumentInfo(document) {
            $('#currentDocumentNumber').text(document.number);
            $('#currentDocumentType').text(document.type);
            $('#currentDocumentStatus').text(document.status).removeClass().addClass('badge badge-' +
                getStatusBadgeClass(document.status));
            $('#currentDocumentAmount').text(formatCurrency(document.amount));
        }

        // Update relationship summary
        function updateRelationshipSummary(relationships) {
            $('#baseDocumentsCount').text(relationships.base_documents.count);
            $('#targetDocumentsCount').text(relationships.target_documents.count);
            $('#totalDocumentsCount').text(relationships.base_documents.count + relationships.target_documents
                .count);
        }

        // Render Mermaid diagram
        async function renderMermaidDiagram(mermaidData) {
            try {
                const diagramDefinition = generateMermaidDefinition(mermaidData);

                // Clear previous diagram
                $('#mermaidDiagram').empty();

                // Create a unique ID for this diagram
                const diagramId = 'relationshipDiagram_' + Date.now();

                // Render new diagram using modern Mermaid API
                const {
                    svg
                } = await mermaid.render(diagramId, diagramDefinition);
                $('#mermaidDiagram').html(svg);
            } catch (error) {
                console.error('Mermaid rendering error:', error);
                $('#mermaidDiagram').html(
                    '<div class="alert alert-warning">Unable to render workflow diagram. Please check the browser console for details.</div>'
                );
            }
        }

        // Generate Mermaid diagram definition
        function generateMermaidDefinition(mermaidData) {
            let definition = 'graph TD\n';

            // Add nodes with detailed information
            mermaidData.nodes.forEach(node => {
                const nodeId = node.id.replace('doc_', '');

                // Create detailed node label with document info
                let nodeLabel = `${node.label}`;
                if (node.date) {
                    nodeLabel += `\\n${node.date}`;
                }
                if (node.reference) {
                    nodeLabel += `\\n${node.reference}`;
                }
                if (node.amount) {
                    nodeLabel += `\\n${formatCurrency(node.amount)}`;
                }

                const nodeClass = node.isCurrent ? 'current' : getDocumentTypeClass(node.type);

                definition += `    ${nodeId}["${nodeLabel}"]:::${nodeClass}\n`;
            });

            // Add edges with different arrow types for different relationships
            mermaidData.edges.forEach(edge => {
                const fromId = edge.from.replace('doc_', '');
                const toId = edge.to.replace('doc_', '');

                // Use different arrow styles based on relationship type
                if (edge.type === 'direct') {
                    definition += `    ${fromId} -->|${edge.label || ''}| ${toId}\n`;
                } else if (edge.type === 'parallel') {
                    definition += `    ${fromId} -.->|${edge.label || ''}| ${toId}\n`;
                } else {
                    definition += `    ${fromId} --> ${toId}\n`;
                }
            });

            // Add comprehensive styling for different document types
            definition += `
    classDef current fill:#007bff,stroke:#0056b3,stroke-width:3px,color:#fff,font-weight:bold
    classDef purchaseOrder fill:#e3f2fd,stroke:#1976d2,stroke-width:2px,color:#000
    classDef goodsReceipt fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#000
    classDef purchaseInvoice fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#000
    classDef purchasePayment fill:#e8f5e8,stroke:#388e3c,stroke-width:2px,color:#000
    classDef salesOrder fill:#fce4ec,stroke:#c2185b,stroke-width:2px,color:#000
    classDef deliveryOrder fill:#f1f8e9,stroke:#689f38,stroke-width:2px,color:#000
    classDef salesInvoice fill:#fff8e1,stroke:#ffa000,stroke-width:2px,color:#000
    classDef salesReceipt fill:#e0f2f1,stroke:#00796b,stroke-width:2px,color:#000
    classDef default fill:#f8f9fa,stroke:#6c757d,stroke-width:2px,color:#000
        `;

            return definition;
        }

        // Get document type class for styling
        function getDocumentTypeClass(type) {
            const typeMap = {
                'Purchase Order': 'purchaseOrder',
                'Goods Receipt PO': 'goodsReceipt',
                'Purchase Invoice': 'purchaseInvoice',
                'Purchase Payment': 'purchasePayment',
                'Sales Order': 'salesOrder',
                'Delivery Order': 'deliveryOrder',
                'Sales Invoice': 'salesInvoice',
                'Sales Receipt': 'salesReceipt'
            };
            return typeMap[type] || 'default';
        }

        // Render document lists
        function renderDocumentLists(relationships) {
            renderDocumentList('baseDocumentsList', relationships.base_documents.documents, 'base');
            renderDocumentList('targetDocumentsList', relationships.target_documents.documents, 'target');
        }

        // Render individual document list
        function renderDocumentList(containerId, documents, type) {
            const container = $('#' + containerId);
            container.empty();

            if (documents.length === 0) {
                container.html('<p class="text-muted">No ' + type + ' documents found.</p>');
                return;
            }

            documents.forEach(doc => {
                const docCard = createDocumentCard(doc, type);
                container.append(docCard);
            });
        }

        // Create document card
        function createDocumentCard(doc, type) {
            const statusBadgeClass = getStatusBadgeClass(doc.status);
            const typeIcon = getDocumentTypeIcon(doc.type);

            return $(`
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <i class="${typeIcon} mr-1"></i>
                                ${doc.number}
                            </h6>
                            <p class="mb-1 text-muted small">${doc.type}</p>
                            <p class="mb-0 small">
                                <span class="badge badge-${statusBadgeClass}">${doc.status}</span>
                                <span class="ml-2">${formatCurrency(doc.amount)}</span>
                            </p>
                        </div>
                        <a href="${doc.url}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        `);
        }

        // Utility functions
        function showLoading() {
            $('#relationshipMapLoading').removeClass('d-none');
            $('#relationshipMapError').addClass('d-none');
            $('#relationshipMapInfo').addClass('d-none');
            $('#relationshipMapContainer').addClass('d-none');
            $('#relationshipMapList').addClass('d-none');
        }

        function hideLoading() {
            $('#relationshipMapLoading').addClass('d-none');
        }

        function showError(message) {
            hideLoading();
            $('#relationshipMapErrorMessage').text(message);
            $('#relationshipMapError').removeClass('d-none');
        }

        function getStatusBadgeClass(status) {
            const statusMap = {
                'draft': 'secondary',
                'approved': 'success',
                'posted': 'primary',
                'closed': 'dark',
                'cancelled': 'danger',
                'pending': 'warning'
            };
            return statusMap[status.toLowerCase()] || 'secondary';
        }

        function getDocumentTypeIcon(type) {
            const iconMap = {
                'Purchase Order': 'fas fa-shopping-cart',
                'Goods Receipt PO': 'fas fa-truck-loading',
                'Purchase Invoice': 'fas fa-file-invoice',
                'Purchase Payment': 'fas fa-credit-card',
                'Sales Order': 'fas fa-handshake',
                'Delivery Order': 'fas fa-truck',
                'Sales Invoice': 'fas fa-file-invoice-dollar',
                'Sales Receipt': 'fas fa-receipt'
            };
            return iconMap[type] || 'fas fa-file';
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Event handlers
        $('#refreshRelationshipMapBtn').on('click', function() {
            loadRelationshipMap();
        });

        // Zoom controls
        $('#zoomInBtn').on('click', function() {
            currentZoomLevel += 0.1;
            $('#mermaidDiagram').css('transform', `scale(${currentZoomLevel})`);
        });

        $('#zoomOutBtn').on('click', function() {
            currentZoomLevel = Math.max(0.5, currentZoomLevel - 0.1);
            $('#mermaidDiagram').css('transform', `scale(${currentZoomLevel})`);
        });

        $('#resetZoomBtn').on('click', function() {
            currentZoomLevel = 1;
            $('#mermaidDiagram').css('transform', 'scale(1)');
        });

        // Reset zoom when modal is closed
        $('#relationshipMapModal').on('hidden.bs.modal', function() {
            currentZoomLevel = 1;
            $('#mermaidDiagram').css('transform', 'scale(1)');
        });
    });
</script>

<style>
    .mermaid-container {
        background: #f8f9fa;
        border-radius: 0.375rem;
        padding: 1rem;
    }

    .mermaid-container svg {
        max-width: 100%;
        height: auto;
    }

    #relationshipMapModal .modal-xl {
        max-width: 95%;
    }

    @media (max-width: 768px) {
        #relationshipMapModal .modal-xl {
            max-width: 100%;
            margin: 0;
        }

        .mermaid-container {
            padding: 0.5rem;
        }
    }
</style>
