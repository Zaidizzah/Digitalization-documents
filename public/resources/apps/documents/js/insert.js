// Initialize the schema builder
const schemaBuilder = new DocumentSchemaBuilder({
    submissionURL: `${location.origin}/api/documents/schema/save`,
    loadURL: `${location.origin}/api/documents/schema/load`,
    csrf_token: CSRF_TOKEN,
});

(() => {
    "use strict";

    const formInsertDocumentType = document.getElementById(
        "form-insert-document-type"
    );

    formInsertDocumentType.addEventListener("submit", (e) => {
        if (!formInsertDocumentType.checkValidity()) {
            e.preventDefault();
        }

        if (
            !schemaBuilder.hasSavedSchema &&
            schemaBuilder.isEmptyAttributes()
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
                        formInsertDocumentType.submit();
                    }

                    return;
                } else {
                    e.preventDefault();
                    if (confirm("Are you sure you want to save this data?")) {
                        formInsertDocumentType.submit();
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
})();
