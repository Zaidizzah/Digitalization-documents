(() => {
    "use strict";

    const formDeleteAll = document.querySelector("form.form-delete-all");
    const formDelete = document.querySelector("form.form-delete");

    if (formDelete) {
        formDelete.addEventListener("submit", (event) => {
            event.preventDefault();

            const name = formDelete.dataset.name;

            const confirmation = confirm(
                `Are you sure you want to delete document type '${name}'?`
            );

            if (confirmation) {
                formDelete.submit();
            }
        });
    }

    if (formDeleteAll) {
        formDeleteAll.addEventListener("submit", (event) => {
            event.preventDefault();

            const name = formDeleteAll.dataset.name;

            const confirmation = confirm(
                `Are you sure you want to delete all data in document type '${name}'?`
            );

            if (confirmation) {
                formDeleteAll.submit();
            }
        });
    }
})();
