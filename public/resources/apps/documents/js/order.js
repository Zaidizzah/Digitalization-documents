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

// Render columns
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

function handleDragLeave(e) {
    this.classList.remove("active");
}

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

// Touch event handlers (mobile)
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

function normalizeSequence() {
    const sortedColumns = [...columns].sort((a, b) => a.sequence - b.sequence);

    sortedColumns.forEach((column, index) => {
        column.sequence = index + 1;
    });
}

function resetColumns() {
    columns = initialColumns;

    renderColumns();
    showNotification("Sequence has been reset");
    announceChange("Columns have been reset to their original order");
}

async function loadColumnsData(url) {
    try {
        LOADER.show();
        const response = await fetch(url, {
            method: "GET",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": CSRF_TOKEN,
            },
        });

        if (!response.ok) {
            throw new Error("Failed to load columns data. Please try again.");
        }

        const data = await response.json();
        initialColumns = data.columns;
        columns = data.columns;

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

// Display notification
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

function isTouchDevice() {
    return "ontouchstart" in window || navigator.maxTouchPoints > 0;
}

function setupDeviceSpecificStyles() {
    if (isTouchDevice()) {
        document.body.classList.add("touch-device");
    } else {
        document.body.classList.add("mouse-device");
    }
}

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
