(() => {
    "use strict";

    const btnCopyExtractedResult = document.querySelectorAll(
            ".btn-copy-ex-result"
        ),
        inputFieldsContainer = document.querySelectorAll(
            ".input-fields-container"
        ),
        btnAddInputField = document.querySelectorAll(".btn-add-input-field");

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

    if (inputFieldsContainer) {
        inputFieldsContainer.forEach((element, index) => {
            element.addEventListener("click", function (event) {
                const btn = event.target.closest(".btn-delete-input-field");

                if (btn) {
                    const inputFieldWrapper = event.target.closest(
                        ".input-field-wrapper"
                    );

                    // remove element from DOM
                    if (
                        inputFieldWrapper &&
                        confirm("Are you sure to remove this input field?")
                    )
                        inputFieldWrapper.remove();
                }
            });
        });
    }

    // initialize input unique tracker
    const uniqueTracker = new UniqueInputTracker();
    uniqueTracker.addStyles();

    const refreshTracker = () => {
        uniqueTracker.refreshAll();
    };
})();
