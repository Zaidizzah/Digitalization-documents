// File Upload Queue Manager
class UploadQueueManager {
    /**
     * Initializes a new instance of the UploadQueueManager class.
     * Sets up the initial state for the upload queue, including an empty queue,
     * upload status, and counters for total and uploaded files.
     * Also, creates and inserts the upload progress modal into the DOM.
     */
    constructor({ csrf_token, uploadUrl }) {
        this.csrf_token = csrf_token;
        this.uploadUrl = uploadUrl;
        this.queue = [];
        this.isUploading = false;
        this.totalFiles = 0;
        this.uploadedFiles = 0;
        this.allowedFileTypes = [
            "image/jpeg",
            "image/png",
            "image/jpg",
            "image/webp",
            "application/pdf",
        ];
        this.maxFileSize = 20 * 1024 * 1024;
        this.createModal();
    }

    /**
     * Creates and inserts an upload progress modal into the DOM.
     * The modal displays a spinner, the number of files uploaded out of the total,
     * and a progress bar reflecting the upload progress.
     * Initializes references to modal elements for later use.
     */
    createModal() {
        const modalHtml = `
            <div class="modal fade" id="upload-progress-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="upload-progress-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center p-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Processing...</span>
                            </div>
                            <h5 class="modal-title mb-3" id="upload-progress-modal-label">Uploading Files</h5>
                            <p class="mb-2">Uploaded <span id="uploaded-count">0</span> out of <span id="total-count">0</span> files</p>
                            <div class="progress">
                                <div id="upload-progress" class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

        document
            .querySelector("main.app-content")
            .insertAdjacentHTML("beforeend", modalHtml);
        this.modal = new bootstrap.Modal(
            document.querySelector(".modal#upload-progress-modal")
        );
        this.progressBar = document.querySelector(
            ".modal#upload-progress-modal #upload-progress"
        );
        this.uploadedCountEl = document.querySelector(
            ".modal#upload-progress-modal #uploaded-count"
        );
        this.totalCountEl = document.querySelector(
            ".modal#upload-progress-modal #total-count"
        );
    }

    /**
     * Validates a file object by checking its extension and size.
     * @param {File} file - The file object to validate.
     * @returns {Array<string>} Errors found when validating the file.
     */
    validateFile(file) {
        const errors = [];

        if (!this.allowedFileTypes.includes(file.type)) {
            errors.push(
                `File: "${file.name}" type "${
                    file.type
                }" is not allowed. Allowed types: ${this.allowedFileTypes.join(
                    ", "
                )}.`
            );
        }

        if (file.size > this.maxFileSize) {
            errors.push(
                `File: "${file.name}" size exceeds ${
                    this.maxFileSize / (1024 * 1024)
                }MB limit.`
            );
        }

        return errors;
    }

    /**
     * Updates the progress bar and displayed counts of uploaded and total files
     * in the modal.
     */
    updateProgress() {
        const progress = (this.uploadedFiles / this.totalFiles) * 100;
        this.progressBar.style.width = `${progress}%`;
        this.progressBar.setAttribute("aria-valuenow", progress);
        this.uploadedCountEl.textContent = this.uploadedFiles;
        this.totalCountEl.textContent = this.totalFiles;
    }

    /**
     * Adds files to the upload queue and starts uploading if the queue wasn't
     * already being processed.
     * @param {File[]} files - The files to add to the upload queue.
     * @returns {Promise<void>} When the files have been added to the queue.
     */
    async addToQueue(files) {
        this.queue.push(...files);
        this.totalFiles = this.queue.length;
        this.updateProgress();
        this.modal.show();

        if (!this.isUploading) {
            this.isUploading = true;

            const response = await this.processQueue();

            return response;
        }
    }

    /**
     * Processes the file upload queue.
     * @returns {Promise<void>} When all files have been uploaded.
     * @private
     */
    async processQueue() {
        const fileSuccessMetadata = [];
        const fileErrorsMetadata = [];

        while (this.queue.length > 0) {
            const file = this.queue.shift();
            try {
                const response = await this.uploadFile(this.uploadUrl, file);
                this.uploadedFiles++;
                this.updateProgress();

                // show toast notification on success
                toast(
                    response.message,
                    response.success ? "success" : "error",
                    response.success === false ? false : true
                );

                if (response.success) {
                    fileSuccessMetadata.unshift(response.files);
                } else {
                    // Handle error and add to failed uploads list
                    fileErrorsMetadata.unshift({
                        name: file.name,
                        error: response.message,
                    });
                }
            } catch (error) {
                // show toast notification on error
                toast(error.message, "error", false);

                // Handle error and add to failed uploads list
                fileErrorsMetadata.unshift({
                    name: file.name,
                    error: error.message,
                });
                this.uploadedFiles++;
                this.updateProgress();

                console.error(error);
            }
        }

        // Upload complete
        this.isUploading = false;
        this.totalFiles = 0;
        this.uploadedFiles = 0;
        setTimeout(() => {
            this.modal.hide();
        }, 1000);

        return { fileSuccessMetadata, fileErrorsMetadata };
    }

    /**
     * Uploads a file to the server.
     * @param {string} url - The URL to upload the file to.
     * @param {File} file - The file to be uploaded.
     * @returns {Promise<Object>} The response from the server as a JSON object.
     * @throws {Error} If there is an error."
     */
    async uploadFile(url, file) {
        // create object form data for file uploading
        const formData = new FormData();
        formData.append("file", file, file.name);

        const response = await fetch(url, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": this.csrf_token,
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error(
                `Failed to upload file: ${file.name}. Please try again.`
            );
        }

        return await response.json();
    }
}
