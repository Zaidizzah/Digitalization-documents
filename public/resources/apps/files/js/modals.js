const uploadQueue = new UploadQueueManager({
    csrf_token: CSRF_TOKEN,
    uploadUrl: "/documents/files/upload",
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
                var list = response.fileSuccessMetadata.join("");

                // check if list is empty before inserting
                const noFileAvailable =
                    document.querySelector(".no-file-available");

                if (noFileAvailable) {
                    noFileAvailable.remove();
                }

                fileList.insertAdjacentHTML("afterbegin", list);
            }
        }
    }

    var page = $('#current_page').val();
    var max_page = $('#last_page').val();
    var scrolling = false;
    var scroll_diff_before = 0;
    $("#file-container").scroll(function () {
        var container = $(this);
        var scroll_diff = container.scrollTop() + container.innerHeight();
        var scroll_area = container[0].scrollHeight;

        if(scroll_diff > scroll_diff_before){
            scroll_area = container[0].scrollHeight - 50;
        }
        scroll_diff_before = scroll_diff;
        
        if (scroll_diff >= scroll_area && !scrolling ) {
            if(page < max_page){
                page++;
                loadMoreFiles();
                scrolling = true;
            }
        }
    });

    function loadMoreFiles() {
        $('#loading_file').removeClass('d-none');
        $.ajax({
            url: "?page=" + page,
            type: "get",
            success: function (data) {
                if (!data) {
                    $(window).off("scroll"); // Hentikan scroll jika tidak ada data
                } else {
                    $("#file-list").append(data.files);
                }
                scrolling = false;
                $('#loading_file').addClass('d-none');
            },
            error: function (data) {
                page--
            },
        });
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
                },
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

    if(uploadZone && fileInput){
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
        const btnCollectingData = formCollectingData.querySelector(
            "button[type=submit]"
        );

        fileList.addEventListener("change", function (event) {
            const element = event.target;

            if (
                element.classList.contains("cbx-file-id") &&
                element.tagName === "INPUT" &&
                element.type === "checkbox" &&
                element.closest("#file-list")
            ) {
                const checkboxList = fileList.querySelectorAll(
                    ".cbx-file-id:checked"
                );

                if (checkboxList.length > 0) {
                    btnCollectingData.disabled = false;
                } else {
                    btnCollectingData.disabled = true;
                }
            }
        });
    }

    $('#delete_option').on('show.bs.modal', function(e){
        var button = $(e.relatedTarget);
        var keep_btn = $(this).find('#keep-btn');
        var erase_btn = $(this).find('#erase-btn');
    
        var link_keep = `${keep_btn.data('url')}?file=${button.data('file')}`
        var link_erase = `${erase_btn.data('url')}?file=${button.data('file')}`
    
        keep_btn.attr('href', link_keep)
        erase_btn.attr('href', link_erase)
    })
})();
