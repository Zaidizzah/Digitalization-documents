const uploadQueue = new UploadQueueManager({
    uploadUrl: "api/documents/files/upload",
});

(() => {
    "use strict";

    const fileList = document.getElementById("file-list"),
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
     * Handles the scroll event on the file list container.
     * Loads more files when the user scrolls to the bottom of the container.
     */
    let scrolling = false,
        scrollDiffBefore = 0,
        page = parseInt(fileList.dataset.currentPage, 10),
        maxPage = parseInt(fileList.dataset.lastPage, 10);

    $("#file-list-container").scroll(function () {
        const container = $(this);

        let scrollArea = container[0].scrollHeight,
            scrollDiff = container.scrollTop() + container.innerHeight();

        if (scrollDiff > scrollDiffBefore) {
            scrollArea = container[0].scrollHeight - 50;
        }
        scrollDiffBefore = scrollDiff;

        if (scrollDiff >= scrollArea && !scrolling) {
            if (page < maxPage) {
                // Assign new page
                page = loadMoreFiles(page);
                scrolling = true;
            }
        }
    });

    function loadMoreFiles(page) {
        // Assign 1 page to page variable
        page++;

        const loadingFilesElement = document.querySelector("#loading-files");
        loadingFilesElement.classList.remove("d-none");

        // URL to load more files with 'api' preffix
        let url = `${location.origin}/api${location.pathname}?page=${page}`;

        // check if queryURL contain value: 'action=attach'
        const action = new URLSearchParams(window.location.search).get(
            "action"
        );
        if (action) url += `&action=${action}`;

        $.ajax({
            url: url,
            type: "get",
            xhrFields: { withCredentials: true },
            success: function (data) {
                if (!data) {
                    $(window).off("scroll");
                } else {
                    fileList.insertAdjacentHTML("beforeend", data.files);
                }
                scrolling = false;
                loadingFilesElement.classList.add("d-none");
            },
            error: () => {
                return page--;
            },
        });

        return page;
    }

    const paginationFileWarapper = document.querySelector(
        ".pagination-file-wrapper"
    );

    if (
        paginationFileWarapper &&
        paginationFileWarapper.querySelectorAll("a").length > 0
    ) {
        // delegation event for clicking paginasi link
        document.addEventListener("click", async function (event) {
            const element = event.target;

            if (
                element.classList.contains("page-link") &&
                element.tagName === "A" &&
                element.closest(".pagination-file-wrapper")
            ) {
                event.preventDefault();

                const url = element.getAttribute("href");

                // Fetching the data
                const response = await getPaginateData(url);

                // set new html structure to file list container
                fileList.innerHTML = response.files;

                // change the displayed links to new element
                paginationFileWarapper.innerHTML = response.links;
            }
        });

        /**
         * Fetches paginated data from the specified URL and returns it as JSON.
         *
         * @param {string} url - The URL to fetch data from.
         * @param {string} [type="files"] - The type of data being fetched, used for error messages.
         * @throws Will throw an error if the response is not ok.
         * @returns {Promise<Object>} The JSON data from the response.
         */
        async function getPaginateData(url, type = "files") {
            LOADER.show();
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-type": "application/json",
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                    "XSRF-TOKEN": XSRF_TOKEN,
                },
                credentials: "include",
            })
                .catch((error) => {
                    console.log(error);

                    toast(
                        `Failed to getting more ${type} data. Please try again.`
                    );
                })
                .finally(() => LOADER.hide());

            if (!response.ok) {
                throw new Error(
                    `Failed to getting more ${type} data. Please try again.`
                );
            }

            return await response.json();
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
