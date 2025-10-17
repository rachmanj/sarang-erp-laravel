/**
 * Preview Journal Button Component
 * Shows journal entries that would be created for the current action
 */
class PreviewJournalButton {
    constructor(containerId, documentType, documentId, actionType = "post") {
        this.container = document.getElementById(containerId);
        this.documentType = documentType;
        this.documentId = documentId;
        this.actionType = actionType;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.render();
        this.bindEvents();
    }

    render() {
        this.container.innerHTML = `
            <div class="preview-journal-button">
                <button type="button" 
                        class="btn btn-outline-info preview-journal-btn" 
                        id="previewJournalBtn"
                        data-toggle="modal" 
                        data-target="#previewJournalModal">
                    <i class="fas fa-eye"></i> Preview Journal
                </button>
                
                <!-- Preview Journal Modal -->
                <div class="modal fade" id="previewJournalModal" tabindex="-1" role="dialog" aria-labelledby="previewJournalModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="previewJournalModalLabel">
                                    <i class="fas fa-eye"></i> Preview Journal Entries
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="preview-journal-content">
                                    <!-- Loading state -->
                                    <div class="text-center loading-state" id="loadingState">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading journal preview...</p>
                                    </div>
                                    
                                    <!-- Error state -->
                                    <div class="alert alert-danger error-state" id="errorState" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span class="error-message"></span>
                                    </div>
                                    
                                    <!-- Journal preview content -->
                                    <div class="journal-preview-content" id="journalPreviewContent" style="display: none;">
                                        <div class="journal-summary mb-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Journal Number:</strong> <span class="journal-number"></span>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Date:</strong> <span class="journal-date"></span>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <strong>Description:</strong> <span class="journal-description"></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="journal-lines">
                                            <h6>Journal Lines:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Account</th>
                                                            <th>Description</th>
                                                            <th class="text-right">Debit</th>
                                                            <th class="text-right">Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="journal-lines-tbody">
                                                        <!-- Populated dynamically -->
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <th colspan="2">Total</th>
                                                            <th class="text-right total-debit"></th>
                                                            <th class="text-right total-credit"></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <div class="journal-balance-check mt-3">
                                            <div class="alert alert-success" id="balancedAlert" style="display: none;">
                                                <i class="fas fa-check-circle"></i> Journal is balanced
                                            </div>
                                            <div class="alert alert-danger" id="unbalancedAlert" style="display: none;">
                                                <i class="fas fa-exclamation-triangle"></i> Journal is not balanced
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        const previewBtn = document.getElementById("previewJournalBtn");
        const modal = document.getElementById("previewJournalModal");

        previewBtn.addEventListener("click", (e) => {
            e.preventDefault();
            this.showPreview();
        });

        // Reset modal when closed
        modal.addEventListener("hidden.bs.modal", () => {
            this.resetModal();
        });
    }

    async showPreview() {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoadingState();

        try {
            // Simulate journal preview API call
            const journalData = await this.getJournalPreview();
            this.displayJournalPreview(journalData);
        } catch (error) {
            console.error("Error loading journal preview:", error);
            this.showErrorState(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    async getJournalPreview() {
        // This would be replaced with actual API call
        // For now, we'll simulate the journal preview based on document type

        const response = await fetch(
            `/api/documents/${this.documentType}/${this.documentId}/journal-preview`,
            {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    action_type: this.actionType,
                }),
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || "Failed to load journal preview");
        }

        return data.data;
    }

    displayJournalPreview(journalData) {
        this.hideLoadingState();
        this.hideErrorState();

        // Update journal summary
        document.querySelector(".journal-number").textContent =
            journalData.journal_number || "Auto-generated";
        document.querySelector(".journal-date").textContent = this.formatDate(
            journalData.date
        );
        document.querySelector(".journal-description").textContent =
            journalData.description;

        // Update journal lines
        const tbody = document.querySelector(".journal-lines-tbody");
        tbody.innerHTML = "";

        let totalDebit = 0;
        let totalCredit = 0;

        journalData.lines.forEach((line) => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${line.account_code} - ${line.account_name}</td>
                <td>${line.memo || ""}</td>
                <td class="text-right">${
                    line.debit > 0 ? this.formatAmount(line.debit) : ""
                }</td>
                <td class="text-right">${
                    line.credit > 0 ? this.formatAmount(line.credit) : ""
                }</td>
            `;
            tbody.appendChild(row);

            totalDebit += parseFloat(line.debit || 0);
            totalCredit += parseFloat(line.credit || 0);
        });

        // Update totals
        document.querySelector(".total-debit").textContent =
            this.formatAmount(totalDebit);
        document.querySelector(".total-credit").textContent =
            this.formatAmount(totalCredit);

        // Check balance
        const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01;
        this.showBalanceCheck(isBalanced);

        // Show content
        document.getElementById("journalPreviewContent").style.display =
            "block";
    }

    showBalanceCheck(isBalanced) {
        const balancedAlert = document.getElementById("balancedAlert");
        const unbalancedAlert = document.getElementById("unbalancedAlert");

        if (isBalanced) {
            balancedAlert.style.display = "block";
            unbalancedAlert.style.display = "none";
        } else {
            balancedAlert.style.display = "none";
            unbalancedAlert.style.display = "block";
        }
    }

    showLoadingState() {
        document.getElementById("loadingState").style.display = "block";
        document.getElementById("errorState").style.display = "none";
        document.getElementById("journalPreviewContent").style.display = "none";
    }

    hideLoadingState() {
        document.getElementById("loadingState").style.display = "none";
    }

    showErrorState(message) {
        this.hideLoadingState();
        document.getElementById("errorState").style.display = "block";
        document.querySelector(".error-message").textContent = message;
        document.getElementById("journalPreviewContent").style.display = "none";
    }

    hideErrorState() {
        document.getElementById("errorState").style.display = "none";
    }

    showSuccessMessage(message) {
        // Create temporary success alert
        const alert = document.createElement("div");
        alert.className =
            "alert alert-success alert-dismissible fade show position-fixed";
        alert.style.top = "20px";
        alert.style.right = "20px";
        alert.style.zIndex = "9999";
        alert.innerHTML = `
            <i class="fas fa-check-circle"></i> ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

        document.body.appendChild(alert);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 3000);
    }

    resetModal() {
        this.hideLoadingState();
        this.hideErrorState();
        document.getElementById("journalPreviewContent").style.display = "none";
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    formatAmount(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    }

    // Public method to refresh preview
    refresh() {
        this.showPreview();
    }
}

// Export for use in other modules
if (typeof module !== "undefined" && module.exports) {
    module.exports = PreviewJournalButton;
}
