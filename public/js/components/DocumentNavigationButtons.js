/**
 * Document Navigation Buttons Component
 * Handles Base Document and Target Document navigation with smart states
 */
class DocumentNavigationButtons {
    constructor(containerId, documentType, documentId) {
        this.container = document.getElementById(containerId);
        this.documentType = documentType;
        this.documentId = documentId;
        this.baseDocuments = [];
        this.targetDocuments = [];
        this.isLoading = false;
        
        this.init();
    }

    init() {
        this.render();
        this.loadNavigationData();
    }

    render() {
        this.container.innerHTML = `
            <div class="document-navigation-buttons">
                <div class="btn-group" role="group">
                    <!-- Base Document Button -->
                    <button type="button" 
                            class="btn btn-outline-secondary base-document-btn" 
                            id="baseDocumentBtn"
                            disabled>
                        <i class="fas fa-arrow-left"></i> Base Document
                    </button>
                    
                    <!-- Target Document Button -->
                    <button type="button" 
                            class="btn btn-outline-secondary target-document-btn" 
                            id="targetDocumentBtn"
                            disabled>
                        Target Document <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                
                <!-- Base Document Dropdown -->
                <div class="dropdown-menu base-document-dropdown" id="baseDocumentDropdown">
                    <!-- Populated dynamically -->
                </div>
                
                <!-- Target Document Dropdown -->
                <div class="dropdown-menu target-document-dropdown" id="targetDocumentDropdown">
                    <!-- Populated dynamically -->
                </div>
            </div>
        `;

        this.bindEvents();
    }

    bindEvents() {
        const baseBtn = document.getElementById('baseDocumentBtn');
        const targetBtn = document.getElementById('targetDocumentBtn');

        baseBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleBaseDocumentClick();
        });

        targetBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleTargetDocumentClick();
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.closeDropdowns();
            }
        });
    }

    async loadNavigationData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();

        try {
            const response = await fetch(`/api/documents/${this.documentType}/${this.documentId}/navigation`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.baseDocuments = data.data.base_documents.documents;
                this.targetDocuments = data.data.target_documents.documents;
                this.updateButtonStates(data.data);
            } else {
                throw new Error(data.message || 'Failed to load navigation data');
            }

        } catch (error) {
            console.error('Error loading navigation data:', error);
            this.showErrorState(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    updateButtonStates(data) {
        const baseBtn = document.getElementById('baseDocumentBtn');
        const targetBtn = document.getElementById('targetDocumentBtn');

        // Update Base Document Button
        this.updateButtonState(baseBtn, data.base_documents, 'base');

        // Update Target Document Button
        this.updateButtonState(targetBtn, data.target_documents, 'target');
    }

    updateButtonState(button, data, type) {
        const state = data.state;
        const count = data.count;

        // Remove existing classes
        button.classList.remove('btn-primary', 'btn-outline-secondary', 'btn-outline-primary');
        
        // Reset button content
        const icon = type === 'base' ? '<i class="fas fa-arrow-left"></i>' : '<i class="fas fa-arrow-right"></i>';
        const text = type === 'base' ? 'Base Document' : 'Target Document';

        switch (state) {
            case 'disabled':
                button.disabled = true;
                button.classList.add('btn-outline-secondary');
                button.innerHTML = `${icon} ${text}`;
                break;

            case 'single':
                button.disabled = false;
                button.classList.add('btn-outline-primary');
                button.innerHTML = `${icon} ${text}`;
                break;

            case 'multiple':
                button.disabled = false;
                button.classList.add('btn-outline-primary');
                button.innerHTML = `${icon} ${text} (${count})`;
                break;
        }
    }

    handleBaseDocumentClick() {
        if (this.baseDocuments.length === 0) return;

        if (this.baseDocuments.length === 1) {
            // Single document - navigate directly
            window.location.href = this.baseDocuments[0].url;
        } else {
            // Multiple documents - show dropdown
            this.showBaseDocumentDropdown();
        }
    }

    handleTargetDocumentClick() {
        if (this.targetDocuments.length === 0) return;

        if (this.targetDocuments.length === 1) {
            // Single document - navigate directly
            window.location.href = this.targetDocuments[0].url;
        } else {
            // Multiple documents - show dropdown
            this.showTargetDocumentDropdown();
        }
    }

    showBaseDocumentDropdown() {
        this.closeDropdowns();
        
        const dropdown = document.getElementById('baseDocumentDropdown');
        const button = document.getElementById('baseDocumentBtn');
        
        // Create dropdown content
        let dropdownContent = '<div class="dropdown-header">Base Documents</div>';
        
        this.baseDocuments.forEach(doc => {
            dropdownContent += `
                <a href="${doc.url}" class="dropdown-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${doc.number}</strong>
                            <small class="text-muted d-block">${this.formatDocumentType(doc.type)}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-${this.getStatusBadgeClass(doc.status)}">${doc.status}</span>
                            <div class="text-muted small">${this.formatAmount(doc.amount)}</div>
                        </div>
                    </div>
                </a>
            `;
        });

        dropdown.innerHTML = dropdownContent;
        
        // Position and show dropdown
        const buttonRect = button.getBoundingClientRect();
        dropdown.style.position = 'absolute';
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        dropdown.style.left = `${buttonRect.left}px`;
        dropdown.style.minWidth = '300px';
        dropdown.style.display = 'block';
        dropdown.style.zIndex = '1000';
    }

    showTargetDocumentDropdown() {
        this.closeDropdowns();
        
        const dropdown = document.getElementById('targetDocumentDropdown');
        const button = document.getElementById('targetDocumentBtn');
        
        // Create dropdown content
        let dropdownContent = '<div class="dropdown-header">Target Documents</div>';
        
        this.targetDocuments.forEach(doc => {
            dropdownContent += `
                <a href="${doc.url}" class="dropdown-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${doc.number}</strong>
                            <small class="text-muted d-block">${this.formatDocumentType(doc.type)}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-${this.getStatusBadgeClass(doc.status)}">${doc.status}</span>
                            <div class="text-muted small">${this.formatAmount(doc.amount)}</div>
                        </div>
                    </div>
                </a>
            `;
        });

        dropdown.innerHTML = dropdownContent;
        
        // Position and show dropdown
        const buttonRect = button.getBoundingClientRect();
        dropdown.style.position = 'absolute';
        dropdown.style.top = `${buttonRect.bottom + 5}px`;
        dropdown.style.right = `${window.innerWidth - buttonRect.right}px`;
        dropdown.style.minWidth = '300px';
        dropdown.style.display = 'block';
        dropdown.style.zIndex = '1000';
    }

    closeDropdowns() {
        document.getElementById('baseDocumentDropdown').style.display = 'none';
        document.getElementById('targetDocumentDropdown').style.display = 'none';
    }

    showLoadingState() {
        const baseBtn = document.getElementById('baseDocumentBtn');
        const targetBtn = document.getElementById('targetDocumentBtn');

        baseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        targetBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        baseBtn.disabled = true;
        targetBtn.disabled = true;
    }

    showErrorState(message) {
        const baseBtn = document.getElementById('baseDocumentBtn');
        const targetBtn = document.getElementById('targetDocumentBtn');

        baseBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
        targetBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
        
        baseBtn.disabled = true;
        targetBtn.disabled = true;
        
        // Show error tooltip or notification
        console.error('Document Navigation Error:', message);
    }

    formatDocumentType(type) {
        const typeMap = {
            'App\\Models\\PurchaseOrder': 'Purchase Order',
            'App\\Models\\GoodsReceiptPO': 'Goods Receipt PO',
            'App\\Models\\PurchaseInvoice': 'Purchase Invoice',
            'App\\Models\\PurchasePayment': 'Purchase Payment',
            'App\\Models\\SalesOrder': 'Sales Order',
            'App\\Models\\DeliveryOrder': 'Delivery Order',
            'App\\Models\\SalesInvoice': 'Sales Invoice',
            'App\\Models\\SalesReceipt': 'Sales Receipt',
        };
        
        return typeMap[type] || type;
    }

    getStatusBadgeClass(status) {
        const statusMap = {
            'draft': 'secondary',
            'pending': 'warning',
            'approved': 'info',
            'received': 'success',
            'posted': 'success',
            'paid': 'success',
            'cancelled': 'danger',
            'closed': 'dark',
        };
        
        return statusMap[status.toLowerCase()] || 'secondary';
    }

    formatAmount(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    // Public method to refresh navigation data
    refresh() {
        this.loadNavigationData();
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DocumentNavigationButtons;
}
