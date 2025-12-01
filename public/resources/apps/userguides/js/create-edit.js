// Initialize the text editor
const TEXT_EDITOR_HTML = new TextEditorHTML("editor-content-wrapper", {
    uploadEndpoint: `${location.origin}/api/settings/user-guides/upload`,
    minHeight: "500px",
    attributes: {
        spellcheck: false,
        name: "content",
        placeholder: "Type your contents here...",
        required: true,
    },
});

(async () => {
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
        ),
        btnClearUncheckSelection = document.querySelector(
            ".user-guides-actions-wrapper button#user-guides-clear-uncheck-current-selection-btn"
        ),
        radioInputs = document.querySelectorAll(
            '.user-guides-wrapper input[type="radio"].radio-input:not(.active)'
        ),
        isCreateFormState =
            window.location.pathname.split("/").pop() === "create",
        isEditFormState = window.location.pathname.split("/").at(-2) === "edit",
        selectedTreeItems = document.querySelectorAll(
            ".user-guides-tree-item.selected"
        );

    let SELECTED_RADIO_INPUT_INDEX = null,
        CURRENT_EDIT_PARENT_TREE_ITEM_ID = null,
        CURRENT_EDIT_PARENT_TREE_ITEM = null;

    // if the current action is edit then replace null value from CURRENT_EDIT_PARENT_TREE_ITEM to parent tree item element
    if (isEditFormState) {
        CURRENT_EDIT_PARENT_TREE_ITEM_ID = document
            .querySelector(
                `.user-guides-tree-item.active[data-id="${window.location.pathname
                    .split("/")
                    .pop()}"]`
            )
            .parentElement.id.split("-")
            .pop();

        CURRENT_EDIT_PARENT_TREE_ITEM = document.querySelector(
            `.user-guides-tree-item[data-id="${CURRENT_EDIT_PARENT_TREE_ITEM_ID}"]`
        );
    }

    // Check if value of CURRENT_EDIT_PARENT_TREE_ITEM_ID and CURRENT_EDIT_PARENT_TREE_ITEM is not null
    if (
        isEditFormState &&
        (CURRENT_EDIT_PARENT_TREE_ITEM instanceof Element === false ||
            CURRENT_EDIT_PARENT_TREE_ITEM_ID === null)
    ) {
        toast(
            "Sorry, the parent tree item not found. can you please refresh this page or reported if this issue is still happens.",
            "error"
        );
    }

    if (btnClearSelection) {
        btnClearSelection.addEventListener("click", function () {
            if (radioInputs)
                for (let index = 0; index < radioInputs.length; index++) {
                    if (
                        radioInputs[index].value ===
                        CURRENT_EDIT_PARENT_TREE_ITEM_ID
                    ) {
                        SELECTED_RADIO_INPUT_INDEX = index;
                        radioInputs[index].checked = true;
                        radioInputs[index].ariaChecked = true;
                        continue;
                    }
                    radioInputs[index].checked = false;
                    radioInputs[index].removeAttribute("checked");
                    radioInputs[index].ariaChecked = false;
                }

            // Remove selected elements
            if (selectedTreeItems)
                selectedTreeItems.forEach((element) => {
                    if (
                        element.dataset.id &&
                        element.dataset.id !== CURRENT_EDIT_PARENT_TREE_ITEM_ID
                    )
                        element.classList.remove("selected");

                    element.classList.add("selected");
                });

            // check if form state is edit
            if (isEditFormState) {
                let CHILDREN_PARENT_TREE_ITEM =
                    radioInputs[SELECTED_RADIO_INPUT_INDEX].closest(
                        ".children"
                    );

                // Expand all parent elements
                while (CHILDREN_PARENT_TREE_ITEM instanceof Element) {
                    CHILDREN_PARENT_TREE_ITEM.classList.remove("collapsed");
                    CHILDREN_PARENT_TREE_ITEM.classList.add("expanded");
                    CHILDREN_PARENT_TREE_ITEM.ariaExpanded = true;
                    CHILDREN_PARENT_TREE_ITEM =
                        parent.parentElement?.closest(".children");
                }
            }
        });
    }

    if (btnClearUncheckSelection) {
        btnClearUncheckSelection.addEventListener("click", function () {
            if (radioInputs)
                radioInputs.forEach((element) => {
                    element.checked = false;
                    element.removeAttribute("checked");
                    element.ariaChecked = false;
                });

            if (selectedTreeItems)
                selectedTreeItems.forEach((element) => {
                    element.classList.remove("selected");
                });
        });
    }

    // if (radioInputs) {
    //     radioInputs.forEach((el, index) => {
    //         if (el.checked === true) {
    //             selectedRadioInput = index;
    //         }

    //         el.addEventListener("change", function () {
    //             const treeItem = el.closest(".user-guides-tree-item");

    //             if (el.checked) {
    //                 el.ariaChecked = true;
    //                 // Remove all selected tree items
    //                 const treeItems = document.querySelectorAll(
    //                     ".user-guides-tree-item.selected"
    //                 );
    //                 if (treeItems)
    //                     treeItems.forEach((el) => {
    //                         el.classList.remove("selected");
    //                     });

    //                 if (radioInputs)
    //                     radioInputs.forEach((_el, _index) => {
    //                         if (_index !== index) {
    //                             _el.checked = false;
    //                             _el.ariaChecked = false;
    //                             _el.removeAttribute("checked");
    //                         }
    //                     });

    //                 if (treeItem) treeItem.classList.add("selected");
    //             } else {
    //                 el.ariaChecked = false;
    //                 el.removeAttribute("checked");

    //                 if (treeItem) treeItem.classList.remove("selected");
    //             }
    //         });
    //     });
    // }

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
            let CURRENTPAGE = btnLoadMoreData.dataset.currentPage,
                LASTPAGE = btnLoadMoreData.dataset.lastPage;

            // Check if all resource is defined and not NULL
            if (
                controlElement !== null &&
                CURRENTPAGE !== null &&
                LASTPAGE !== null
            ) {
                // Parse type to integer, then increase amount of currentPage by 1
                CURRENTPAGE = parseInt(CURRENTPAGE, 10);
                LASTPAGE = parseInt(LASTPAGE, 10);

                try {
                    CURRENTPAGE++;

                    LOADER.show(true, "bottom-right");
                    // Change status isGetting variable to true
                    isGetting = true;

                    // Get more data's
                    const response = await fetch(
                        `${location.origin}/api/settings/user-guides/get?page=${CURRENTPAGE}`,
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
                            `Failed to get user guide data from server for page ${CURRENTPAGE}. Please try to refresh this page.`
                        );
                    }

                    const DATA = await response.json();

                    // Check if response structure is valid
                    if (
                        (DATA.hasOwnProperty("success") &&
                            DATA.success === false) ||
                        DATA.hasOwnProperty("html") === false
                    ) {
                        throw new Error(DATA.message);
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
                    btnLoadMoreData.dataset.currentPage = CURRENTPAGE;

                    // And check if currentPage value is equal lastPage value, then remove this event listener
                    if (CURRENTPAGE === LASTPAGE) {
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

    const formSettings = document.querySelector("form#form-settings");
    if (formSettings) {
        formSettings.addEventListener("submit", function (e) {
            e.preventDefault();

            const confirmation = confirm(
                `Are you sure you want to ${formSettings.dataset.action} this user guide data?`
            );
            if (confirmation) {
                formSettings.submit();
            }
        });
    }

    // Handle getting userguide content after page has been loaded completely and if current page is for editing userguide data
    if (
        TEXT_EDITOR_HTML instanceof TextEditorHTML &&
        TEXT_EDITOR_HTML.readyState === true &&
        window.location.pathname.split("/").at(-2) === "edit"
    ) {
        LOADER.show(true);
        try {
            const response = await fetch(
                `${
                    location.origin
                }/api/user-guides/get/content/${window.location.pathname
                    .split("/")
                    .pop()}`,
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
                    `Failed to get user guide data from server. Please try to refresh this page.`
                );
            }

            const DATA = await response.json();

            // Check if response structure is valid
            if (
                (DATA.hasOwnProperty("success") && DATA.success === false) ||
                DATA.hasOwnProperty("content") === false
            ) {
                throw new Error(DATA.message);
            }
            toast(DATA.message, "success");

            await TEXT_EDITOR_HTML.setValue(DATA.content);
        } catch (error) {
            // Display error message
            toast(error.message, "error");
            console.log(error);
        } finally {
            LOADER.hide();
        }
    }
})(TEXT_EDITOR_HTML, LOADER);
