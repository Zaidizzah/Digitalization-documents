// Columns data
let initialColumns = null;
let columns = null;

// Tracking for drag & drop in touch events
let draggedItem = null;
let dragStartY = 0;
let currentDropIndicator = null;
let isDragging = false;
let dragOffsetY = 0;
let ghostElement = null;

/**
 * Renders all columns in the UI, sorted by sequence number. This function
 * also sets up event listeners for drag & drop (desktop), touch events (mobile),
 * and keyboard accessibility.
 */
function renderColumns() {
    const columnsContainer = document.getElementById("columns-container");
    columnsContainer.innerHTML = "";

    // Sort by sequence
    const sortedColumns = [...columns].sort((a, b) => a.sequence - b.sequence);

    // Add drop indicator
    addDropIndicator(columnsContainer, 0);

    sortedColumns.forEach((column, index) => {
        const columnItem = document.createElement("div");
        columnItem.className = "column-item";
        columnItem.dataset.id = column.id;
        columnItem.dataset.index = index;
        columnItem.setAttribute("draggable", "true");
        columnItem.setAttribute("aria-grabbed", "false");
        columnItem.setAttribute("role", "listitem");

        columnItem.innerHTML = `
            <!-- Input hidden for id of column attribute and sequence -->
            <input type="hidden" name="id[]" value="${column.id}">
            <input type="hidden" name="sequence[]" value="${column.sequence}">

            <div class="column-handle" aria-label="Drag handle"><i class="bi bi-list fs-5"></i></div>
            <div class="column-sequence">${column.sequence}</div>
            <div class="column-name">${column.name}</div>
            <div class="column-type">${column.type}</div>
        `;

        // Add event listener for drag & drop (desktop)
        columnItem.addEventListener("dragstart", handleDragStart);
        columnItem.addEventListener("dragend", handleDragEnd);

        // Add event listener for touch events (mobile)
        columnItem.addEventListener("touchstart", handleTouchStart, {
            passive: false,
        });
        columnItem.addEventListener("touchmove", handleTouchMove, {
            passive: false,
        });
        columnItem.addEventListener("touchend", handleTouchEnd);

        // Add event listener for keyboard accessibility
        columnItem.addEventListener("keydown", handleKeyDown);

        columnsContainer.appendChild(columnItem);

        // Add drop indicator after each item
        addDropIndicator(columnsContainer, index + 1);
    });
}

/**
 * Creates a drop indicator element and appends it to the given container at the specified index.
 * Listens for dragover, dragleave, and drop events on the indicator element.
 * @param {HTMLElement} container - The container element to append the drop indicator to.
 * @param {number} index - The index of the drop indicator in the container.
 */
function addDropIndicator(container, index) {
    const indicator = document.createElement("div");
    indicator.className = "drop-indicator";
    indicator.dataset.index = index;
    indicator.setAttribute("role", "presentation");

    // Add event listener for drag & drop
    indicator.addEventListener("dragover", handleDragOver);
    indicator.addEventListener("dragleave", handleDragLeave);
    indicator.addEventListener("drop", handleDrop);

    container.appendChild(indicator);
}

/**
 * Handles the start of a drag & drop event, for desktop and mobile devices.
 *
 * Sets up the custom drag image, stores the mouse offset, and sets the effect
 * and data for the drag event.
 *
 * @param {Event} e The dragstart event.
 */
function handleDragStart(e) {
    if (!e.target.closest(".column-item")) return;

    const columnItem = e.target.closest(".column-item");
    columnItem.classList.add("dragging");
    columnItem.setAttribute("aria-grabbed", "true");

    draggedItem = columnItem;

    // Store the mouse offset
    const rect = columnItem.getBoundingClientRect();
    dragOffsetY = e.clientY - rect.top;

    // Set drag data and effect
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", columnItem.dataset.id);

    // Create custom drag image for better visuals across browsers
    const dragImage = columnItem.cloneNode(true);
    dragImage.style.width = `${columnItem.offsetWidth}px`;
    dragImage.classList.add("drag-ghost");
    document.body.appendChild(dragImage);

    // Set transparent ghost in some browsers
    e.dataTransfer.setDragImage(dragImage, 0, 0);

    // Store reference to remove later
    ghostElement = dragImage;

    // Hide the ghost immediately after creating it
    setTimeout(() => {
        dragImage.style.display = "none";
    }, 0);

    isDragging = true;
}

/**
 * Handles drag end event.
 *
 * Resets the dragged item's state and removes the custom drag image.
 * Also resets all drop indicators.
 *
 * @param {DragEvent} e The event object containing the drag object.
 */
function handleDragEnd(e) {
    if (draggedItem) {
        draggedItem.classList.remove("dragging");
        draggedItem.setAttribute("aria-grabbed", "false");
        draggedItem = null;
    }

    // Remove ghost element if it exists
    if (ghostElement && ghostElement.parentNode) {
        ghostElement.parentNode.removeChild(ghostElement);
        ghostElement = null;
    }

    // Reset all drop indicators
    document.querySelectorAll(".drop-indicator").forEach((indicator) => {
        indicator.classList.remove("active");
    });

    isDragging = false;
}

/**
 * Handles the drag over event for drop indicators.
 *
 * Prevents the default behavior of the event, sets the drop effect to "move",
 * and adds an "active" class to the current drop indicator element, providing
 * visual feedback that the item is currently being dragged over it.
 *
 * @param {DragEvent} e - The drag over event.
 */
function handleDragOver(e) {
    if (!isDragging) return;

    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    // Remove active class from all indicators
    document.querySelectorAll(".drop-indicator").forEach((ind) => {
        ind.classList.remove("active");
    });

    // Add active class to current indicator
    this.classList.add("active");
    currentDropIndicator = this;
}

/**
 * Handles the drag leave event for drop indicators.
 *
 * Removes the "active" class from the drop indicator element
 * when a dragged item leaves its area, providing visual feedback
 * that the item is no longer a potential drop target.
 *
 * @param {DragEvent} e - The drag leave event.
 */

function handleDragLeave(e) {
    this.classList.remove("active");
}

/**
 * Handles drop event when a column is being reordered.
 *
 * @param {DragEvent} e - The drop event.
 *
 * @returns {void}
 */
function handleDrop(e) {
    e.preventDefault();

    // If no item is being dragged, abort
    if (!draggedItem) return;

    // Get the ID of the dragged column
    const columnId = draggedItem.dataset.id;

    // Get the drop index
    const dropIndex = parseInt(this.dataset.index, 10);

    // Remove active indicator
    this.classList.remove("active");

    // Reorder columns based on drop position
    reorderColumns(columnId, dropIndex);
}

/**
 * Handles touch start events during drag and drop.
 *
 * Prevents scrolling when starting drag from a column handle.
 * Stores the touch offset and creates a visual feedback element
 * (ghost element) that is used to indicate the drag position.
 *
 * @param {TouchEvent} e The event object containing the touch object.
 */
function handleTouchStart(e) {
    if (!e.target.closest(".column-handle")) return;

    e.preventDefault(); // Prevent scrolling when starting drag from handle

    const touch = e.touches[0];
    const columnItem = e.target.closest(".column-item");

    draggedItem = columnItem;
    dragStartY = touch.clientY;

    // Store the touch offset
    const rect = columnItem.getBoundingClientRect();
    dragOffsetY = touch.clientY - rect.top;

    // Create visual feedback for touch drag
    const dragImage = columnItem.cloneNode(true);
    dragImage.style.position = "fixed";
    dragImage.style.top = `${touch.clientY - dragOffsetY}px`;
    dragImage.style.left = `${rect.left}px`;
    dragImage.style.width = `${columnItem.offsetWidth}px`;
    dragImage.style.zIndex = "1000";
    dragImage.style.opacity = "0.8";
    dragImage.style.pointerEvents = "none";
    dragImage.classList.add("touch-drag-ghost");

    document.body.appendChild(dragImage);
    ghostElement = dragImage;

    columnItem.classList.add("dragging");
    columnItem.style.opacity = "0.4";

    isDragging = true;
}

/**
 * Handles touch move events during drag and drop.
 *
 * Updates the position of the ghost element as the user drags.
 * Finds the drop indicator the user is over and updates its "active" class.
 * If no indicator is found, finds the closest indicator and updates its "active" class.
 *
 * @param {TouchEvent} e The event object containing the touch object.
 */
function handleTouchMove(e) {
    if (!isDragging || !draggedItem) return;

    e.preventDefault(); // Prevent scrolling during drag

    const touch = e.touches[0];
    const currentY = touch.clientY;

    // Update ghost element position
    if (ghostElement) {
        ghostElement.style.top = `${currentY - dragOffsetY}px`;
    }

    // Find the drop indicator we're over
    const indicators = document.querySelectorAll(".drop-indicator");
    let activeIndicator = null;

    indicators.forEach((indicator) => {
        indicator.classList.remove("active");

        const rect = indicator.getBoundingClientRect();
        if (currentY >= rect.top && currentY <= rect.bottom) {
            indicator.classList.add("active");
            activeIndicator = indicator;
            currentDropIndicator = indicator;
        }
    });

    // If no indicator is found, find the closest one
    if (!activeIndicator) {
        let closestDistance = Infinity;

        indicators.forEach((indicator) => {
            const rect = indicator.getBoundingClientRect();
            const middle = rect.top + rect.height / 2;
            const distance = Math.abs(currentY - middle);

            if (distance < closestDistance) {
                closestDistance = distance;
                activeIndicator = indicator;
            }
        });

        if (activeIndicator) {
            activeIndicator.classList.add("active");
            currentDropIndicator = activeIndicator;
        }
    }
}

/**
 * Handles the touchend event after a column has been dragged and dropped.
 *
 * Restores the original item appearance, cleans up the ghost element, and
 * processes the drop by calling reorderColumns if we have an active drop
 * indicator.  Resets the state after the drop is complete.
 */
function handleTouchEnd(e) {
    if (!isDragging || !draggedItem) return;

    // Restore original item appearance
    draggedItem.classList.remove("dragging");
    draggedItem.style.opacity = "";

    // Clean up ghost element
    if (ghostElement && ghostElement.parentNode) {
        ghostElement.parentNode.removeChild(ghostElement);
        ghostElement = null;
    }

    // Process the drop if we have an active indicator
    if (currentDropIndicator) {
        const columnId = draggedItem.dataset.id;
        const dropIndex = parseInt(currentDropIndicator.dataset.index, 10);

        currentDropIndicator.classList.remove("active");
        reorderColumns(columnId, dropIndex);
    }

    // Reset state
    draggedItem = null;
    currentDropIndicator = null;
    isDragging = false;
}

/**
 * Handles keyboard interactions for column items, enabling accessibility
 * features such as grabbing and moving items using the keyboard.
 *
 * @param {Event} e - The keyboard event triggered by user interaction.
 *
 * - Space or Enter: Toggles the grab/release state of the column item.
 * - ArrowUp: Moves the grabbed item up in the order, if possible.
 * - ArrowDown: Moves the grabbed item down in the order, if possible.
 *
 * The function ensures that only one item can be grabbed at a time and
 * provides visual feedback for the grabbed state.
 */

function handleKeyDown(e) {
    const columnItem = e.target.closest(".column-item");
    if (!columnItem) return;

    const currentIndex = parseInt(columnItem.dataset.index, 10);
    const currentId = columnItem.dataset.id;

    // Space or Enter to grab/release
    if (e.code === "Space" || e.code === "Enter") {
        e.preventDefault();

        const isGrabbed = columnItem.getAttribute("aria-grabbed") === "true";

        if (isGrabbed) {
            // Release
            columnItem.setAttribute("aria-grabbed", "false");
            columnItem.classList.remove("keyboard-dragging");
            draggedItem = null;
        } else {
            // Grab
            // First reset any previously grabbed items
            document
                .querySelectorAll('[aria-grabbed="true"]')
                .forEach((item) => {
                    item.setAttribute("aria-grabbed", "false");
                    item.classList.remove("keyboard-dragging");
                });

            columnItem.setAttribute("aria-grabbed", "true");
            columnItem.classList.add("keyboard-dragging");
            draggedItem = columnItem;
        }
    }

    // If item is grabbed, arrow keys to move
    if (columnItem.getAttribute("aria-grabbed") === "true") {
        if (e.code === "ArrowUp" && currentIndex > 0) {
            e.preventDefault();
            reorderColumns(currentId, currentIndex);
            showNotification("Item moved up");
        } else if (
            e.code === "ArrowDown" &&
            currentIndex < columns.length - 1
        ) {
            e.preventDefault();
            reorderColumns(currentId, currentIndex + 2);
            showNotification("Item moved down");
        }
    }
}

/**
 * Reorders a column in the schema based on its ID and the desired index.
 * The column's sequence number is updated accordingly, and the UI is re-rendered.
 * A notification is displayed to the user, and the change is announced for screen readers.
 *
 * @param {string} columnId The ID of the column to reorder.
 * @param {number} dropIndex The desired index of the column after reordering.
 */
function reorderColumns(columnId, dropIndex) {
    const draggedColumn = columns.find((col) => col.id === columnId);
    if (!draggedColumn) return;

    const sortedColumns = [...columns].sort((a, b) => a.sequence - b.sequence);
    const currentIndex = sortedColumns.findIndex((col) => col.id === columnId);

    // If dragged column is already at the target position, abort
    if (
        currentIndex === dropIndex ||
        (currentIndex + 1 === dropIndex && dropIndex === sortedColumns.length)
    )
        return;

    // Calculate new sequence number
    if (dropIndex === 0) {
        draggedColumn.sequence = sortedColumns[0].sequence - 1;
    } else if (dropIndex >= sortedColumns.length) {
        draggedColumn.sequence =
            sortedColumns[sortedColumns.length - 1].sequence + 1;
    } else {
        const beforeColumn = sortedColumns[dropIndex - 1];
        const afterColumn = sortedColumns[dropIndex];
        draggedColumn.sequence =
            (beforeColumn.sequence + afterColumn.sequence) / 2;
    }

    // Normalize sequence numbers
    normalizeSequence();

    // Re-render UI
    renderColumns();

    // Display notification
    showNotification("Columns have been reordered");

    // Announce for screen readers
    announceChange(
        `Column ${draggedColumn.name} moved to position ${dropIndex}`
    );
}

/**
 * Normalizes the sequence numbers of the columns to a continuous
 * sequence starting from 1.
 *
 * This function is called after the user has reordered the columns.
 *
 * @returns {void}
 */

function normalizeSequence() {
    const sortedColumns = [...columns].sort((a, b) => a.sequence - b.sequence);

    sortedColumns.forEach((column, index) => {
        column.sequence = index + 1;
    });
}

/**
 * Resets the columns to their original order and re-renders the UI.
 *
 * This function is called when the user clicks the "Reset" button.
 *
 * @returns {void}
 */
function resetColumns() {
    columns = JSON.parse(JSON.stringify(initialColumns));

    renderColumns();
    showNotification("Sequence has been reset");
    announceChange("Columns have been reset to their original order");
}

/**
 * Loads the columns data from the specified URL and initializes the UI.
 *
 * @param {string} url - The URL to fetch the columns data from.
 *
 * @throws {Error} - If the response is not ok, an error is thrown with a
 *  message indicating the failure.
 *
 * @returns {Promise<void>}
 */
async function loadColumnsData(url) {
    try {
        LOADER.show(true);
        const response = await fetch(url, {
            method: "GET",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "XSRF-TOKEN": XSRF_TOKEN,
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
            credentials: "include",
        });

        if (!response.ok) {
            throw new Error("Failed to load columns data. Please try again.");
        }

        const data = await response.json();

        // create new object of data columns
        initialColumns = JSON.parse(JSON.stringify(data.columns));
        columns = JSON.parse(JSON.stringify(data.columns));

        // Render columns
        renderColumns();
    } catch (error) {
        // Display error message
        toast(error.message, "error");

        console.error(error);
    } finally {
        LOADER.hide();
    }
}

/**
 * Displays a notification message to the user.
 *
 * This function creates a div element with a class of "notification" if it
 * doesn't already exist, and appends it to the document body. It sets the
 * provided message as the text content of the notification and makes it
 * visible. The notification will automatically disappear after 3 seconds.
 *
 * @param {string} message - The message to display in the notification.
 */
function showNotification(message) {
    let notification = document.getElementById("notification");
    if (!notification) {
        // Create notification element if it doesn't exist
        notification = document.createElement("div");
        notification.className = "notification";
        notification.id = "notification";
        notification.setAttribute("role", "alert");
        notification.setAttribute("aria-live", "polite");
        notification.setAttribute("aria-label", "Notification message");
        document.body.appendChild(notification);
    }

    notification.textContent = message;
    notification.style.display = "block";

    // Remove any existing timeout
    if (notification.timeoutId) {
        clearTimeout(notification.timeoutId);
    }

    // Set new timeout
    notification.timeoutId = setTimeout(() => {
        notification.style.display = "none";
    }, 3000);
}

/**
 * Announces a change to the user using a screen reader.
 *
 * If the `sr-announcer` element does not exist, it is created and appended to
 * the document body. The message is then set as the textContent of the
 * announcer element, which will be read out by screen readers.
 *
 * @param {string} message - The message to be announced to the user.
 */
function announceChange(message) {
    let announcer = document.getElementById("sr-announcer");

    if (!announcer) {
        announcer = document.createElement("div");
        announcer.id = "sr-announcer";
        announcer.className = "sr-only";
        announcer.setAttribute("aria-live", "polite");
        announcer.setAttribute("aria-atomic", "true");
        document.body.appendChild(announcer);
    }

    announcer.textContent = message;
}

/**
 * Determines if the current device is a touch-enabled device.
 *
 * Checks for the presence of touch-specific capabilities such as 'ontouchstart'
 * in the window object or a non-zero number of maximum touch points supported
 * by the device.
 *
 * @returns {boolean} True if the device supports touch, otherwise false.
 */
function isTouchDevice() {
    return "ontouchstart" in window || navigator.maxTouchPoints > 0;
}

/**
 * Adds classnames to the document body to indicate whether the user is using
 * a touch device or a mouse device.
 *
 * This is used to adjust the column drag handles to be larger on touch
 * devices.
 */
function setupDeviceSpecificStyles() {
    if (isTouchDevice()) {
        document.body.classList.add("touch-device");
    } else {
        document.body.classList.add("mouse-device");
    }
}

/**
 * Removes all event listeners from the document and removes the ghost element
 * from the DOM. Used to clean up after reordering the columns.
 */
function cleanup() {
    if (ghostElement && ghostElement.parentNode) {
        ghostElement.parentNode.removeChild(ghostElement);
    }

    document.querySelectorAll(".column-item").forEach((item) => {
        item.removeEventListener("dragstart", handleDragStart);
        item.removeEventListener("dragend", handleDragEnd);
        item.removeEventListener("touchstart", handleTouchStart);
        item.removeEventListener("touchmove", handleTouchMove);
        item.removeEventListener("touchend", handleTouchEnd);
        item.removeEventListener("keydown", handleKeyDown);
    });

    document.querySelectorAll(".drop-indicator").forEach((indicator) => {
        indicator.removeEventListener("dragover", handleDragOver);
        indicator.removeEventListener("dragleave", handleDragLeave);
        indicator.removeEventListener("drop", handleDrop);
    });
}

(() => {
    "use strict";

    setupDeviceSpecificStyles();

    const resetBtn = document.getElementById("btn-reset-order");

    if (resetBtn) {
        resetBtn.addEventListener("click", resetColumns);
    }

    // Handle window resize events
    window.addEventListener("resize", () => {
        // Clear any ghost elements that might be stuck
        if (ghostElement && ghostElement.parentNode) {
            ghostElement.parentNode.removeChild(ghostElement);
            ghostElement = null;
        }
    });

    // Provide cleanup method
    window.cleanupDragDrop = cleanup;
})();
