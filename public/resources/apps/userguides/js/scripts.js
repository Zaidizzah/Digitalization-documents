(() => {
    "use strict";

    const TEXT_EDITOR_HTML = new TextEditorHTML("editor-content-wrapper", {
        uploadEndpoint: "/custom-upload",
        minHeight: "500px",
    });
})();
