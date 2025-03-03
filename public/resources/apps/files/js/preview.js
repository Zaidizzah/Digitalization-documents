(() => {
    "use strict";

    const pdfContainer = document.getElementById("file-preview-pdf");

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
                noPage.textContent = `Page ${pageNumber}`;
                canvas.appendChild(noPage);

                page.render(renderContext);
                pdfContainer.appendChild(canvas);
            });
        }

        // Load the PDF
        LOADER.show();
        pdfjsLib
            .getDocument(pdfPreviewUrl)
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
