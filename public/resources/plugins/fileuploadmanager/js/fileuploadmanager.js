// File Upload Queue Manager
class UploadQueueManager {
    /**
     * Initializes a new instance of the UploadQueueManager class.
     * Sets up the initial state for the upload queue, including an empty queue,
     * upload status, and counters for total and uploaded files.
     * Also, creates and inserts the upload progress modal into the DOM.
     */
    constructor({ uploadUrl }) {
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
     * Adds files to the upload queue and starts uploading if the queue wasn't
     * already being processed.
     * @param {File[]} files - The files to add to the upload queue.
     * @returns {Promise<void>} When the files have been added to the queue.
     */
    async addToQueue(files) {
        this.queue.push(...files);
        this.totalFiles = this.queue.length;

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
                // Initialize LOADER
                LOADER.show(true, "bottom-left");

                const response = await this.uploadFile(this.uploadUrl, file);
                this.uploadedFiles++;

                // show toast notification on success
                toast(
                    response.message,
                    response.success ? "success" : "error",
                    true
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
                toast(error.message, "error", true);

                // Handle error and add to failed uploads list
                fileErrorsMetadata.unshift({
                    name: file.name,
                    error: error.message,
                });
                this.uploadedFiles++;

                console.error(error);
            } finally {
                // Hide current loader
                LOADER.hide();
            }
        }

        // Upload complete
        this.isUploading = false;
        this.totalFiles = 0;
        this.uploadedFiles = 0;

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
                "X-CSRF-TOKEN": CSRF_TOKEN,
                "XSRF-TOKEN": XSRF_TOKEN,
            },
            credentials: "include",
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
