(() => {
    "use strict";

    const formDeleteDocumentTypeData = document.querySelectorAll(
        "form.form-delete-document-type-data"
    );
    if (formDeleteDocumentTypeData) {
        formDeleteDocumentTypeData.forEach((form) => {
            form.addEventListener("submit", (event) => {
                event.preventDefault();

                const confirmation = confirm(
                    `Are you sure you want to delete this data?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }
})();
