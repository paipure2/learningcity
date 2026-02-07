/**
 * Admin Scripts
 * Scripts for WordPress Admin
 */

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    // Function to show toast notification
    function showToast(message, type = "success") {
        // Remove existing toast if any
        const existingToast = document.querySelector(".custom-toast");
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement("div");
        toast.className = `custom-toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Show toast with animation
        setTimeout(() => {
            toast.classList.add("show");
        }, 10);

        // Hide and remove toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }

    // Listen for WordPress save events
    window.addEventListener("load", function () {
        // Check if this is a page reload after save
        const isPageReloadAfterSave =
            sessionStorage.getItem("showSaveToast") === "true";

        if (isPageReloadAfterSave) {
            // Show toast for page reload case
            sessionStorage.removeItem("showSaveToast");
            setTimeout(() => {
                showToast("✓ Saved successfully", "success");
            }, 100);
            return; // Don't setup AJAX listener if this is a page reload
        }

        // Monitor for form submission completion
        const form = document.querySelector("#post");
        if (form) {
            form.addEventListener("submit", function () {
                // Set a flag to show toast on page reload
                sessionStorage.setItem("showSaveToast", "true");
            });
        }
    });

    // Add keyboard shortcut for save (Cmd+S on Mac, Ctrl+S on Windows)
    document.addEventListener("keydown", function (e) {
        // Check if Cmd (Mac) or Ctrl (Windows) + S is pressed
        if ((e.metaKey || e.ctrlKey) && e.key === "s") {
            e.preventDefault(); // Prevent browser's default save behavior

            // Find the publish/update button
            const publishButton = document.querySelector("#publish");
            const saveButton = document.querySelector("#save");

            // Click the appropriate button
            if (publishButton) {
                console.log(
                    "Save shortcut triggered - clicking publish button"
                );
                publishButton.click();
            } else if (saveButton) {
                console.log("Save shortcut triggered - clicking save button");
                saveButton.click();
            }
        }
    });

    // console.log("Admin scripts initialized");
});
