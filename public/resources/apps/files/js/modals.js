(() => {
    "use strict";

    const fileList = document.getElementById("file-list");
    const uploadQueue = new UploadQueueManager({
        csrf_token: CSRF_TOKEN,
        uploadUrl: "/documents/files/upload",
    });

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
                renderFiles(response.fileSuccessMetadata);
            }

            if (response.fileErrorsMetadata.length > 0) {
            }
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
        let list = files
            .map(
                (file) => `
            <div class="file-item uploaded-file-item p-3" aria-label="File ${file.name}.${file.extension}" title="File ${file.name}.${file.extension}">
                <div class="file-info-wrapper d-flex align-items-center">
                    <div class="file-info" aria-label="Info for file ${file.name}.${file.extension}">
                        <div class="fw-semibold"><span>${file.name}.${file.extension}</span></div>
                        <div class="small text-muted">
                            <span>${file.size} - Uploaded on ${file.uploaded_at}.</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="file-browse btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Actions button for file ${file.name}.${file.extension}" title="Actions button for file ${file.name}.${file.extension}">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)"
                                aria-label="Browse file ${file.name}.${file.extension}" title="Button: to browse file ${file.name}.${file.extension}" 
                                data-bs-toggle="modal" data-bs-target="#modal_files"
                                data-file-id="${file.id}" data-file-name="${file.name}" data-file-extension="${file.extension}" 
                                data-file-size="${file.size}"
                                data-file-uploaded-at="${file.uploaded_at}" data-file-modified-at="${file.modified_at}" 
                                data-file-labeled="kk" data-file-abbr="kartu kaluarga"
                                aria-label="File ${file.name}" title="File ${file.name}.${file.extension}">
                                <i class="bi bi-search fs-5"></i>Info
                            </a>
                        </li>
                        <li>
                            <a href="${file.preview_uri}" role="button" class="dropdown-item" aria-label="Preview file ${file.name}" title="Button: to preview file ${file.name}"><i class="bi bi-eye fs-5"></i> Preview</a>
                        </li>
                        <li>
                            <a href="${file.download_uri}" role="button" class="dropdown-item" aria-label="Download file ${file.name}" title="Button: to download file ${file.name}"><i class="bi bi-download fs-5"></i> Download</a>
                        </li>
                        <li>    
                            <a href="javascript:void(0)" role="button" class="dropdown-item" aria-label="Edit file ${file.name}" title="Button: to edit file ${file.name}" data-file-id="${file.id}" data-file-name="${file.name}" data-file-extension="${file.extension}" data-file-document-id="${file.document_type_id}"><i class="bi bi-pencil-square fs-5"></i> Edit</a>
                        </li>
                        <li>
                            <a href="${file.delete_uri}" role="button" class="dropdown-item" aria-label="Delete file ${file.name}" title="Button: to delete file ${file.name}" onclick="return confirm('Are you sure to delete this file?')"><i class="bi bi-trash fs-5"></i> Delete</a>
                        </li>
                    </ul>
                </div>
            </div>
        `
            )
            .join("");
        fileList.insertAdjacentHTML("afterbegin", list);
    }

    // files action
    $("#modal-files").on("shown.bs.modal", (event) => {
        let button = $(event.relatedTarget);

        $("#modal-files-label").html(
            `File: ${button.data("file-name")}.${button.data(
                "file-extension"
            )} info`
        );
        $("#file-name").html(
            button.data("file-name") + "." + button.data("file-extension")
        );
        $("#file-labeled").html(
            `<abbr title="${button.data("file-abbr")}">${button.data(
                "file-labeled"
            )}</abbr>`
        );
        $("#file-size").html(button.data("file-size"));
        $("#file-uploaded-at").html(button.data("file-uploaded-at"));
        $("#file-modified-at").html(button.data("file-modified-at"));
    });

    $("#modal-files-edit").on("shown.bs.modal", (event) => {
        let button = $(event.relatedTarget);

        $("#modal-files-edit-label").html(
            `Edit file: ${button.data("file-name")}.${button.data(
                "file-extension"
            )}`
        );

        $('input[name="name"]').val(button.data("file-name"));
        $('input[name="id"]').val(button.data("file-id"));
        $('select[name="document_type_id"]').val(
            button.data("file-document-id")
        );
    });
})();
