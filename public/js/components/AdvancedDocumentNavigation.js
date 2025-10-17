/**
 * Advanced Document Navigation Component
 * Enhanced version with tooltips, keyboard shortcuts, and advanced features
 */

class AdvancedDocumentNavigation {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            enableTooltips: true,
            enableKeyboardShortcuts: true,
            enableBulkOperations: false,
            enableAnalytics: true,
            cacheTimeout: 300000, // 5 minutes
            ...options,
        };

        this.cache = new Map();
        this.analytics = new Map();
        this.keyboardShortcuts = new Map();

        this.init();
    }

    init() {
        if (!this.container) {
            console.error("Document navigation container not found");
            return;
        }

        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.setupTooltips();
        this.loadNavigationData();

        if (this.options.enableAnalytics) {
            this.trackUsage();
        }
    }

    setupEventListeners() {
        // Base Document button
        const baseBtn = this.container.querySelector(
            '[data-action="base-document"]'
        );
        if (baseBtn) {
            baseBtn.addEventListener("click", (e) =>
                this.handleBaseDocumentClick(e)
            );
        }

        // Target Document button
        const targetBtn = this.container.querySelector(
            '[data-action="target-document"]'
        );
        if (targetBtn) {
            targetBtn.addEventListener("click", (e) =>
                this.handleTargetDocumentClick(e)
            );
        }

        // Preview Journal button
        const previewBtn = this.container.querySelector(
            '[data-action="preview-journal"]'
        );
        if (previewBtn) {
            previewBtn.addEventListener("click", (e) =>
                this.handlePreviewJournalClick(e)
            );
        }

        // Bulk operations
        if (this.options.enableBulkOperations) {
            this.setupBulkOperations();
        }
    }

    setupKeyboardShortcuts() {
        if (!this.options.enableKeyboardShortcuts) return;

        document.addEventListener("keydown", (e) => {
            // Only activate shortcuts when not in input fields
            if (
                e.target.tagName === "INPUT" ||
                e.target.tagName === "TEXTAREA"
            ) {
                return;
            }

            const shortcuts = {
                b: () => this.handleBaseDocumentClick(),
                t: () => this.handleTargetDocumentClick(),
                p: () => this.handlePreviewJournalClick(),
                Escape: () => this.closeModals(),
            };

            if (shortcuts[e.key]) {
                e.preventDefault();
                shortcuts[e.key]();
            }
        });
    }

    setupTooltips() {
        if (!this.options.enableTooltips) return;

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = this.container.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        );
        tooltipTriggerList.forEach((tooltipTriggerEl) => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add custom tooltips for disabled buttons
        this.addDisabledButtonTooltips();
    }

    addDisabledButtonTooltips() {
        const disabledButtons =
            this.container.querySelectorAll("button[disabled]");
        disabledButtons.forEach((btn) => {
            if (!btn.hasAttribute("data-bs-toggle")) {
                btn.setAttribute("data-bs-toggle", "tooltip");
                btn.setAttribute("data-bs-placement", "top");

                if (btn.textContent.includes("Base Document")) {
                    btn.setAttribute(
                        "title",
                        "No base documents found for this document"
                    );
                } else if (btn.textContent.includes("Target Document")) {
                    btn.setAttribute(
                        "title",
                        "No target documents found for this document"
                    );
                }
            }
        });
    }

    async loadNavigationData() {
        const documentType = this.container.dataset.documentType;
        const documentId = this.container.dataset.documentId;

        if (!documentType || !documentId) {
            console.error("Document type or ID not found");
            return;
        }

        try {
            // Check cache first
            const cacheKey = `${documentType}_${documentId}`;
            const cachedData = this.getCachedData(cacheKey);

            if (cachedData) {
                this.updateUI(cachedData);
                return;
            }

            // Show loading state
            this.showLoadingState();

            // Fetch from API
            const response = await fetch(
                `/api/documents/${documentType}/${documentId}/navigation`
            );
            const data = await response.json();

            if (data.success) {
                this.updateUI(data.data);
                this.setCachedData(cacheKey, data.data);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error("Error loading navigation data:", error);
            this.showError("Failed to load navigation data");
        }
    }

    async handleBaseDocumentClick(e) {
        if (e) e.preventDefault();

        const documentType = this.container.dataset.documentType;
        const documentId = this.container.dataset.documentId;

        try {
            const response = await fetch(
                `/api/documents/${documentType}/${documentId}/base`
            );
            const data = await response.json();

            if (data.success) {
                this.showDocumentListModal(
                    "Base Documents",
                    data.data.documents
                );
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error("Error loading base documents:", error);
            this.showError("Failed to load base documents");
        }
    }

    async handleTargetDocumentClick(e) {
        if (e) e.preventDefault();

        const documentType = this.container.dataset.documentType;
        const documentId = this.container.dataset.documentId;

        try {
            const response = await fetch(
                `/api/documents/${documentType}/${documentId}/targets`
            );
            const data = await response.json();

            if (data.success) {
                this.showDocumentListModal(
                    "Target Documents",
                    data.data.documents
                );
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error("Error loading target documents:", error);
            this.showError("Failed to load target documents");
        }
    }

    async handlePreviewJournalClick(e) {
        if (e) e.preventDefault();

        const documentType = this.container.dataset.documentType;
        const documentId = this.container.dataset.documentId;

        try {
            const response = await fetch(
                `/api/documents/${documentType}/${documentId}/journal-preview`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        action_type: "post",
                    }),
                }
            );

            const data = await response.json();

            if (data.success) {
                this.showJournalPreviewModal(data.data);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error("Error loading journal preview:", error);
            this.showError("Failed to load journal preview");
        }
    }

    showDocumentListModal(title, documents) {
        const modalHtml = this.generateDocumentListModalHtml(title, documents);
        this.showModal(modalHtml);
    }

    showJournalPreviewModal(journalData) {
        const modalHtml = this.generateJournalPreviewModalHtml(journalData);
        this.showModal(modalHtml);
    }

    generateDocumentListModalHtml(title, documents) {
        let documentsHtml = "";

        if (documents.length === 0) {
            documentsHtml = '<p class="text-muted">No documents found.</p>';
        } else {
            documentsHtml = '<div class="list-group">';
            documents.forEach((doc) => {
                documentsHtml += `
                    <a href="${
                        doc.url
                    }" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${doc.number}</h6>
                            <small class="text-muted">${doc.status}</small>
                        </div>
                        <p class="mb-1">${doc.type}</p>
                        <small>Amount: ${this.formatCurrency(
                            doc.amount
                        )} | Date: ${this.formatDate(doc.date)}</small>
                    </a>
                `;
            });
            documentsHtml += "</div>";
        }

        return `
            <div class="modal fade" id="documentListModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${documentsHtml}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    generateJournalPreviewModalHtml(journalData) {
        const linesHtml = journalData.lines
            .map(
                (line) => `
            <tr>
                <td>${line.account_code} - ${line.account_name}</td>
                <td>${line.description}</td>
                <td class="text-end">${
                    line.debit ? this.formatCurrency(line.debit) : "-"
                }</td>
                <td class="text-end">${
                    line.credit ? this.formatCurrency(line.credit) : "-"
                }</td>
            </tr>
        `
            )
            .join("");

        return `
            <div class="modal fade" id="journalPreviewModal" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-eye"></i> Preview Journal Entries
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Journal Number:</strong> ${
                                        journalData.journal_number ||
                                        "Auto-generated"
                                    }
                                </div>
                                <div class="col-md-6">
                                    <strong>Date:</strong> ${this.formatDate(
                                        journalData.date
                                    )}
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong>Description:</strong> ${
                                    journalData.description
                                }
                            </div>
                            <h6>Journal Lines:</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${linesHtml}
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-info">
                                            <th colspan="2">Total</th>
                                            <th class="text-end">${this.formatCurrency(
                                                journalData.total_debit
                                            )}</th>
                                            <th class="text-end">${this.formatCurrency(
                                                journalData.total_credit
                                            )}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Journal is balanced
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="executeJournalAction()">
                                <i class="fas fa-play"></i> Execute Action
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    showModal(modalHtml) {
        // Remove existing modals
        const existingModals = document.querySelectorAll(".modal");
        existingModals.forEach((modal) => modal.remove());

        // Add new modal to body
        document.body.insertAdjacentHTML("beforeend", modalHtml);

        // Show modal
        const modal = new bootstrap.Modal(document.querySelector(".modal"));
        modal.show();
    }

    closeModals() {
        const modals = document.querySelectorAll(".modal");
        modals.forEach((modal) => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }

    updateUI(data) {
        // Update base document button
        const baseBtn = this.container.querySelector(
            '[data-action="base-document"]'
        );
        if (baseBtn) {
            if (data.base_documents.length > 0) {
                baseBtn.disabled = false;
                baseBtn.innerHTML = `<i class="fas fa-arrow-left"></i> Base Document${
                    data.base_documents.length > 1 ? "s" : ""
                }`;
            } else {
                baseBtn.disabled = true;
                baseBtn.innerHTML =
                    '<i class="fas fa-arrow-left"></i> Base Document';
            }
        }

        // Update target document button
        const targetBtn = this.container.querySelector(
            '[data-action="target-document"]'
        );
        if (targetBtn) {
            if (data.target_documents.length > 0) {
                targetBtn.disabled = false;
                targetBtn.innerHTML = `<i class="fas fa-arrow-right"></i> Target Document${
                    data.target_documents.length > 1 ? "s" : ""
                }`;
            } else {
                targetBtn.disabled = true;
                targetBtn.innerHTML =
                    '<i class="fas fa-arrow-right"></i> Target Document';
            }
        }

        // Update tooltips
        this.addDisabledButtonTooltips();
    }

    showLoadingState() {
        const buttons = this.container.querySelectorAll("button");
        buttons.forEach((btn) => {
            if (!btn.disabled) {
                btn.disabled = true;
                btn.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Loading...';
            }
        });
    }

    showError(message) {
        // Show toast notification
        if (typeof toastr !== "undefined") {
            toastr.error(message);
        } else {
            alert("Error: " + message);
        }
    }

    getCachedData(key) {
        const cached = this.cache.get(key);
        if (
            cached &&
            Date.now() - cached.timestamp < this.options.cacheTimeout
        ) {
            return cached.data;
        }
        return null;
    }

    setCachedData(key, data) {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(amount);
    }

    formatDate(date) {
        return new Date(date).toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    trackUsage() {
        const documentType = this.container.dataset.documentType;
        const documentId = this.container.dataset.documentId;

        const key = `${documentType}_${documentId}`;
        const count = this.analytics.get(key) || 0;
        this.analytics.set(key, count + 1);

        // Send analytics data to server (optional)
        this.sendAnalyticsData(documentType, documentId);
    }

    async sendAnalyticsData(documentType, documentId) {
        try {
            await fetch("/api/analytics/document-navigation", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    document_type: documentType,
                    document_id: documentId,
                    action: "navigation_view",
                    timestamp: new Date().toISOString(),
                }),
            });
        } catch (error) {
            console.warn("Failed to send analytics data:", error);
        }
    }

    setupBulkOperations() {
        // Implementation for bulk operations
        console.log("Bulk operations setup");
    }

    // Public API methods
    refresh() {
        this.cache.clear();
        this.loadNavigationData();
    }

    getAnalytics() {
        return Object.fromEntries(this.analytics);
    }

    clearCache() {
        this.cache.clear();
    }
}

// Global function for executing journal actions
window.executeJournalAction = function () {
    // Implementation for executing journal actions
    console.log("Executing journal action...");
};

// Auto-initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    const containers = document.querySelectorAll(
        '[id^="document-navigation-buttons"]'
    );
    containers.forEach((container) => {
        new AdvancedDocumentNavigation(container.id, {
            enableTooltips: true,
            enableKeyboardShortcuts: true,
            enableAnalytics: true,
        });
    });
});
