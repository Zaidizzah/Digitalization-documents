(() => {
    "use strict";

    const filePreviewWrapper = document.getElementById("file-preview-wrapper"),
        filePreviewImage = document.querySelector(
            "#file-preview-wrapper #file-preview-image"
        ),
        pdfContainer = document.getElementById("file-preview-pdf"),
        panzoom = Panzoom(filePreviewWrapper, {
            maxScale: 5,
            minScale: 0.5,
            contain: "outside",
        });

    if (filePreviewWrapper) {
        if (filePreviewImage) {
            panzoom.pan(10, 10);
            filePreviewImage.addEventListener(
                "wheel",
                function (event) {
                    event.preventDefault();
                    panzoom.zoomWithWheel(event);
                },
                { passive: false }
            );
        } else {
            panzoom.destroy();
            panzoom.setStyle("cursor", "default");
        }
    }

    if (pdfContainer) {
        const pdfPreviewUrl = pdfContainer.getAttribute("data-url-preview"),
            pdfTitle = pdfContainer.getAttribute("data-title");

        pdfjsLib.GlobalWorkerOptions.workerSrc =
            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.worker.min.js";

        function renderPage(pdf, pageNumber) {
            pdf.getPage(pageNumber).then(function (page) {
                const scale = window.innerWidth <= 768 ? 1 : 1.5;

                const canvas = document.createElement("canvas");
                const context = canvas.getContext("2d");

                const viewport = page.getViewport({ scale: scale });
                const outputScale = window.devicePixelRatio || 1;

                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);

                const transform =
                    outputScale !== 1
                        ? [outputScale, 0, 0, outputScale, 0, 0]
                        : null;

                const renderContext = {
                    canvasContext: context,
                    transform: transform,
                    viewport: viewport,
                };

                const noPage = document.createElement("span");
                noPage.classList.add("no-page");
                noPage.id = `no-page-${pageNumber}`;
                noPage.textContent = `Page ${pageNumber}`;
                canvas.appendChild(noPage);

                // adding some accebilitas like aria and title for page number
                canvas.setAttribute(
                    "aria-label",
                    `${pdfTitle} - Page ${pageNumber} of ${pdf.numPages}`
                );
                canvas.setAttribute(
                    "title",
                    `${pdfTitle} - Page ${pageNumber} of ${pdf.numPages}`
                );
                canvas.setAttribute("aria-labelledby", `no-page-${pageNumber}`);
                canvas.setAttribute("role", "img");

                page.render(renderContext);
                pdfContainer.appendChild(canvas);
            });
        }

        // Load the PDF
        LOADER.show();
        pdfjsLib
            .getDocument({
                url: pdfPreviewUrl,
                withCredentials: true,
                httpHeaders: {
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
            })
            .promise.then(function (pdf) {
                toast(`File ${pdfTitle} has been loaded successfully.`);

                // Get all pages of the PDF and display them
                for (let i = 1; i <= pdf.numPages; i++) {
                    renderPage(pdf, i);
                }
            })
            .catch(function (error) {
                toast(`Failed to load file ${pdfTitle}.`, "error");
            })
            .finally(function () {
                LOADER.hide();
            });
    }
})();
