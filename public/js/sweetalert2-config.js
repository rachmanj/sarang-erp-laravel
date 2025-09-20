/**
 * Global SweetAlert2 Configuration
 * This file provides consistent SweetAlert2 configuration across the application
 */

// Global SweetAlert2 configuration
if (typeof Swal !== "undefined") {
    // Set default configuration
    Swal.mixin({
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        showCancelButton: true,
        showCloseButton: true,
        focusConfirm: false,
        focusCancel: false,
        allowOutsideClick: false,
        allowEscapeKey: true,
        customClass: {
            confirmButton: "btn btn-primary",
            cancelButton: "btn btn-secondary",
        },
    });

    // Global confirmation function
    window.confirmDelete = async function (
        title = "Are you sure?",
        text = "You won't be able to revert this!",
        confirmText = "Yes, delete it!"
    ) {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: confirmText,
            cancelButtonText: "Cancel",
        });

        return result.isConfirmed;
    };

    // Global success notification function
    window.showSuccess = function (
        title = "Success!",
        text = "Operation completed successfully",
        timer = 2000
    ) {
        Swal.fire({
            icon: "success",
            title: title,
            text: text,
            timer: timer,
            showConfirmButton: false,
            toast: timer > 0,
        });
    };

    // Global error notification function
    window.showError = function (
        title = "Error!",
        text = "An error occurred",
        html = null
    ) {
        Swal.fire({
            icon: "error",
            title: title,
            text: html ? null : text,
            html: html || null,
            confirmButtonText: "OK",
        });
    };

    // Global loading function
    window.showLoading = function (title = "Loading...", text = "Please wait") {
        Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });
    };

    // Global close loading function
    window.hideLoading = function () {
        Swal.close();
    };

    // Global validation error display function
    window.showValidationErrors = function (errors) {
        let errorMessage = "";

        if (typeof errors === "object") {
            const errorArray = Object.values(errors).flat();
            errorMessage = errorArray.join("<br>");
        } else if (typeof errors === "string") {
            errorMessage = errors;
        } else {
            errorMessage = "Validation failed";
        }

        Swal.fire({
            icon: "error",
            title: "Validation Error!",
            html: errorMessage,
            confirmButtonText: "OK",
        });
    };

    // Global AJAX error handler
    window.handleAjaxError = function (
        error,
        defaultMessage = "An error occurred"
    ) {
        let errorMessage = defaultMessage;

        if (error.responseJSON) {
            if (error.responseJSON.errors) {
                showValidationErrors(error.responseJSON.errors);
                return;
            } else if (error.responseJSON.message) {
                errorMessage = error.responseJSON.message;
            }
        } else if (error.status === 0) {
            errorMessage = "Network error. Please check your connection.";
        } else if (error.status === 404) {
            errorMessage = "Resource not found.";
        } else if (error.status === 500) {
            errorMessage = "Server error. Please try again later.";
        }

        showError("Error!", errorMessage);
    };

    // Global handler for data-confirm attributes
    document.addEventListener("DOMContentLoaded", function () {
        // Handle forms with data-confirm attribute
        document.addEventListener("submit", function (e) {
            const form = e.target;
            const confirmMessage = form.getAttribute("data-confirm");

            if (confirmMessage) {
                e.preventDefault();

                Swal.fire({
                    title: "Confirm Action",
                    text: confirmMessage,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, proceed!",
                    cancelButtonText: "Cancel",
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Remove the data-confirm attribute temporarily to avoid infinite loop
                        form.removeAttribute("data-confirm");
                        form.submit();
                    }
                });
            }
        });

        // Handle buttons with data-confirm attribute
        document.addEventListener("click", function (e) {
            const button = e.target.closest("[data-confirm]");

            if (button && button.tagName === "BUTTON") {
                const confirmMessage = button.getAttribute("data-confirm");

                if (confirmMessage) {
                    e.preventDefault();

                    Swal.fire({
                        title: "Confirm Action",
                        text: confirmMessage,
                        icon: "question",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, proceed!",
                        cancelButtonText: "Cancel",
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Remove the data-confirm attribute temporarily
                            button.removeAttribute("data-confirm");
                            button.click();
                        }
                    });
                }
            }
        });
    });
}
