(() => {
    "use strict";

    const formDelete = document.querySelectorAll("form.form-delete");
    const formRestore = document.querySelectorAll("form.form-restore");

    if (formDelete) {
        /**
         * Adds an event listener to each button with the class "btn-delete" that opens a confirmation alert.
         */
        formDelete.forEach((form) => {
            form.addEventListener("submit", (event) => {
                event.preventDefault();

                const name = form
                    .closest("tr")
                    .querySelector("td:nth-child(2)").textContent;

                const confirmation = confirm(
                    `Are you sure you want to delete document type '${name}'?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }

    if (formRestore) {
        /**
         * Adds an event listener to each button with the class "btn-restore" that opens a confirmation alert.
         */
        formRestore.forEach((form) => {
            form.addEventListener("submit", (event) => {
                event.preventDefault();

                const name = form
                    .closest("tr")
                    .querySelector("td:nth-child(2)").textContent;

                const confirmation = confirm(
                    `Are you sure you want to restore document type '${name}'?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }
})();
