// Initialize the schema builder
const schemaBuilder = new DocumentSchemaBuilder({
    submissionURL: `${location.origin}/api/documents/schema/save`,
    isModify: true,
    csrf_token: CSRF_TOKEN,
});

(() => {
    "use strict";

    const formModifyDocumentType = document.getElementById(
        "form-modify-document-type"
    );

    formModifyDocumentType.addEventListener("submit", (e) => {
        if (!formModifyDocumentType.checkValidity()) {
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

                if (confirm("Are you sure you want to save this data?")) {
                    formModifyDocumentType.querySelector(
                        'button[type="submit"]'
                    ).disabled = true;

                    if (schemaBuilder.saveSchema(schemaBuilder.submissionURL)) {
                        formModifyDocumentType.submit();
                    } else {
                        e.preventDefault();

                        formModifyDocumentType.querySelector(
                            'button[type="submit"]'
                        ).disabled = false;

                        return;
                    }
                } else {
                    e.preventDefault();

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
