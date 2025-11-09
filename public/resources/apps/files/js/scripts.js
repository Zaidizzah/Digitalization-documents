const uploadQueue = new UploadQueueManager({
    uploadUrl: `${location.origin}/api/documents/files/upload`,
});

(() => {
    "use strict";

    const fileList = document.querySelector("#file-list"),
        fileListContainer = document.querySelector("#file-list-container"),
        folderList = document.querySelector("#folder-list"),
        renameUrl = "/documents/files/rename";

    /**
     * Handles the uploaded files by validating and adding them to the upload queue.
     * Displays errors for invalid files and updates the file list for valid files.
     *
     * @param {FileList} uploadedFiles - The list of files to be processed.
     * @returns {Promise<void>} A promise that resolves when valid files are added to the queue.
     */
    async function handleFiles(uploadedFiles) {
        const validFiles = [];

        Array.from(uploadedFiles).forEach((file) => {
            const errors = uploadQueue.validateFile(file);
            if (errors.length > 0) {
                errors.forEach((error) => toast(error, "error"));
            } else {
                validFiles.push(file);
            }
        });

        if (validFiles.length > 0) {
            const response = await uploadQueue.addToQueue(validFiles);

            if (response.fileSuccessMetadata.length > 0) {
                const list = response.fileSuccessMetadata.join("");

                // check if list is empty before inserting
                const noFileAvailable =
                    document.querySelector(".no-file-available");

                if (noFileAvailable) {
                    noFileAvailable.remove();
                }

                // Check if selected counter element exists
                const selectedCounter =
                    fileList.querySelector("#selected-counter");
                if (selectedCounter) {
                    selectedCounter.insertAdjacentHTML("afterend", list);
                } else {
                    fileList.insertAdjacentHTML("afterbegin", list);
                }
            }
        }
    }

    /**
     * Handles the scroll event for both files and folders.
     * Loads more data (files or folders) when the user scrolls to the bottom.
     */
    let listConfigs = {
        file: {},
        folder: {},
    };
    const scrolling = {
            file: false,
            folder: false,
        },
        scrollDiffBefore = {
            file: 0,
            folder: 0,
        };

    // Check if both #folder-list and #file-list-container elements are exist, then assign file and folder properties object to listConfigs variable
    if (document.querySelector("#file-list-container")) {
        listConfigs.file.container = document.querySelector(
            "#file-list-container"
        );
        listConfigs.file.list = document.querySelector("#file-list");
        listConfigs.file.currentPage = fileList.hasAttribute("current-page")
            ? parseInt(fileList.dataset.currentPage, 10)
            : null;
        listConfigs.file.lastPage = parseInt(fileList.dataset.lastPage, 10);
    }
    if (document.querySelector("#folder-list")) {
        listConfigs.folder.container = document.querySelector("#folder-list");
        listConfigs.folder.currentPage = fileList.hasAttribute("current-page")
            ? parseInt(fileList.dataset.currentPage, 10)
            : null;
        listConfigs.folder.lastPage = parseInt(fileList.dataset.lastPage, 10);
    }

    Object.keys(listConfigs).forEach((type) => {
        const config = listConfigs[type];

        config.container?.addEventListener("scroll", function () {
            let scrollArea = this.scrollHeight,
                scrollDiff = this.scrollTop + this.clientHeight;

            if (scrollDiff > scrollDiffBefore[type]) {
                scrollArea = this.scrollHeight - 50;
            }
            scrollDiffBefore[type] = scrollDiff;

            if (scrollDiff >= scrollArea && !scrolling[type]) {
                if (config.currentPage < config.lastPage) {
                    // Assign new page
                    loadMoreData(type);
                    scrolling[type] = true;
                } else {
                    // check if current page is equal than last page then remove the listener
                    this.removeEventListener("scroll", loadMoreData);
                }
            }
        });
    });

    async function loadMoreData(type) {
        const config = listConfigs[type];

        // Assign 1 page to page variable
        config.currentPage++;

        // declared url variable
        let url;
        if (type === "file") {
            // URL to load more data with 'api' prefix
            url = `${location.origin}/api${location.pathname}?page=${config.currentPage}`;

            // check if queryURL contain value: 'action=attach'
            const action = new URLSearchParams(window.location.search).get(
                "action"
            );
            if (action) url += `&action=${action}`;
        } else {
            url = `${location.origin}/api/documents/folders?page=${config.currentPage}`;
        }

        try {
            // Initialize LOADER
            LOADER.show(true, type === "file" ? "bottom-right" : "bottom-left");

            const response = await fetch(url, {
                method: "get",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "XSRF-TOKEN": XSRF_TOKEN,
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
                credentials: "include",
            });

            if (!response.ok) {
                throw new Error("Failed to load more data. Please try again.");
            }

            const data = await response.json();

            if (data.hasOwnProperty("success") && data.success !== true) {
                throw new Error(data.message);
            }

            // insert data to list if available
            if (type === "file" && data.hasOwnProperty(`${type}s`)) {
                config.list.insertAdjacentHTML("beforeend", data[`${type}s`]);
            }
            if (type === "folder" && data.data.hasOwnProperty(`${type}s`)) {
                config.container.insertAdjacentHTML(
                    "beforeend",
                    data[`${type}s`]
                );
            }

            scrolling[type] = false;
        } catch (error) {
            console.error(error);

            config.currentPage--;
        } finally {
            // Hide current LOADER
            LOADER.hide();
        }
    }

    // Upload zone functionality
    const uploadZone = document.getElementById("upload-zone");
    const fileInput = document.getElementById("file-input");

    if (uploadZone && fileInput) {
        uploadZone.addEventListener("click", () => fileInput.click());

        uploadZone.addEventListener("dragover", (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = "var(--primary-color)";
            uploadZone.style.backgroundColor = "rgba(13, 110, 253, 0.05)";
        });

        uploadZone.addEventListener("dragleave", (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = "#dee2e6";
            uploadZone.style.backgroundColor = "transparent";
        });

        uploadZone.addEventListener("drop", (e) => {
            e.preventDefault();
            uploadZone.style.borderColor = "#dee2e6";
            uploadZone.style.backgroundColor = "transparent";
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener("change", (e) => {
            handleFiles(e.target.files);

            // Reset file input
            fileInput.value = "";
        });
    }

    // files action
    $("#modal-files").on("shown.bs.modal", (event) => {
        const button = $(event.relatedTarget);

        $("#modal-files-label").html(
            `File: ${button.data("file-name")}.${button.data(
                "file-extension"
            )} info`
        );
        $("#file-name").html(
            button.data("file-name") + "." + button.data("file-extension")
        );
        const labeled = button.data("file-document-name");
        $("#file-labeled").html(
            labeled !== "" && button.data("file-document-long-name")
                ? `<abbr title="${button.data(
                      "file-document-long-name"
                  )}">${labeled}</abbr>`
                : labeled
        );
        $("#file-size").html(button.data("file-size"));
        $("#file-uploaded-at").html(button.data("file-uploaded-at"));
        $("#file-modified-at").html(button.data("file-modified-at"));
    });

    $("#modal-files-edit").on("shown.bs.modal", (event) => {
        const button = $(event.relatedTarget);

        $("#modal-files-edit-label").html(
            `Edit file: ${button.data("file-name")}.${button.data(
                "file-extension"
            )}`
        );

        $('input[name="name"]').val(button.data("file-name"));
        $('select[name="document_type_id"]').val(
            button.data("file-document-id")
        );

        // change the action of the form
        const form = document.querySelector("#modal-files-edit form");
        form.action = `${renameUrl}?file=${button.data("file-id")}`;
    });

    const formCollectingData = document.querySelector(
        "#tile-file-list form#form-collecting-files-data"
    );

    if (formCollectingData) {
        const MAX_SELECTED_FILE_TO_INSERTING = 15;
        const btnCollectingData = formCollectingData.querySelector(
            "button[type=submit]"
        );

        const selectedStatus = formCollectingData.querySelector(
            ".file-list .selected-counter span"
        );

        fileList.addEventListener("change", function (event) {
            const element = event.target;

            if (
                element.classList.contains("cbx-file") &&
                element.tagName === "INPUT" &&
                element.type === "checkbox" &&
                element.closest("#file-list")
            ) {
                const checkboxCheckedList =
                    fileList.querySelectorAll(".cbx-file:checked");
                const checkboxUnCheckedList = fileList.querySelectorAll(
                    ".cbx-file:not(:checked)"
                );

                const updateSelectedStatus = (numberOfElements) =>
                    (selectedStatus.textContent = `Selected file ${numberOfElements} out of 15 (maximum for inserting data)`);

                // Disable all checkbox if selected file is reached 15
                if (
                    checkboxCheckedList.length ===
                    MAX_SELECTED_FILE_TO_INSERTING
                ) {
                    checkboxUnCheckedList.forEach((element) => {
                        element.disabled = true;
                    });
                } else {
                    checkboxUnCheckedList.forEach((element) => {
                        element.disabled = false;
                    });
                }

                if (checkboxCheckedList.length > 0) {
                    btnCollectingData.disabled = false;

                    updateSelectedStatus(checkboxCheckedList.length);
                } else {
                    btnCollectingData.disabled = true;

                    updateSelectedStatus(checkboxCheckedList.length);
                }
            }
        });
    }

    // Delete option modal
    document
        .querySelector("#delete-option")
        ?.addEventListener("show.bs.modal", function (e) {
            const button = e.relatedTarget;

            const formDeleteErase = document.querySelector(
                "form.form-delete-erase"
            );
            const formDeleteKeep = document.querySelector(
                "form.form-delete-keep"
            );

            if (formDeleteErase) {
                formDeleteErase.addEventListener("submit", (event) => {
                    event.preventDefault();

                    // Adding input field hidden to encrypted file name
                    formDeleteErase.insertAdjacentHTML(
                        "beforeend",
                        `<input type="hidden" name="file" value="${button.dataset.fileEncryption}">`
                    );

                    const name = `${button.dataset.fileName}.${button.dataset.fileExtension}`;

                    const confirmation = confirm(
                        `Are you sure you want to delete file '${name}' and erase all data that attached to this file?`
                    );

                    if (confirmation) {
                        formDeleteErase.submit();
                    }
                });
            }

            if (formDeleteKeep) {
                formDeleteKeep.addEventListener("submit", (event) => {
                    event.preventDefault();

                    // Adding input field hidden to encrypted file name
                    formDeleteKeep.insertAdjacentHTML(
                        "beforeend",
                        `<input type="hidden" name="file" value="${button.dataset.fileEncryption}">`
                    );

                    const name = `${button.dataset.fileName}.${button.dataset.fileExtension}`;

                    const confirmation = confirm(
                        `Are you sure you want to delete file '${name}' but keep all data that attached to this file?`
                    );

                    if (confirmation) {
                        formDeleteKeep.submit();
                    }
                });
            }
        });

    const formDeleteFleRoot = document.querySelectorAll(
        "form.form-delete-file-root"
    );
    if (formDeleteFleRoot) {
        formDeleteFleRoot.forEach((form) => {
            form.addEventListener("submit", (event) => {
                event.preventDefault();

                const name = `${form.dataset.fileName}.${form.dataset.fileExtension}`;

                const confirmation = confirm(
                    `Are you sure you want to delete file '${name}'?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }
})();
