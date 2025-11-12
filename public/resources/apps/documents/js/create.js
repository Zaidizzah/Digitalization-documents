// Initialize the schema builder
const schemaBuilder = new DocumentSchemaBuilder({
    submissionURL: `${location.origin}/api/documents/schema/save`,
    loadURL: `${location.origin}/api/documents/schema/load`,
    loadSavedURL: `${location.origin}/api/documents/schema/status/get`,
    csrf_token: CSRF_TOKEN,
});

(() => {
    "use strict";

    const formDocumentType = document.getElementById("form-document-type");

    // Handle page unload
    window.addEventListener("beforeunload", (e) => {
        if (schemaBuilder.statusSave.hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue =
                "You have unsaved changes. Are you sure you want to leave?";
            return e.returnValue;
        }
    });

    formDocumentType.addEventListener("submit", function (e) {
        if (!formDocumentType.checkValidity()) {
            e.preventDefault();
        }

        if (
            schemaBuilder.isEmptyAttributes() &&
            !schemaBuilder.hasSavedSchema
        ) {
            e.preventDefault();

            toast("No attributes defined.", "info");

            return;
        } else {
            try {
                const schema = schemaBuilder.collectSchema();

                // check if schema is empty and the attributes
                schemaBuilder.validateAttributes(schema);

                if (schemaBuilder.statusSave.hasUnsavedChanges) {
                    e.preventDefault();
                    if (
                        confirm(
                            `You have unsaved changes on current schema attributes. Would you like to save them first?${
                                schemaBuilder.hasSavedSchema
                                    ? ", this action will overwrite the previous saved schema."
                                    : ""
                            }`
                        )
                    ) {
                        schemaBuilder.saveSchema(schemaBuilder.submissionURL);

                        return;
                    }

                    return;
                } else if (schemaBuilder.hasSavedSchema) {
                    e.preventDefault();
                    if (
                        confirm(
                            "Are you sure you want to save this data with previous saved schema? or you can check it first by clicking the 'Load Schema' button."
                        )
                    ) {
                        formDocumentType.submit();
                    }

                    return;
                } else {
                    e.preventDefault();
                    if (confirm("Are you sure you want to save this data?")) {
                        formDocumentType.submit();
                    }

                    return;
                }
            } catch (error) {
                e.preventDefault();

                // Displaying error message
                toast(error.message, "error");
            }
        }
    });

    const btnReset = document.querySelector("button[type='reset']");
    if (btnReset) {
        btnReset.addEventListener("click", function (e) {
            const formHasUnsavedChanges = Array.from(
                formDocumentType.querySelectorAll("input, select, textarea")
            ).some((input) => input.value.trim() !== input.defaultValue);

            if (
                formHasUnsavedChanges ||
                schemaBuilder.statusSave.hasUnsavedChanges
            ) {
                e.preventDefault();

                if (
                    confirm(
                        "Are you sure you want to reset this form, this includes resetting the schema as well?"
                    )
                ) {
                    schemaBuilder.resetAttributes();

                    formDocumentType.reset();
                }
            }
        });
    }

    const nameInput = document.getElementById("name");
    if (nameInput) {
        nameInput.addEventListener("input", function (e) {
            nameInput.value = schemaBuilder.validateAttributeNameAndReplace(
                nameInput.value
            );
        });
    }
})();
