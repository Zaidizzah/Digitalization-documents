(() => {
    "use strict";

    /**
     * Create and display a toast message
     *
     * @param {string} message
     * @param {string} type
     * @return {string}
     */
    window.toast = (
        message,
        type = "success",
        canHide = true,
        duration = 15000
    ) => {
        const APP_CONTENT = document.querySelector("main.app-content");
        let toastContainer = APP_CONTENT.querySelector(".toast-container");
        if (!toastContainer) {
            APP_CONTENT.insertAdjacentHTML(
                "beforeend",
                `<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>`
            );
            toastContainer = APP_CONTENT.querySelector(".toast-container");
        }
        const toastId = `toast-${new Date().getTime().toString(36)}`;
        toastContainer.insertAdjacentHTML(
            "beforeend",
            `
        <div id="${toastId}" class="toast ${type} fade" role="alert" aria-live="assertive" aria-atomic="true" ${
                canHide
                    ? `data-bs-autohide="true" data-bs-delay="${duration}"`
                    : 'data-bs-autohide="false"'
            } can-hide="${canHide}" aria-label="toast">
            <div class="toast-header">
                <strong class="me-auto">${
                    type.charAt(0).toUpperCase() + type.slice(1)
                }!</strong>
                <button type="button" class="btn-close bg-light-subtle" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <p class="paragraph">${message}</p>
            </div>
        </div>
        `
        );

        // Show toast
        const toastElement = toastContainer.querySelector(`#${toastId}`);
        if (toastElement) {
            const bootstrapToast = new bootstrap.Toast(toastElement);
            bootstrapToast.show();
        }
    };

    /**
     * Returns a debounced function that delays invoking func until after wait milliseconds have elapsed since the last time the debounced function was invoked.
     *
     * @param {Function} func The function to debounce.
     * @param {number} [wait=0] The number of milliseconds to delay.
     * @param {boolean} [immediate=false] Pass `true` for `immediate` to apply the function on the leading instead of the trailing edge of the `wait` timeout.
     * @return {Function} The debounced function.
     */
    window.debounce = (func, wait, immediate) => {
        let timeout;
        return () => {
            const context = this;
            const args = arguments;
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };

    window.CSRF_TOKEN = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content");

    window.INITIALIZE_TOOlTIPS = () => {
        const tooltipTriggerList = document.querySelectorAll(
            '[data-bs-toggle="tooltip"]:not([data-tooltip-initialize]):not(:disabled)'
        );
        const tooltipList = [...tooltipTriggerList].map((tooltipTriggerEl) => {
            new bootstrap.Tooltip(tooltipTriggerEl);
            tooltipTriggerEl.setAttribute("data-tooltip-initialize", true);
        });
    };
    INITIALIZE_TOOlTIPS();

    // Initialize toast
    const toastContainer = document.querySelector(".toast-container");
    if (toastContainer) {
        const toasts = Array.from(toastContainer.children).filter(
            (toast) => !toast.classList.contains("hide")
        );
        toasts.forEach((toast) => new bootstrap.Toast(toast).show());
    }

    // Remove the toast after hide
    document.addEventListener("hidden.bs.toast", function (e) {
        e.target.remove();

        // remove toast container if toast element is empty
        if (toastContainer && toastContainer.children.length === 0)
            toastContainer.remove();
    });

    window.DEFAULT_IMAGE = "/resources/images/dummys/no-image-available.jpg";

    window.IMAGE_PREVIEW = new ImagePreview();

    window.LOADER = {
        structure: `
        <div id="loader" class="loader-container">
            <div class="loader-box">
                <div class="loader-spinner"></div>
                <div class="loader-text">Processing...</div>
            </div>
        </div>`,
        remove: function (loaderElement) {
            if (loaderElement) {
                loaderElement.remove();
            }
        },
        show: function () {
            let loaderElement = document.getElementById("loader");
            if (!loaderElement) {
                document
                    .querySelector("body")
                    .insertAdjacentHTML("beforeend", this.structure);
                loaderElement = document.getElementById("loader");
            }
            loaderElement.style.display = "flex";

            // Disable pointer events on the document
            document.body.classList.add("pointer-events-none");
        },

        hide: function () {
            let loaderElement = document.getElementById("loader");
            if (loaderElement) {
                loaderElement.style.display = "none";
                setTimeout(() => this.remove(loaderElement), 500);
                // Enable pointer events on the document
                document.body.classList.remove("pointer-events-none");
            }
        },
    };

    // popover script
    const popoverButtons = document.querySelectorAll(
        "button[popovertarget], a[popovertarget]"
    );

    popoverButtons.forEach((button) => {
        let timeout = 0;
        button.addEventListener("mouseenter", () => {
            const target = button.getAttribute("popovertarget");
            const popover = document.querySelector(target);
            // delay opening
            timeout = setTimeout(() => {
                popover.showPopover();
            }, 1500);
        });

        button.addEventListener("mouseleave", () => {
            clearTimeout(timeout);
            button.removeAttribute("style");
        });
    });

    /**
     * Add slashes to a string.
     *
     * This function escapes special characters in a string, such as
     * backslashes, tabs, newlines, form feeds, carriage returns, single
     * quotes, and double quotes, by prepending a backslash to them.
     *
     * @param {string} string The string to add slashes to.
     * @return {string} The string with added slashes.
     */
    window.addSlashes = (string) => {
        return string
            .replace(/\\/g, "\\\\")
            .replace(/\u0008/g, "\\b")
            .replace(/\t/g, "\\t")
            .replace(/\n/g, "\\n")
            .replace(/\f/g, "\\f")
            .replace(/\r/g, "\\r")
            .replace(/'/g, "\\'")
            .replace(/"/g, '\\"');
    };
})();
