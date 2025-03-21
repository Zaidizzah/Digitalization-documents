(async () => {
    "use strict";

    let OCR_SPACE_API_KEYS = null;

    // Fetching data API KEY from storage server
    LOADER.show();
    fetch("/apikey.json", {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
        },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    "Failed to load evironment resource. Please reload this page and try again later."
                );
            }

            return response.json();
        })
        .then(({ OCR_SPACE_API_KEY, OCR_SPACE_SPARE_API_KEY }) => {
            // Set API key
            OCR_SPACE_API_KEYS = {
                OCR_SPACE_API_KEY: removeCharacter(
                    OCR_SPACE_API_KEY,
                    "-RajshIks"
                ),
                OCR_SPACE_SPARE_API_KEY: removeCharacter(
                    OCR_SPACE_SPARE_API_KEY,
                    "-234567890"
                ),
            };
        })
        .catch((error) => {
            // Display error
            toast(error.message, "error");

            console.error(error);
        })
        .finally(() => LOADER.hide());

    // worker for pdf js
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.worker.min.js";

    const formInsertDocumentType = document.getElementById(
            "form-insert-document-type"
        ),
        action = formInsertDocumentType.getAttribute("data-action"),
        recognizeUrlServer =
            formInsertDocumentType.getAttribute("data-recognize-url"),
        btnCopyExtractedResult = document.querySelectorAll(
            ".btn-copy-ex-result"
        );

    if (btnCopyExtractedResult) {
        btnCopyExtractedResult.forEach((element) => {
            element.addEventListener("click", function (event) {
                const extractedResultElement = element
                    .closest(".extraction-container")
                    .querySelector(".ex-b-result");

                // Copy the text to user
                navigator.clipboard.writeText(
                    extractedResultElement.textContent.trim()
                );

                element.textContent = "Copied";
                setTimeout(() => {
                    element.textContent = "Copy to clipboard";
                }, 1000);
            });
        });
    }

    const btnAddInputField = document.querySelectorAll(".btn-add-input-field");

    if (btnAddInputField) {
        btnAddInputField.forEach((element) => {
            element.addEventListener("click", function (event) {
                const tamplateInputFieldID =
                    element.getAttribute("data-template-id");
                const inputFieldsContainer = element.closest(
                    ".input-fields-container"
                );

                const numberOfFieldInputs =
                    inputFieldsContainer.querySelectorAll(
                        ".input-field-wrapper:not(.attached-file)"
                    ).length;

                const inputFieldTemplate = document
                    .getElementById(tamplateInputFieldID)
                    .content.cloneNode(true);

                // assign different ids to input field wrapper and the title
                inputFieldTemplate
                    .querySelector(".input-field-wrapper")
                    .setAttribute(
                        "id",
                        `input-field-${numberOfFieldInputs + 1}`
                    );
                inputFieldTemplate
                    .querySelector(".input-field-title")
                    .setAttribute(
                        "aria-labelledby",
                        `input-field-${numberOfFieldInputs + 1}`
                    );

                // change the field number in attribut aria-label and span.input-field-title
                inputFieldTemplate
                    .querySelector(".input-field-title")
                    .setAttribute(
                        "aria-label",
                        `Input field ${numberOfFieldInputs + 1}`
                    );
                inputFieldTemplate.querySelector(
                    ".input-field-title"
                ).textContent = `Input field ${numberOfFieldInputs + 1}`;

                // assign different ids to labels and related inputs (input, select, and textarea)
                inputFieldTemplate
                    .querySelectorAll("label")
                    .forEach((label) => {
                        label.setAttribute(
                            "for",
                            `${label.getAttribute("for")}-${
                                numberOfFieldInputs + 1
                            }`
                        );
                    });
                inputFieldTemplate
                    .querySelectorAll(
                        "input:not([type='hidden']), select, textarea"
                    )
                    .forEach((input) => {
                        input.id = `${input.id}-${numberOfFieldInputs + 1}`;
                    });

                // sets the attribute name of the 'data-name' attribute and removes the disabled attribute
                inputFieldTemplate
                    .querySelectorAll("[data-name]")
                    .forEach((input) => {
                        input.setAttribute(
                            "name",
                            `${input.getAttribute("data-name")}`
                        );
                        // remove attribute 'disabled' and 'data-name'
                        input.removeAttribute("data-name");
                        input.disabled = false;
                    });

                element.parentElement.before(inputFieldTemplate);
            });
        });
    }

    if (action === "create") {
        const inputFieldAtachedFile = document.querySelectorAll(
                ".input-field-wrapper.attached-file"
            ),
            divider = document.querySelectorAll(".divider"),
            inputFieldsContainer = document.querySelectorAll(
                ".input-fields-container"
            );

        if (inputFieldAtachedFile) {
            /**
             * Reads a file and returns its content as a Uint8Array.
             *
             * This function utilizes the FileReader API to read the file
             * as an ArrayBuffer and converts it to a Uint8Array.
             *
             * @param {File} file - The file to be read.
             * @returns {Promise<Uint8Array>} - A promise that resolves to the file content as a Uint8Array.
             */
            const readFile = (file) => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.addEventListener("loadend", (event) =>
                        resolve(new Uint8Array(event.target.result))
                    );
                    reader.readAsArrayBuffer(file);
                });
            };

            /**
             * Creates an HTML element to represent the result of extracting text from a file.
             *
             * @param {string} ocrResult - The extracted text from the file.
             * @param {string} fileName - The name of the file.
             * @returns {string} - The HTML element representing the result of extracting text from the file.
             */
            const createResultElement = (ocrResult, fileName) => {
                return `<div class="extraction-container" aria-label="Atachment file extraction container" aria-labelledby="extraction-container-label">
                    <div class="extraction-header">
                        <div class="ex-h-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                                <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                            </svg>
                        </div>
                        <h2 class="ex-h-title" id="extraction-container-label">Result of extraction document <span class="visually-hidden">file: ${fileName}</span></h2>
                    </div>
                    <div class="extraction-body">
                        <div class="ex-b-title">
                            <h5>File: ${fileName}</h5>
                        </div>
                        <div class="ex-b-result">
                            ${ocrResult}
                        </div>
                        <div class="ex-b-actions">
                            <button class="btn btn-secondary btn-copy-ex-result" type="button" role="button" title="Button: to copy extraction texts/result from file: ${fileName}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.5 2a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-12a.5.5 0 0 0-.5-.5H12a.5.5 0 0 1 0-1h.5A1.5 1.5 0 0 1 14 2.5v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-12A1.5 1.5 0 0 1 3.5 1H4a.5.5 0 0 1 0 1h-.5Z"/>
                                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Z"/>
                                </svg>
                                Copy to clipboard
                            </button>
                        </div>
                    </div>
                </div>`;
            };

            if (inputFieldsContainer) {
                inputFieldsContainer.forEach((element, index) => {
                    element.addEventListener("click", function (event) {
                        const btn = event.target.closest(
                            ".btn-delete-input-field"
                        );

                        if (btn) {
                            const inputFieldWrapper = event.target.closest(
                                ".input-field-wrapper"
                            );

                            // remove element from DOM
                            if (
                                inputFieldWrapper &&
                                confirm(
                                    "Are you sure to remove this input field?"
                                )
                            )
                                inputFieldWrapper.remove();
                        }
                    });
                });
            }

            inputFieldAtachedFile.forEach((element, index) => {
                // get all file inputs
                const fileInput = element.querySelector(
                        "input[type='file'].attached-file"
                    ),
                    cbxIgnoringFileInput = element.querySelector(
                        `#ignore-attached-file-${index + 1}`
                    ),
                    buttonStartOCRProcess = fileInput.nextElementSibling;

                if (
                    buttonStartOCRProcess.tagName === "BUTTON" &&
                    buttonStartOCRProcess.classList.contains(
                        "btn-ocr-content-file"
                    ) &&
                    cbxIgnoringFileInput
                ) {
                    cbxIgnoringFileInput.addEventListener(
                        "change",
                        function (event) {
                            const checked = cbxIgnoringFileInput.checked;

                            if (checked) {
                                // Set disable attribute of file input element to true
                                fileInput.disabled = true;
                            } else {
                                // Set disable attribute of file input element to true
                                fileInput.disabled = false;
                            }
                        }
                    );

                    buttonStartOCRProcess.addEventListener(
                        "click",
                        async function (event) {
                            // Prevent tha actions like (submit and redirectting)
                            event.preventDefault();

                            const file = fileInput.files[0];

                            // Validate file for maximum size limit is 20MB and image type is 'PNG', 'JPG', 'JPEG', 'WEBP', and 'PDF'
                            if (
                                file &&
                                (file.size > 20 * (1024 * 1024) ||
                                    ![
                                        "image/png",
                                        "image/jpeg",
                                        "image/jpg",
                                        "image/webp",
                                        "application/pdf",
                                    ].includes(file.type))
                            ) {
                                toast(
                                    "The file size is too large or the file type is not supported. Please choose a file with a size less than 20MB and a type of 'PNG', 'JPG', 'JPEG', 'WEBP', or 'PDF'.",
                                    "error",
                                    true
                                );
                            }

                            if (file) {
                                await preProcessRecognizing(
                                    await readFile(file),
                                    file,
                                    divider[index]
                                );
                            } else {
                                // Displaying toast message
                                toast("No file defined.", "info");
                            }
                        }
                    );

                    fileInput.addEventListener(
                        "change",
                        async function (event) {
                            const file = fileInput.files[0];

                            if (file) {
                                // Set enable/disable attribute to false to button element
                                buttonStartOCRProcess.disabled = false;

                                // set the file name in the divider
                                divider[
                                    index
                                ].textContent = `File: ${file.name}`;
                            } else {
                                // Set disable/disable attribute to true to button element
                                buttonStartOCRProcess.disabled = true;

                                // delete extraction container element and reset the textContent of related divider
                                divider[index].textContent =
                                    "File: Not Initialized";
                                formInsertDocumentType
                                    .querySelectorAll(".extraction-container")
                                    [index].remove();
                            }
                        }
                    );
                } else {
                    // Delete the element wrapper and related divider
                    element.remove();
                    divider[index].remove();

                    // Displayiny message toast
                    toast(
                        "There are some invalid or ill-defined element conditions. Please reload this page and try again."
                    );
                }
            });

            /**
             * Preprocesses an image input, either an HTMLImageElement or HTMLCanvasElement, by converting it to a grayscale
             * tensor, normalizing the pixel values, and then converting it back to image data to draw on a canvas.
             * The function ensures that the output is in a format suitable for further image processing tasks.
             *
             * @param {HTMLImageElement|HTMLCanvasElement} input - The image or canvas element to be preprocessed.
             * @returns {Promise<HTMLCanvasElement>} - A canvas element with the preprocessed image data.
             * @throws {Error} - Throws an error if the input is neither an HTMLImageElement nor an HTMLCanvasElement.
             */
            async function preProcessImage(input) {
                let canvas, ctx;

                // Check if input is an <img> or <canvas> element
                if (input instanceof HTMLImageElement) {
                    // If input is an <img>, create a new canvas
                    canvas = document.createElement("canvas");
                    canvas.width = input.width;
                    canvas.height = input.height;
                    ctx = canvas.getContext("2d");
                    ctx.drawImage(input, 0, 0, input.width, input.height);
                } else if (input instanceof HTMLCanvasElement) {
                    // If input is already a canvas
                    canvas = input;
                    ctx = canvas.getContext("2d");
                } else {
                    throw new Error(
                        "Input must be an HTMLImageElement or HTMLCanvasElement."
                    );
                }

                // Convert canvas to a Tensor
                const tensor = tf.browser
                    .fromPixels(canvas)
                    .mean(2)
                    .toFloat()
                    .div(255);

                // Normalize & convert back to the range [0, 255]
                const preProcessedTensor = tensor
                    .mul(255)
                    .clipByValue(0, 255)
                    .toInt();

                // Convert Tensor to ImageData
                const [height, width] = preProcessedTensor.shape;
                const imageDataArray = preProcessedTensor.dataSync();
                const imageDataRGBA = new Uint8ClampedArray(width * height * 4);

                for (let i = 0; i < width * height; i++) {
                    imageDataRGBA[i * 4] = imageDataArray[i]; // R
                    imageDataRGBA[i * 4 + 1] = imageDataArray[i]; // G
                    imageDataRGBA[i * 4 + 2] = imageDataArray[i]; // B
                    imageDataRGBA[i * 4 + 3] = 255; // Alpha (Full Opacity)
                }

                // Draw the preprocessed image back to the canvas
                canvas.width = width;
                canvas.height = height;
                ctx.putImageData(
                    new ImageData(imageDataRGBA, width, height),
                    0,
                    0
                );

                // Dispose tensors to free memory
                tensor.dispose();
                preProcessedTensor.dispose();

                // Return the preprocessed canvas
                return canvas;
            }

            /**
             * Convert PDF to Image
             *
             * @param {Uint8Array} pdfData PDF data as Uint8Array
             * @return {Promise<Array<string>>} Resolves to an array of Base64 encoded images
             */
            async function PDFConvertToImage(pdfData) {
                const loadingTask = pdfjsLib.getDocument({ data: pdfData }),
                    pdf = await loadingTask.promise,
                    images = [];

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const scale = 2;
                    const viewport = page.getViewport({ scale });
                    const canvas = document.createElement("canvas");
                    const ctx = canvas.getContext("2d");
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    await page.render({
                        canvasContext: ctx,
                        viewport,
                    }).promise;

                    // Put the image into the array
                    images.push(
                        (await preProcessImage(canvas)).toDataURL("image/png")
                    );
                }

                // Clean up memory
                pdf.destroy();

                return images;
            }

            /**
             * Recognizes text from an array of images using Tesseract OCR.
             *
             * This function takes an array of Base64 encoded images and processes them
             * with Tesseract to extract text. If no text is detected, it returns a message
             * indicating no text was found. The extracted text is sanitized and formatted
             * with HTML line breaks.
             *
             * @param {Uint8Array} uInt8Array An object buffer containing the PDF data (Uint8Array)
             * @return {Promise<string>} Resolves to the OCR results as a sanitized HTML string.
             */
            async function recognizeTextFromPDFWithTesseract(uInt8Array) {
                const images = await PDFConvertToImage(uInt8Array);
                let ocrResults = "";

                const worker = await Tesseract.createWorker({
                    logger: (m) => {
                        // Do nothing
                    },
                    errorHandler: (e) => {
                        // Display error
                        toast(e.message, "error");

                        console.error(e);
                    },
                });

                await worker.loadLanguage(["eng", "ind"]);
                await worker.initialize(["eng", "ind"]);
                await worker.setParameters({
                    user_defined_dpi: 300,
                });

                for (const [index, base64Image] of images.entries()) {
                    const {
                        data: { text },
                    } = await worker.recognize(base64Image);

                    ocrResults += `===== PAGE ${index + 1} =====\n${text}\n\n`;
                }

                if (!ocrResults) {
                    ocrResults =
                        '<div class="empty-state">No text detected.</div>';
                } else {
                    ocrResults = htmlspecialchars(ocrResults)
                        .trim()
                        .replace(/\n/g, "<br />");
                }

                // Clean up memory
                await worker.terminate();

                return ocrResults;
            }

            /**
             * Loads an image from a base64 encoded string.
             *
             * @param {string} base64Image - The base64 encoded image string.
             * @returns {Promise<HTMLImageElement>} A promise that resolves with the loaded image element.
             */
            function loadImageFromBase64Image(base64Image) {
                return new Promise((resolve, reject) => {
                    const image = new Image();
                    image.onload = () => {
                        resolve(image);
                    };
                    image.onerror = (error) => reject(error);
                    // Set the source AFTER defining the onload function
                    image.src = base64Image;
                });
            }

            /**
             * Recognizes text from an image using Tesseract.js
             *
             * @param {string} base64Image - The base64 encoded image to be processed.
             * @returns {Promise<string>} A promise that resolves to the recognized text.
             */
            async function recognizeTextFromImageWithTesseract(base64Image) {
                // Handle program to generate/load the image from base64Image encode
                try {
                    const image = await loadImageFromBase64Image(base64Image);

                    const processedImage = (
                        await preProcessImage(image)
                    ).toDataURL("image/png");

                    const worker = await Tesseract.createWorker({
                        logger: (m) => {
                            // Do nothing
                        },
                        errorHandler: (e) => {
                            // Display error
                            toast(e.message, "error");

                            console.error(e);
                        },
                    });

                    await worker.loadLanguage(["eng", "ind"]);
                    await worker.initialize(["eng", "ind"]);
                    await worker.setParameters({
                        user_defined_dpi: 300,
                    });

                    let {
                        data: { text },
                    } = await worker.recognize(processedImage);

                    if (!text) {
                        text =
                            '<div class="empty-state">No text detected.</div>';
                    } else {
                        text = htmlspecialchars(text)
                            .trim()
                            .replace(/\n/g, "<br />");
                    }

                    // Clean up memory
                    await worker.terminate();

                    return text;
                } catch (err) {
                    throw new Error(`Failed to load image: ${err.message}`);
                }
            }

            /**
             * Recognizes text from an image using the OCR.space API.
             *
             * @param {string} base64File - The base64 encoded image/pdf file to be processed.
             * @param {string} fileName - The name of the file being processed.
             * @param {boolean} [isSpare=false] - Whether to use the spare API key.
             * @throws Will throw an error if the upload or text recognition fails.
             * @returns {Promise<void>} - A promise that resolves when the text is recognized.
             */
            async function recognizeTextOCRSpace(base64File, fileName, apiKey) {
                try {
                    const formData = new FormData();
                    formData.append("base64Image", base64File);
                    formData.append("language", "eng");
                    formData.append("detectorientation", true);
                    formData.append("apikey", apiKey);

                    // Send the file to OCR.space
                    const response = await fetch(
                        "https://api.ocr.space/parse/image",
                        {
                            method: "POST",
                            body: formData,
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            `Failed to upload and recognize text from file: ${fileName}. Please try again.`
                        );
                    }

                    const data = await response.json();

                    if (data.IsErroredOnProcessing) {
                        throw new Error(
                            `Failed to upload and recognize text from file: ${fileName}. Please try again.`
                        );
                    }

                    if (!data.ParsedResults) {
                        data.ParsedResults =
                            '<div class="empty-state">No text detected.</div>';
                    } else {
                        data.ParsedResults = htmlspecialchars(
                            data.ParsedResults.map(
                                (result, index) =>
                                    `===== PAGE ${index + 1} =====\n${
                                        result.ParsedText
                                    }\n\n`
                            ).join("")
                        )
                            .trim()
                            .replace(/\n/g, "<br />");
                    }

                    return data.ParsedResults;
                } catch (error) {
                    // Handle the error
                    toast(error.message, "error");

                    console.log(error);
                }
            }

            /**
             * Recognizes text from a file using the server-side API.
             *
             * This function takes a URL and a file as input, uploads the file to the server using the given URL,
             * and processes the file using the server-side API to extract text. If no text is detected, it returns a message
             * indicating no text was found. The extracted text is sanitized and formatted with HTML line breaks.
             *
             * @param {string} url The URL to the server-side API.
             * @param {File} file The file to be processed.
             * @returns {Promise<string>} Resolves to the extracted text as a sanitized HTML string.
             */
            async function recognizeTextOnServer(url, file) {
                const formData = new FormData();
                formData.append("file", file, file.name);

                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(
                        `Failed to upload and recognize text from file: ${file.name}. Please try again.`
                    );
                }

                const data = await response.json();

                toast(data.message, data.success ? "success" : "error");
                // If status succes is false return and die the next code/program
                if (!data.success) return;

                if (!data.result.text) {
                    return '<div class="empty-state">No text detected.</div>';
                } else {
                    return htmlspecialchars(data.result.text)
                        .trim()
                        .replace(/\n/g, "<br />");
                }
            }

            /**
             * Returns the number of pages in a PDF file from the given data.
             *
             * @param {Uint8Array} pdfData - The PDF data in Uint8Array format.
             * @returns {number} The number of pages in the PDF file.
             */
            async function getPDFPages(pdfData) {
                const pdf = await pdfjsLib.getDocument({ data: pdfData })
                    .promise;

                return pdf.numPages;
            }

            /**
             * Pre-processes a file for text recognition, converting it to a base64 string,
             * determining if it's a PDF, and handling its extraction result display.
             *
             * @param {File} file - The file to be processed, expected in binary format (Uint8Array).
             * @param {string} fileType - The MIME type of the file.
             * @param {HTMLElement} appendedParentElement - The parent element where the result will be appended.
             * @returns {Promise<void>} A promise that resolves when the pre-processing and recognition tasks are completed.
             */
            async function preProcessRecognizing(
                binaryFileUint8Array,
                file,
                appendedParentElement
            ) {
                const fileUint8Array = new Uint8Array(binaryFileUint8Array), // Duplikat object buffer of Uint8Array from binaryFileUint8Array variable
                    base64File = (fileUint8Array, fileType) => {
                        let binaryString = fileUint8Array.reduce(
                            (acc, byte) => acc + String.fromCharCode(byte),
                            ""
                        );

                        return `data:${fileType};base64,${btoa(binaryString)}`;
                    },
                    isPDF = file.type === "application/pdf",
                    pdfPages = isPDF
                        ? await getPDFPages(new Uint8Array(fileUint8Array)) // Duplikat object buffer of Uint8Array from from fileUint8Array variable
                        : null,
                    extractionContainerElement =
                        appendedParentElement.nextElementSibling;

                // Check if previous element result is exist and deleted form HTML structure
                if (
                    extractionContainerElement.tagName === "DIV" &&
                    extractionContainerElement.classList.contains(
                        "extraction-container"
                    )
                )
                    extractionContainerElement.remove();

                try {
                    LOADER.show();
                    if (
                        file.size <= 0.5 * (1024 * 1024) &&
                        isPDF &&
                        pdfPages <= 2
                    ) {
                        // Append element
                        appendedParentElement.insertAdjacentHTML(
                            "afterend",
                            createResultElement(
                                await recognizeTextFromPDFWithTesseract(
                                    binaryFileUint8Array // Use object buffer of Uint8Array from binaryFileUint8Array variable
                                ),
                                file.name
                            )
                        );
                        return;
                    } else if (file.size <= 0.75 * (1024 * 1024) && !isPDF) {
                        // Append element
                        appendedParentElement.insertAdjacentHTML(
                            "afterend",
                            createResultElement(
                                await recognizeTextFromImageWithTesseract(
                                    base64File(fileUint8Array, file.type) // Use base64encode file form base64File function
                                ),
                                file.name
                            )
                        );
                    } else if (
                        file.size <= 1024 * 1024 &&
                        (!isPDF || pdfPages <= 3)
                    ) {
                        // Append element
                        appendedParentElement.insertAdjacentHTML(
                            "afterend",
                            createResultElement(
                                await recognizeTextOCRSpace(
                                    base64File(fileUint8Array, file.type), // Use base64encode file form base64File function
                                    file.name,
                                    OCR_SPACE_API_KEYS.OCR_SPACE_API_KEY
                                ),
                                file.name
                            )
                        );
                        return;
                    } else if (
                        file.size <= 5 * (1024 * 1024) &&
                        (!isPDF || pdfPages <= 10)
                    ) {
                        // Append element
                        appendedParentElement.insertAdjacentHTML(
                            "afterend",
                            createResultElement(
                                await recognizeTextOCRSpace(
                                    base64File(fileUint8Array, file.type), // Use base64encode file form base64File function
                                    file.name,
                                    OCR_SPACE_API_KEYS.OCR_SPACE_SPARE_API_KEY
                                ),
                                file.name
                            )
                        );
                        return;
                    } else {
                        // Recognize using server
                        // Append element
                        appendedParentElement.insertAdjacentHTML(
                            "afterend",
                            createResultElement(
                                await recognizeTextOnServer(
                                    recognizeUrlServer,
                                    file
                                ),
                                file.name
                            )
                        );
                        return;
                    }
                } catch (error) {
                    // Handle the error
                    toast(error.message, "error");

                    console.error(error);
                } finally {
                    LOADER.hide();
                }
            }
        }
    }
})();
