(() => {
    "use strict";

    const FORM_STATUS = {
        create: true,
        update: false,
    };
    const formUsers = document.getElementById("form-users");
    const modalElement = document.getElementById("modal-users");
    const modalUsers = new bootstrap.Modal(modalElement);

    /**
     * Adds an event listener to the modal users form that prevents the modal from closing if the form status is "update".
     */
    modalElement.addEventListener("hidden.bs.modal", (event) => {
        event.preventDefault();

        if (!FORM_STATUS.create) {
            /**
             * Sets the form status to "create".
             */
            FORM_STATUS.create = true;
            FORM_STATUS.update = false;

            /**
             * Give enabled attribut to password and password_confirmation fields
             */
            const password = document.querySelector('input[name="password"]');
            const passwordConfirmation = document.querySelector(
                'input[name="password_confirmation"]'
            );
            if (password && passwordConfirmation) {
                password.disabled = false;
                passwordConfirmation.disabled = false;
            }

            /**
             * Replace message 'update' to 'save' the action onclick attribut on button submit from
             */
            const btnSubmit = formUsers.querySelector("button[type='submit']");
            if (btnSubmit) {
                const onclick = btnSubmit.getAttribute("onclick");
                if (onclick) {
                    btnSubmit.setAttribute(
                        "onclick",
                        onclick.replace("update", "save")
                    );
                }
            }

            /**
             * Resets the form.
             */
            formUsers.reset();

            /**
             * Reset the action of the form.
             */
            formUsers.action = formUsers.action.replace(/update\/.*/, "store");

            /**
             * Remove the active class from the table row.
             */
            const tableRow = document.querySelector("tr.table-active");
            if (tableRow) {
                tableRow.classList.remove("table-active");
            }
        }
    });

    if (formUsers) {
        const password = document.querySelector('input[name="password"]');
        const confirmPassword = document.querySelector(
            'input[name="password_confirmation"]'
        );

        /**
         * Validates the password and confirmPassword fields by comparing their values.
         * If they do not match, sets the custom validity of confirmPassword to "Passwords do not match.".
         * Otherwise, clears the custom validity.
         */
        if (password && confirmPassword) {
            confirmPassword.addEventListener("input", function (event) {
                if (confirmPassword.value !== password.value) {
                    confirmPassword.setCustomValidity(
                        "Passwords do not match."
                    );
                } else {
                    confirmPassword.setCustomValidity("");
                }
            });
        }
    }

    const btnEdit = document.querySelectorAll(".btn-edit");
    const formDelete = document.querySelectorAll("form.form-delete");

    if (btnEdit) {
        /**
         * Adds an event listener to each button with the class "btn-edit" that opens a modal.
         */
        btnEdit.forEach((btn) => {
            btn.addEventListener("click", async (event) => {
                event.preventDefault();

                btn.closest("tr").classList.add("table-active");

                const id = btn.dataset.id;
                formUsers.action = formUsers.action.replace(
                    "store",
                    "update/" + id
                );

                try {
                    LOADER.show(true);
                    /**
                     * GET request to get the data of the user
                     */
                    const response = await fetch(
                        `${location.origin}/api/users/${id}`,
                        {
                            method: "GET",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                                "XSRF-TOKEN": XSRF_TOKEN,
                                Accept: "application/json",
                            },
                            credentials: "include",
                        }
                    );

                    if (!response.ok) {
                        throw new Error(
                            "Failed to get user data. Please try again."
                        );
                    }

                    const data = await response.json();

                    if (
                        data.hasOwnProperty("success") &&
                        data.success !== true
                    ) {
                        throw new Error(data.message);
                    }

                    // Set the form data
                    const inputName =
                            formUsers.querySelector("input[name='name']"),
                        inputEmail = formUsers.querySelector(
                            "input[name='email']"
                        );

                    if (inputName && inputEmail) {
                        inputName.value = data.user.name;
                        inputEmail.value = data.user.email;
                    }
                } catch (error) {
                    // Display error message
                    toast(error.message, "error");

                    console.log(error);
                    return;
                } finally {
                    LOADER.hide();
                }

                /**
                 * Sets the form status to "update".
                 */
                FORM_STATUS.create = false;
                FORM_STATUS.update = true;

                /**
                 * Give disabled attribut to password and password_confirmation fields
                 */
                const password = document.querySelector(
                    'input[name="password"]'
                );
                const passwordConfirmation = document.querySelector(
                    'input[name="password_confirmation"]'
                );

                if (password && passwordConfirmation) {
                    password.disabled = true;
                    passwordConfirmation.disabled = true;
                }

                // Adding input hidden for _method PUT
                formUsers.insertAdjacentHTML(
                    "beforeend",
                    '<input type="hidden" name="_method" value="PUT">'
                );

                /**
                 * Replace message 'save' to 'update' the action onclick attribut on button submit from
                 */
                const btnSubmit = formUsers.querySelector(
                    "button[type='submit']"
                );
                if (btnSubmit) {
                    const onclick = btnSubmit.getAttribute("onclick");
                    if (onclick) {
                        btnSubmit.setAttribute(
                            "onclick",
                            onclick.replace("save", "update")
                        );
                    }
                }

                /**
                 * Opens the modal.
                 */
                modalUsers.show();
            });
        });
    }

    if (formDelete) {
        formDelete.forEach((form) => {
            form.addEventListener("submit", (event) => {
                event.preventDefault();

                const name = form
                    .closest("tr")
                    .querySelector("td:nth-child(2)").textContent;

                const confirmation = confirm(
                    `Are you sure you want to delete data '${name}'?`
                );

                if (confirmation) {
                    form.submit();
                }
            });
        });
    }

    const btnSubmit = formUsers.querySelector("button[type='submit']");
    if (btnSubmit) {
        btnSubmit.addEventListener("click", (event) => {
            if (formUsers.checkValidity()) {
                event.preventDefault();
                if (confirm("Are you sure you want to save this data?")) {
                    formUsers.submit();
                }

                return;
            }
        });
    }
})();
