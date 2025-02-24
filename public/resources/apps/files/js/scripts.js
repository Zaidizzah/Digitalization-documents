(() => {
    "use strict";

    const fileList = document.getElementById("file-list");
    const uploadQueue = new UploadQueueManager({
        csrf_token: CSRF_TOKEN,
        uploadUrl: "/documents/files/upload",
    });

    let files = [
        {
            id: 1,
            name: "file-1",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
        {
            id: 2,
            name: "file-2",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
        {
            id: 3,
            name: "file-3",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
        {
            id: 4,
            name: "file-4",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
        {
            id: 5,
            name: "file-5",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
        {
            id: 6,
            name: "file-6",
            extension: "jpg",
            size: 1024,
            preview_uri: "/images/placeholder.jpg",
            uploaded_at: "January 1, 2022",
            modified_at: "January 1, 2022",
        },
    ];

    renderFiles(files);

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

            console.log(response);

            // renderFiles(fileSuccessMetadata);
        }
    }

    // Upload zone functionality
    const uploadZone = document.getElementById("upload-zone");
    const fileInput = document.getElementById("file-input");

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

    function renderFiles(files) {
        fileList.innerHTML = files
            .map(
                (file) => `
                <div class="file-item uploaded-file-item p-3" data-file-id="${
                    file.id
                }" data-file-name="${file.name}" data-file-extension="${
                    file.extension
                }" data-file-size="${
                    file.size
                }" data-file-preview-uri="${addSlashes(
                    file.preview_uri
                )}" data-file-uploaded-at="${
                    file.uploaded_at
                }" data-file-modified-at="${
                    file.modified_at
                }" aria-label="File ${file.name}" title="File ${file.name}.${
                    file.extension
                }">
                    <button type="button" role="button" class="file-browse btn btn-outline-success btn-sm" aria-label="Browse file ${
                        file.name
                    }.${file.extension}" title="Browse file ${file.name}.${
                    file.extension
                }" popovertarget="#file-info-dialog-${
                    file.id
                }"><i class="bi bi-search fs-5"></i></button>
                    <div class="d-flex align-items-center">
                        <div class="file-icon me-3">
                            <i class="bi bi-files text-primary"></i>
                        </div>
                        <div class="file-info" aria-label="File info" title="Size: ${
                            file.size
                        } -- Uploaded at ${file.date}">
                            <div class="fw-semibold"><span>${file.name}.${
                    file.extension
                }</span></div>
                            <div class="small text-muted">
                                <span>${file.size} -- Uploaded on ${
                    file.date
                }.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popover dialog for ${file.name} -->
                <dialog class="dialog-wrapper" role="tooltip" popover id="file-info-dialog-${
                    file.id
                }" aria-label="File ${file.name} info" title="File ${
                    file.name
                } info">
                    <span class="position-absolute p-2 top-0 start-50 translate-middle badge rounded border border-2 border-dark bg-white shadow-sm text-dark" aria-hidden="true">File: ${
                        file.name
                    } info.</span>
                    <div class="dialog-section pt-2" aria-labelledby="dialog-label-${
                        file.id
                    }">
                        <h3 class="visually-hidden" id="#dialog-label-${
                            file.id
                        }" aria-hidden="true">File: ${file.name} info.</h3>

                        <div class="dialog-content">
                            <div class="dialog-content-metadata">
                                <div class="meta-item">
                                    <span class="meta-label">Name:</span>
                                    <span class="meta-value">${file.name}.${
                    file.extension
                }</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Labeled:</span>
                                    <span class="meta-value"><abbr title="kartu keluarga">kk</abbr></span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Size:</span>
                                    <span class="meta-value">${file.size}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Uploaded at:</span>
                                    <span class="meta-value">${
                                        file.uploaded_at
                                    }</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Modified at:</span>
                                    <span class="meta-value">${
                                        file.modified_at
                                    }</span>
                                </div>
                            </div>

                            <div class="dialog-content-actions">
                                <button type="button" role="button" class="btn btn-primary btn-sm" aria-label="Download file ${
                                    file.name
                                }" title="Button: to download file ${
                    file.name
                }"><i class="bi bi-download fs-5"></i> Download</button>
                                <button type="button" role="button" class="btn btn-danger btn-sm" aria-label="Delete file ${
                                    file.name
                                }" title="Button: to delete file ${
                    file.name
                }"><i class="bi bi-trash fs-5"></i> Delete</button>
                                <button type="button" role="button" class="btn btn-info btn-sm" aria-label="Preview file ${
                                    file.name
                                }" title="Button: to preview file ${
                    file.name
                }"><i class="bi bi-eye fs-5"></i> Preview</button>
                                <button type="submit" role="button" class="btn btn-primary btn-sm" aria-label="Save changes for file ${
                                    file.name
                                }" title="Button: to save changes for file ${
                    file.name
                }"><i class="bi bi-save fs-5"></i> Save</button>
                            </div>
                        </div>
                    </div>
                </dialog>
            `
            )
            .join("");

        // event listener for delete file from uploaded files
        const fileRemoveButtons = fileList.querySelectorAll(".file-remove");
        fileRemoveButtons.forEach((button) => {
            button.addEventListener("click", () => {
                if (!confirm("Are you sure you want to delete this file?")) {
                    return;
                }

                const fileId =
                    button.parentElement.getAttribute("data-file-id");
                deleteFile(fileId);
            });
        });
    }

    // popover handler initialization
    const listPopover = document.querySelectorAll("[popovertarget]");
    if (listPopover) {
        listPopover.forEach((popover) => {
            const popoverTarget = document.querySelector(
                `${popover.getAttribute("popovertarget")}[popover]`
            );

            if (popoverTarget) {
                popover.addEventListener("click", function (e) {
                    popoverTarget.togglePopover();
                });

                popoverTarget.addEventListener("beforetoggle", function (e) {
                    if (e.newState === "open") {
                        popover.parentElement.setAttribute(
                            "aria-expanded",
                            "true"
                        );
                        popover.parentElement.classList.add("active");
                    } else {
                        popover.parentElement.setAttribute(
                            "aria-expanded",
                            "false"
                        );
                        popover.parentElement.classList.remove("active");
                    }
                });
            }
        });
    }
})();
