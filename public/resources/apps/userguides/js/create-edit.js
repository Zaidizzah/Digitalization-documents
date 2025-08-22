// Initialize the text editor
const TEXT_EDITOR_HTML = new TextEditorHTML("editor-content-wrapper", {
    uploadEndpoint: `${location.origin}/api/settings/user-guide/upload`,
    minHeight: "500px",
    attributes: {
        spellcheck: false,
        name: "content",
        placeholder: "Type your contents here...",
        required: true,
    },
});

(() => {
    "use strict";

    document.querySelectorAll(".markdown-input:not(.list").forEach((el) => {
        let lines = el.textContent.split("\n");
        lines = lines.map((line) => line.replace(/^\s+/, ""));
        el.textContent = lines.join("\n");
    });

    document.addEventListener("click", function (e) {
        if (
            e.target.tagName === "BUTTON" &&
            e.target.classList.contains("btn-toggle-children-visibility")
        ) {
            const children = document.getElementById(
                e.target.getAttribute("aria-controls")
            );
            if (children) {
                if (children.classList.contains("collapsed")) {
                    children.classList.remove("collapsed");
                    children.classList.add("expanded");
                    children.ariaExpanded = true;
                } else {
                    children.classList.remove("expanded");
                    children.classList.add("collapsed");
                    children.ariaExpanded = false;
                }

                if (e.target.classList.contains("collapsed")) {
                    e.target.classList.remove("collapsed");
                    e.target.classList.add("expanded");
                } else {
                    e.target.classList.remove("expanded");
                    e.target.classList.add("collapsed");
                }
            }
        }
    });

    const btnClearSelection = document.querySelector(
        ".user-guides-actions-wrapper button#user-guides-clear-selection-btn"
    );
    if (btnClearSelection) {
        btnClearSelection.addEventListener("click", function () {
            const radioInputs = document.querySelectorAll(
                ".user-guides-tree-item .radio-input:checked"
            );
            if (radioInputs)
                radioInputs.forEach((el) => {
                    el.checked = false;
                    el.ariaChecked = false;
                });

            const treeItems = document.querySelectorAll(
                ".user-guides-tree-item.selected"
            );
            if (treeItems)
                treeItems.forEach((el) => {
                    el.classList.remove("selected");
                });
        });
    }

    const radioInputs = document.querySelectorAll(
        '.user-guides-wrapper input[type="radio"].radio-input'
    );
    if (radioInputs) {
        radioInputs.forEach((el) => {
            el.addEventListener("change", function () {
                const treeItem = el.closest(".user-guides-tree-item");

                if (el.checked) {
                    el.ariaChecked = true;
                    // Remove all selected tree items
                    const treeItems = document.querySelectorAll(
                        ".user-guides-tree-item.selected"
                    );
                    if (treeItems)
                        treeItems.forEach((el) => {
                            el.classList.remove("selected");
                        });

                    if (treeItem) treeItem.classList.add("selected");
                } else {
                    el.ariaChecked = false;

                    if (treeItem) treeItem.classList.remove("selected");
                }
            });
        });
    }

    const btnLoadMoreData = document.querySelector(
        "button.btn-load-more-data#user-guides-load-more-data-btn"
    );
    const treeItemView = document.querySelector(
        ".user-guides-tree-view#user-guides-tree-view"
    );
    if (btnLoadMoreData) {
        let isGetting = false;
        const loadMoreData = async (e) => {
            // Warn user if clicking button when fetching process is running
            if (isGetting === true) {
                console.warn(
                    "You must wait until fetching more data process is succesfully finished."
                );
                return;
            }

            const controlElement =
                btnLoadMoreData.getAttribute("aria-controls") !== null
                    ? document.querySelector(
                          `#${btnLoadMoreData.getAttribute("aria-controls")}`
                      )
                    : null;
            let currentPage = btnLoadMoreData.dataset.currentPage,
                lastPage = btnLoadMoreData.dataset.lastPage;

            // Check if all resource is defined and not NULL
            if (
                controlElement !== null &&
                currentPage !== null &&
                lastPage !== null
            ) {
                // Parse type to integer, then increase amount of currentPage by 1
                currentPage = parseInt(currentPage, 10);
                lastPage = parseInt(lastPage, 10);

                try {
                    currentPage++;

                    LOADER.show(true, "bottom-right");
                    // Change status isGetting variable to true
                    isGetting = true;

                    // Get more data's
                    const response = await fetch(
                        `${location.origin}/api/settings/user-guide/get?page=${currentPage}`,
                        {
                            method: "GET",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                                "XSRF-TOKEN": XSRF_TOKEN,
                                Accept: "application/json",
                            },
                            credentials: "include",
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            "Failed to get user guide data. Please try again."
                        );
                    }

                    const DATA = await response.json();

                    // Check if response structure is valid
                    if (
                        (DATA.hasOwnProperty("success") &&
                            DATA.success === false) ||
                        DATA.hasOwnProperty("html") === false
                    ) {
                        throw new Error(
                            "Failed to get user guide data. Please try again."
                        );
                    }

                    if (treeItemView instanceof Element) {
                        treeItemView.insertAdjacentHTML("beforeend", DATA.html);
                    } else {
                        throw new Error(
                            "Failed to set user guide data, because treeItemView element cannot be found in this page."
                        );
                    }
                } catch (error) {
                    // Display error message
                    toast(error.message, "error");

                    console.log(error);
                    return;
                } finally {
                    LOADER.hide();

                    // Change status isGetting variable to false
                    isGetting = false;
                    btnLoadMoreData.dataset.currentPage = currentPage;

                    // And check if currentPage value is equal lastPage value, then remove this event listener
                    if (currentPage === lastPage) {
                        btnLoadMoreData.removeEventListener(
                            "click",
                            loadMoreData
                        );

                        // Remove button
                        btnLoadMoreData.remove();
                    }
                }
            }
        };
        btnLoadMoreData.addEventListener("click", loadMoreData);
    }
})();
