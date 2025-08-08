// Initialize the text editor
const TEXT_EDITOR_HTML = new TextEditorHTML("editor-content-wrapper", {
    uploadEndpoint: `${location.origin}/api/settings/user-guide/upload`,
    minHeight: "500px",
});

() => {
    "use strict";

    document.querySelectorAll(".markdown-input").forEach((el) => {
        let lines = el.textContent.split("\n");
        lines = lines.map((line) => line.replace(/^\s+/, ""));
        el.textContent = lines.join("\n");
    });
};
