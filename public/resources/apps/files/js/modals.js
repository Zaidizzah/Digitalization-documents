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
                var list = response.fileSuccessMetadata.join(''); 
                fileList.insertAdjacentHTML("afterbegin", list);
            }
        }
    }

    var page = 1;
    var scrolling = false;
    $('#file-list').scroll(function() {
        var container = $(this);
        if ((container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 50) && !scrolling) {
            loadMoreFiles();
            scrolling = true;
        }
    });

    function loadMoreFiles() {
        page++;
        // LOADER.show()
        $.ajax({
            url: "?page=" + page,
            type: "get",
            success: function(data) {
                console.log(data.trim() === "");
                if (data.trim() === "") {
                    $(window).off("scroll"); // Hentikan scroll jika tidak ada data lagi
                } else {
                    $("#file-list").append(data);
                }
                scrolling = false;
                // LOADER.hide();
            }
        });
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
