/**
 * Program to track uniqueness of input values
 * Groups inputs based on name attribute and verifies uniqueness
 * for elements with data-unique="true" attribute
 * Supports dynamically added and removed elements
 */

class UniqueInputTracker {
    constructor() {
        this.inputGroups = {};
        this.initializeTracking();
        this.setupMutationObserver();
    }

    /**
     * Initialize event listeners for all existing input fields
     */
    initializeTracking() {
        // Select all input, textarea, and select elements
        const inputs = document.querySelectorAll("input, textarea, select");
        this.registerElements(inputs);
    }

    /**
     * Register elements to the tracking system
     * @param {NodeList|Array} elements - Elements to be registered
     */
    registerElements(elements) {
        elements.forEach((input) => {
            // Check if element has name attribute
            const name = input.getAttribute("name");
            if (!name) return;

            // Check if element is already registered
            if (this.isElementRegistered(input)) return;

            // Check if element has data-unique attribute
            const needsUnique = input.getAttribute("data-unique") === "true";

            // Add to appropriate group
            if (!this.inputGroups[name]) {
                this.inputGroups[name] = {
                    elements: [],
                    uniqueRequired: false,
                };
            }

            this.inputGroups[name].elements.push(input);

            // Mark element as registered
            input.dataset.uniqueTracked = "true";

            // If any element requires uniqueness, set flag for the group
            if (needsUnique) {
                this.inputGroups[name].uniqueRequired = true;
            }

            // Add event listeners for input changes
            input.addEventListener("input", () => this.checkUniqueness(name));
            input.addEventListener("change", () => this.checkUniqueness(name));
        });

        // Check all groups initially
        for (const name in this.inputGroups) {
            this.checkUniqueness(name);
        }
    }

    /**
     * Check if element is already registered
     * @param {HTMLElement} element - Element to check
     * @returns {boolean} - true if registered, false if not
     */
    isElementRegistered(element) {
        return element.dataset.uniqueTracked === "true";
    }

    /**
     * Set up MutationObserver to monitor DOM changes
     */
    setupMutationObserver() {
        // Create MutationObserver
        const observer = new MutationObserver((mutations) => {
            let newElements = [];
            let needsCleanup = false;

            mutations.forEach((mutation) => {
                // Check added nodes
                if (mutation.type === "childList") {
                    // Check for added nodes
                    mutation.addedNodes.forEach((node) => {
                        // If node is an input element, add to list
                        if (
                            node.nodeType === 1 &&
                            (node.tagName === "INPUT" ||
                                node.tagName === "TEXTAREA" ||
                                node.tagName === "SELECT")
                        ) {
                            newElements.push(node);
                        }

                        // If node has children, check for inputs within
                        if (node.nodeType === 1 && node.querySelectorAll) {
                            const childInputs = node.querySelectorAll(
                                "input, textarea, select"
                            );
                            childInputs.forEach((input) =>
                                newElements.push(input)
                            );
                        }
                    });

                    // If nodes were removed, we need to clean up our tracking
                    if (mutation.removedNodes.length > 0) {
                        needsCleanup = true;
                    }
                }
            });

            // If there are new elements, register them
            if (newElements.length > 0) {
                this.registerElements(newElements);
            }

            // If nodes were removed, clean up tracked elements
            if (needsCleanup) {
                this.cleanupRemovedElements();
            }
        });

        // Start observing the entire document for DOM structure changes
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    /**
     * Clean up tracked elements that are no longer in the DOM
     */
    cleanupRemovedElements() {
        for (const name in this.inputGroups) {
            const group = this.inputGroups[name];
            const existingElements = [];

            // Filter out elements that are no longer in the DOM
            group.elements = group.elements.filter((element) => {
                return document.body.contains(element);
            });

            // If the group is now empty, check if we should remove it entirely
            if (group.elements.length === 0) {
                delete this.inputGroups[name];
            } else {
                // Recheck the uniqueRequired flag for the remaining elements
                let stillNeedsUnique = false;
                group.elements.forEach((el) => {
                    if (el.getAttribute("data-unique") === "true") {
                        stillNeedsUnique = true;
                    }
                });
                group.uniqueRequired = stillNeedsUnique;

                // Re-validate the group
                this.checkUniqueness(name);
            }
        }
    }

    /**
     * Check uniqueness of values within a group of inputs
     * @param {string} groupName - Group name (from name attribute)
     */
    checkUniqueness(groupName) {
        const group = this.inputGroups[groupName];

        // If group doesn't exist or doesn't require uniqueness, ignore
        if (!group || !group.uniqueRequired) return;

        const values = new Map();
        const elements = group.elements;

        // Reset all validations first
        elements.forEach((el) => {
            el.setCustomValidity("");
            el.classList.remove("duplicate-value");
        });

        // Collect all values
        elements.forEach((el) => {
            const value = el.value.trim();
            if (!value) return; // Ignore empty values

            if (values.has(value)) {
                // Mark duplicate elements
                const originalEl = values.get(value);
                this.markDuplicate(el);
                this.markDuplicate(originalEl);
            } else {
                // Store value and first element
                values.set(value, el);
            }
        });
    }

    /**
     * Mark an element as duplicate
     * @param {HTMLElement} element - Input element with duplicate value
     */
    markDuplicate(element) {
        element.setCustomValidity("Value must be unique");
        element.classList.add("duplicate-value");

        // Show error message if element is part of a form
        const form = element.form;
        if (form) {
            element.reportValidity();
        }
    }

    /**
     * Check if all inputs are valid (unique values where required)
     * @returns {boolean} - true if all valid, false if there are duplicates
     */
    isValid() {
        let valid = true;

        for (const name in this.inputGroups) {
            const group = this.inputGroups[name];
            if (!group.uniqueRequired) continue;

            const values = new Set();
            const nonEmptyElements = group.elements.filter(
                (el) => el.value.trim() !== ""
            );

            for (const el of nonEmptyElements) {
                const value = el.value.trim();
                if (values.has(value)) {
                    valid = false;
                    break;
                }
                values.add(value);
            }

            if (!valid) break;
        }

        return valid;
    }

    /**
     * Update all registered elements and check for duplicates
     * Also cleans up removed elements
     * Useful to call after major DOM changes
     */
    refreshAll() {
        this.cleanupRemovedElements();
        this.initializeTracking();
        for (const name in this.inputGroups) {
            this.checkUniqueness(name);
        }
    }

    /**
     * Add CSS styles for duplicate fields
     */
    addStyles() {
        if (!document.getElementById("unique-tracker-styles")) {
            const styleSheet = document.createElement("style");
            styleSheet.id = "unique-tracker-styles";
            styleSheet.innerHTML = `
          .duplicate-value {
            border-color: #ff3860 !important;
            background-color: rgba(255, 56, 96, 0.1) !important;
          }
        `;
            document.head.appendChild(styleSheet);
        }
    }
}
