// CHeck if SHOWDOWN JS is loaded
if (typeof showdown === "undefined") {
    throw new Error(
        "SHOWDOWN JS is not loaded. Please load SHOWDOWN JS first at this time editor is not working."
    );
}

// --- ALERT extension for SHOWDOWN JS ---
showdown.extension("alert", function () {
    return [
        {
            type: "lang",
            regex: /(^|\n) *> *\[\!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\n((?: *>.*\n?)*)/g,
            replace: function (_, leading, alertType, content) {
                const cleanedContent = content
                    .replace(/^ *> ?/gm, "")
                    .replace(/\n$/, "");

                const htmlContent = new showdown.Converter().makeHtml(
                    cleanedContent
                );

                return `${leading}<div class="alert alert-${alertType.toLowerCase()}" role="alert">
                        <div class="alert-heading">
                        <img class="alert-heading-icon" src="${
                            location.origin
                        }/resources/images/icons/alert-${alertType.toLowerCase()}.svg" width="24" height="24" alt="${alertType.toLowerCase()}">
                        <h4>${alertType.toUpperCase()}</h4>
                        </div>
                        <div class="alert-content">
                        ${htmlContent}
                        </div>
                        </div>`;
            },
        },
    ];
});
// --- TASK LIST extension for SHOWDOWN JS ---
showdown.extension("tasklist", function () {
    return [
        {
            type: "output",
            regex: /<li>\s*\[ \]\s*(.*)<\/li>/g,
            replace: '<li><input type="checkbox" disabled> $1</li>',
        },
        {
            type: "output",
            regex: /<li>\s*\[x\]\s*(.*)<\/li>/gi,
            replace: '<li><input type="checkbox" checked disabled> $1</li>',
        },
    ];
});
// --- SUMMARY extension for SHOWDOWN JS ---
showdown.extension("summary", function () {
    return [
        {
            type: "lang",
            regex: /:::summary\s+(.+)\n([\s\S]+?):::/g,
            replace: function (_, summaryTitle, content) {
                const innerHtml = new showdown.Converter().makeHtml(
                    content.trim()
                );
                return `<details>
                <summary>${summaryTitle.trim()}</summary>
                ${innerHtml}
                </details>`;
            },
        },
    ];
});
// --- NESTED SMART LIST extension for SHOWDOWN JS ---
showdown.extension("nestedSmartList", function () {
    return [
        {
            type: "lang",
            regex: /(?:^|\n)([ \t]*)([*+-]|\d+|[a-zA-Z]{1,4})\.\s+(.*?)(?=\n|$)/g,
            replace: function (_, indentRaw, marker, content) {
                const indent = indentRaw.replace(/\t/g, "    ").length;
                let type = "decimal";

                if (/^[IVXLCDM]+$/.test(marker)) type = "upper-roman";
                else if (/^[ivxlcdm]+$/.test(marker)) type = "lower-roman";
                else if (/^[A-Z]$/.test(marker)) type = "upper-alpha";
                else if (/^[a-z]$/.test(marker)) type = "lower-alpha";
                else if (/^[*+-]$/.test(marker)) type = "unordered";

                return `\n::LIST|${indent}|${type}::${content}`;
            },
        },
        {
            type: "output",
            regex: /(?:::LIST\|\d+\|[a-z-]+\:\:.*(?:\n)?)+/g,
            replace: function (block) {
                const lines = block.trim().split(/\n/);
                const stack = [];
                let html = "";

                lines.forEach((line) => {
                    const match = line.match(
                        /^::LIST\|(\d+)\|([a-z-]+)::(.*)$/
                    );
                    if (!match) return;

                    const [, indentStr, type, content] = match;
                    const indent = parseInt(indentStr, 10);
                    const tag = type === "unordered" ? "ul" : "ol";
                    const style =
                        type === "unordered"
                            ? 'style="list-style-type: disc;"'
                            : `style="list-style-type: ${type};"`;

                    // Close list if indent up or same but type changed
                    while (
                        stack.length &&
                        (stack[stack.length - 1].indent > indent ||
                            (stack[stack.length - 1].indent === indent &&
                                stack[stack.length - 1].type !== type))
                    ) {
                        const last = stack.pop();
                        html +=
                            "</li></" +
                            (last.type === "unordered" ? "ul" : "ol") +
                            ">";
                    }

                    if (
                        !stack.length ||
                        stack[stack.length - 1].indent < indent ||
                        stack[stack.length - 1].type !== type
                    ) {
                        html += `<${tag} ${style}><li>${content}`;
                        stack.push({ indent, type });
                    } else {
                        html += "</li><li>" + content;
                    }
                });

                while (stack.length) {
                    const last = stack.pop();
                    html +=
                        "</li></" +
                        (last.type === "unordered" ? "ul" : "ol") +
                        ">";
                }

                return html;
            },
        },
    ];
});

const SHOWDOWN = new showdown.Converter({
    extensions: ["alert", "tasklist", "summary", "nestedSmartList"],
    tables: true,
    simplifiedAutoLink: true,
    literalMidWordUnderscores: true,
    strikethrough: true,
    ghCompatibleHeaderId: true,
    emojies: true,
});

class TextEditorHTML {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.querySelector(`#${containerId}`);
        this.options = {
            uploadEndpoint: options.uploadEndpoint || "/api/upload",
            showSettings: options.showSettings || true,
            showFooter: options.showFooter || true,
            attributes: options.attributes || {},
        };

        this.init();
    }

    init() {
        if (!this.container) {
            console.error(`Container with id "${this.containerId}" not found`);
            return;
        }

        // check SHOWDOWN is loaded and has extensions
        if (typeof SHOWDOWN === undefined || typeof showdown === undefined) {
            console.error(
                "SHOWDOWN is not loaded. Please load SHOWDOWNJS first and at this time editor is not working"
            );
            return;
        }
        if (SHOWDOWN.getOptions().extensions.includes("alert") === undefined) {
            console.error("SHOWDOWN extension 'alert' not found");
            return;
        }
        if (
            SHOWDOWN.getOptions().extensions.includes("tasklist") === undefined
        ) {
            console.error("SHOWDOWN extension 'tasklist' not found");
            return;
        }
        if (
            SHOWDOWN.getOptions().extensions.includes("summary") === undefined
        ) {
            console.error("SHOWDOWN extension 'summary' not found");
            return;
        }

        // Set attributes value to attributes element for textarea editor
        this.texteditorAttributes = "";
        if (this.options.attributes) {
            if (this.options.attributes.hasOwnProperty("name")) {
                this.texteditorAttributes += `name="${
                    this.options.attributes.name || "content"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("spellcheck")) {
                this.texteditorAttributes += ` spellcheck="${
                    this.options.attributes.spellcheck || "false"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("placeholder")) {
                this.texteditorAttributes += ` placeholder="${
                    this.options.attributes.placeholder ||
                    "Type your contents here..."
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("class")) {
                this.texteditorAttributes += ` class="${
                    this.options.attributes.class || ""
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("style")) {
                this.texteditorAttributes += ` style="${
                    this.options.attributes.style || "min-height: 300px;"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("rows")) {
                this.texteditorAttributes += ` rows="${
                    this.options.attributes.rows || "3"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("cols")) {
                this.texteditorAttributes += ` cols="${
                    this.options.attributes.cols || "5"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("autofocus")) {
                this.texteditorAttributes += ` autofocus="${
                    this.options.attributes.autofocus || "false"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("minlength")) {
                this.texteditorAttributes += ` minlength="${
                    this.options.attributes.minlength || "0"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("maxlength")) {
                if (
                    this.options.attributes.maxlength !== 0 &&
                    this.options.attributes.maxlength > 0
                ) {
                    this.texteditorAttributes += ` maxlength="${
                        this.options.attributes.maxlength || "0"
                    }"`;
                }
            }
            if (this.options.attributes.hasOwnProperty("required")) {
                this.texteditorAttributes += ` required="${
                    this.options.attributes.required || "true"
                }" aria-required="${
                    this.options.attributes.required || "true"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("disabled")) {
                this.texteditorAttributes += ` disabled="${
                    this.options.attributes.disabled || "false"
                }"`;
            }
            if (this.options.attributes.hasOwnProperty("readonly")) {
                this.texteditorAttributes += ` readonly="${
                    this.options.attributes.readonly || "false"
                }"`;
            }
        }

        this.createEditorHTML();
        this.initializeElements();
        this.initEventListeners();
        this.updatePreview();
        this.slashCommandActive = false;
    }

    createEditorHTML() {
        const editorHTML = `
                    <div class="text-editor-html-container">
                        <div class="editor-header">
                            <div class="tabs" role="tablist">
                                <button class="tab active" role="tab" data-tab="write">Write</button>
                                <button class="tab" role="tab" data-tab="preview">Preview</button>
                            </div>
                            <div class="toolbar">
                                <!-- Bold Button -->
                                <button class="toolbar-btn" data-action="bold" title="Bold (Ctrl+B)">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6 4h8.5a4.5 4.5 0 0 1 3.256 7.606A5 5 0 0 1 15.5 20H6V4zm2.5 2.5v5h5.5a2 2 0 1 0 0-4H8.5zm0 7.5v4h7a2.5 2.5 0 0 0 0-5H8.5z"
                                            fill="currentColor"/>
                                    </svg>
                                </button>

                                <!-- Italic Button -->
                                <button class="toolbar-btn" data-action="italic" title="Italic (Ctrl+I)">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                                        <path d="M10 4h6M8 20h6m1-16-4 16"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <!-- Header Button -->
                                <button class="toolbar-btn" data-action="header" title="Header">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                                        <path d="M6 12h12M6 20V4m6 16V4"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <!-- Quote Button -->
                                <button class="toolbar-btn" data-action="quote" title="Quote">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1zM15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h2v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"
                                            fill="currentColor"/>
                                    </svg>
                                </button>

                                <!-- Code Button -->
                                <button class="toolbar-btn" data-action="code" title="Code">
                                    <svg viewBox="0 0 16 16">
                                        <path d="M10.478 1.647a.5.5 0 1 0-.956-.294l-4 13a.5.5 0 0 0 .956.294l4-13zM4.854 4.146a.5.5 0 0 1 0 .708L1.707 8l3.147 3.146a.5.5 0 0 1-.708.708l-3.5-3.5a.5.5 0 0 1 0-.708l3.5-3.5a.5.5 0 0 1 .708 0zm6.292 0a.5.5 0 0 0 0 .708L14.293 8l-3.147 3.146a.5.5 0 0 0 .708.708l3.5-3.5a.5.5 0 0 0 0-.708l-3.5-3.5a.5.5 0 0 0-.708 0z"/>
                                    </svg>
                                </button>

                                <!-- Link Button -->
                                <button class="toolbar-btn" data-action="link" title="Link (Ctrl+K)">
                                    <svg viewBox="0 0 16 16">
                                        <path d="M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9c-.086 0-.17.01-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z"/>
                                        <path d="M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4.02 4.02 0 0 1-.82 1H12a3 3 0 1 0 0-6H9z"/>
                                    </svg>
                                </button>

                                <!-- Unordered List Button -->
                                <button class="toolbar-btn" data-action="list" title="Bullet List">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                                        <line x1="8" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <line x1="8" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <line x1="8" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <circle cx="4" cy="6" r="1" fill="currentColor"/>
                                        <circle cx="4" cy="12" r="1" fill="currentColor"/>
                                        <circle cx="4" cy="18" r="1" fill="currentColor"/>
                                    </svg>
                                </button>

                                <!-- Numbered List Button -->
                                <button class="toolbar-btn" data-action="ordered-list" title="Numbered List">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none">
                                        <line x1="10" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <line x1="10" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <line x1="10" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M4 6h1v4M4 10h2m-2 4h.01M4 18h.01M5 18v.01"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <!-- Task List Button -->
                                <button class="toolbar-btn" data-action="task-list" title="Task List">
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 3h6a1.5 1.5 0 0 1 1.5 1.5v6A1.5 1.5 0 0 1 9 12H3a1.5 1.5 0 0 1-1.5-1.5v-6A1.5 1.5 0 0 1 3 3Zm6.983 12.893a1.125 1.125 0 0 1 0 1.59l-3.938 3.938a1.125 1.125 0 0 1-1.59 0l-2.25-2.25a1.124 1.124 0 0 1 .489-1.913 1.124 1.124 0 0 1 1.101.323l1.455 1.455 3.143-3.143a1.125 1.125 0 0 1 1.59 0ZM14.625 3.75h8.25a1.125 1.125 0 0 1 0 2.25h-8.25a1.125 1.125 0 0 1 0-2.25Zm0 7.5h8.25a1.125 1.125 0 0 1 0 2.25h-8.25a1.125 1.125 0 0 1 0-2.25Zm0 7.5h8.25a1.125 1.125 0 0 1 0 2.25h-8.25a1.125 1.125 0 0 1 0-2.25ZM3.375 5.625v4.5h4.5v-4.5Z"
                                            fill="currentColor"/>
                                    </svg>
                                </button>

                                <!-- Summary/Details Button -->
                                <button class="toolbar-btn" data-action="summary" title="Summary">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M2 2h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H5.414L2 14V3a1 1 0 0 1 1-1z"/>
                                        <circle cx="5" cy="7" r="1"/>
                                        <circle cx="8" cy="7" r="1"/>
                                        <circle cx="11" cy="7" r="1"/>
                                    </svg>
                                </button>

                                <!-- Table Button -->
                                <button class="toolbar-btn" data-action="table" title="Table">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h11A1.5 1.5 0 0 1 15 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 13.5v-11zM2.5 2a.5.5 0 0 0-.5.5V5h12V2.5a.5.5 0 0 0-.5-.5h-11zM14 6H2v4h12V6zM2 11v2.5a.5.5 0 0 0 .5.5H5v-3H2zm4 0v3h4v-3H6zm5 0v3h2.5a.5.5 0 0 0 .5-.5V11h-3z"/>
                                    </svg>
                                </button>

                                <!-- Hr Button -->
                                <button class="toolbar-btn" data-action="hr" title="Hr">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                        <rect x="2" y="7.5" width="12" height="1" rx="0.5"/>
                                    </svg>
                                </button>

                                <!-- Alert Button -->
                                <button class="toolbar-btn" data-action="alert" title="Alert">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M7.938 2.016a.13.13 0 0 1 .125 0l6.857 11.856c.04.069.08.176.08.253 0 .275-.223.5-.5.5H1.5a.5.5 0 0 1-.5-.5c0-.077.04-.184.08-.253L7.938 2.016zM8 5c-.535 0-.954.462-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 5zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
                                    </svg>
                                </button>

                                <!-- Image Button -->
                                <button class="toolbar-btn" data-action="image" title="Insert Image">
                                    <svg viewBox="0 0 16 16">
                                        <path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
                                        <path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
                                    </svg>
                                </button>
                            </div>
                          </div>
                          <div class="editor-content">
                              <textarea class="editor-textarea" id="--editor-textarea" ${
                                  this.texteditorAttributes
                              }></textarea>
                              <div class="preview-content"></div>
                          </div>

                          ${
                              this.options.showFooter
                                  ? `
                          <div class="footer">
                              <p>Styling with Markdown is supported. Use ____text____ for bold/italic. Drag & drop images directly into the editor.</p>
                          </div>
                          `
                                  : ""
                          }

                          <input type="file" class="file-input" accept="image/png, image/jpg, image/jpeg, image/webp, image/gif, image/svg+xml, image/avif" multiple style="display: none;">
                      </div>
                  `;

        this.container.innerHTML = editorHTML;
    }

    initializeElements() {
        this.editorContainer = this.container.querySelector(
            ".text-editor-html-container"
        );
        this.textarea = this.container.querySelector(".editor-textarea");
        this.previewContent = this.container.querySelector(".preview-content");
        this.fileInput = this.container.querySelector(".file-input");

        // Initialize SlashCommands manu
        this.slashCommandsMenu = document.createElement("div");
        this.slashCommandsMenu.className = "slash-commands-menu";
        this.slashCommandsMenu.role = "menu";
        this.slashCommandsMenu.ariaLabel = "Slash commands menu";
        this.slashCommandsMenu.ariaHidden = "true"; // hidden = "true";
        this.container.appendChild(this.slashCommandsMenu); // add to body

        // Adding value to texteditor if attributes value already set
        if (
            this.options.attributes.hasOwnProperty("value") &&
            this.textarea !== undefined
        ) {
            this.textarea.value = this.options.attributes.value
                .replace(/'/g, "\\'")
                .replace(/"/g, '\\"');
        }
    }

    initEventListeners() {
        // Tab switching
        this.container.querySelectorAll(".tab").forEach((tab) => {
            tab.addEventListener("click", (e) => {
                e.preventDefault();
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Toolbar actions
        this.container.querySelectorAll(".toolbar-btn").forEach((btn) => {
            const tabPreview = this.container.querySelector(
                '.tabs .tab[data-tab="preview"]'
            );

            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                console.log("tabPreview: ", tabPreview);

                // Switch to write mode first if in preview mode
                if (
                    tabPreview !== undefined &&
                    tabPreview.classList.contains("active")
                ) {
                    this.switchTab("write");
                }

                this.handleToolbarAction(e.currentTarget.dataset.action);
            });
        });

        // File upload
        if (this.fileInput) {
            this.fileInput.addEventListener("change", (e) =>
                this.handleFileSelect(e)
            );
        }

        // Direct drag and drop to textarea
        this.textarea.addEventListener("dragover", (e) =>
            this.handleTextareaDragOver(e)
        );
        this.textarea.addEventListener("dragleave", (e) =>
            this.handleTextareaDragLeave(e)
        );
        this.textarea.addEventListener("drop", (e) =>
            this.handleTextareaDrop(e)
        );

        // Real-time preview update
        this.textarea.addEventListener("input", () => {
            this.updatePreview();
            this.handleInput();
        });

        // Keyboard shortcuts
        this.textarea.addEventListener("keydown", (e) => this.handleKeydown(e));

        // Paste image handling
        this.textarea.addEventListener("paste", (e) => this.handlePaste(e));
    }

    switchTab(tab) {
        this.container
            .querySelectorAll(".tab")
            .forEach((t) => t.classList.remove("active"));
        this.container
            .querySelector(`[data-tab="${tab}"]`)
            .classList.add("active");

        if (tab === "write") {
            this.textarea.style.display = "block";
            this.previewContent.style.display = "none";
            this.textarea.focus();
        } else {
            this.textarea.style.display = "none";
            this.previewContent.style.display = "block";
            this.updatePreview();
        }
    }

    async handleToolbarAction(action) {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const selectedText = this.textarea.value.substring(start, end);
        let replacement = "";
        let cursorOffset = 0;
        const value = this.textarea.value;
        const currentLine = this.getCurrentLine();

        // Check if we need to create a new line for block-level elements
        const blockElements = [
            "header",
            "quote",
            "task-list",
            "image",
            "alert",
            "hr",
            "summary",
        ];
        const shouldCreateNewLine =
            blockElements.includes(action) && currentLine.trim().length > 0;

        switch (action) {
            case "bold":
                if (selectedText && this.isWrapped(selectedText, "**", "**")) {
                    replacement = selectedText.slice(2, -2);
                    cursorOffset = -4;
                } else if (
                    !selectedText &&
                    value.substring(start - 2, start) === "**" &&
                    value.substring(end, end + 2) === "**"
                ) {
                    // Hapus formatting jika kursor di tengah formatting kosong
                    replacement = "";
                    this.textarea.value =
                        value.substring(0, start - 2) +
                        value.substring(end + 2);
                    this.textarea.setSelectionRange(start - 2, start - 2);
                    return;
                } else {
                    replacement = "****";
                    cursorOffset = 2;
                }
                break;
            case "italic":
                if (
                    selectedText &&
                    (this.isWrapped(selectedText, "*", "*") ||
                        this.isWrapped(selectedText, "_", "_") ||
                        this.isWrapped(selectedText, "__", "__"))
                ) {
                    replacement = selectedText.slice(1, -1);
                    cursorOffset = -2;
                } else if (
                    !selectedText &&
                    ((value.substring(start - 1, start) === "*" &&
                        value.substring(end, end + 1) === "*") ||
                        (value.substring(start - 1, start) === "_" &&
                            value.substring(end, end + 1) === "_") ||
                        (value.substring(start - 2, start) === "__" &&
                            value.substring(end, end + 2) === "__"))
                ) {
                    // Hapus formatting jika kursor di tengah formatting kosong
                    replacement = "";
                    if (value.substring(start - 1, start) === "*") {
                        this.textarea.value =
                            value.substring(0, start - 1) +
                            value.substring(end + 1);
                        this.textarea.setSelectionRange(start - 1, start - 1);
                    } else if (
                        value.substring(start - 2, start) === "__" &&
                        value.substring(end, end + 2) === "__"
                    ) {
                        this.textarea.value =
                            value.substring(0, start - 2) +
                            value.substring(end + 2);
                        this.textarea.setSelectionRange(start - 2, start - 2);
                    } else if (value.substring(start - 1, start) === "_") {
                        this.textarea.value =
                            value.substring(0, start - 1) +
                            value.substring(end + 1);
                        this.textarea.setSelectionRange(start - 1, start - 1);
                    }
                } else {
                    replacement = "____";
                    cursorOffset = 2;
                }
                break;
            case "header":
                if (shouldCreateNewLine) {
                    replacement = "\n### ";
                } else {
                    replacement = "### ";
                }
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "quote":
                if (shouldCreateNewLine) {
                    replacement = "\n> ";
                } else {
                    replacement = "> ";
                }
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "code":
                if (selectedText.includes("\n")) {
                    if (shouldCreateNewLine) {
                        replacement = "\n```\n```";
                    } else {
                        replacement = "```\n```";
                    }
                    cursorOffset = 4;
                } else {
                    replacement = "``";
                    cursorOffset = 1;
                }
                break;
            case "link":
                if (
                    !selectedText &&
                    value.substring(start - 1, start) === "[" &&
                    value.substring(end, end + 1) === "]" &&
                    value.substring(end + 1, end + 2) === "(" &&
                    value.substring(end + 2, end + 3) === ")"
                ) {
                    // Hapus formatting link kosong
                    replacement = "";
                    this.textarea.value =
                        value.substring(0, start - 1) +
                        value.substring(end + 3);
                    this.textarea.setSelectionRange(start - 1, start - 1);
                    return;
                } else {
                    replacement = "[]()";
                    cursorOffset = 1;
                }
                break;
            case "list":
                replacement = "- ";
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "ordered-list":
                replacement = "1. ";
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "image":
                this.fileInput.click();
                return;
            case "task-list":
                if (shouldCreateNewLine) {
                    replacement = "\n- [ ] ";
                } else {
                    replacement = "- [ ] ";
                }
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "alert":
                if (shouldCreateNewLine) {
                    replacement = "\n/ ";
                } else {
                    replacement = "/ ";
                }
                cursorOffset = replacement.length - selectedText.length;

                // show alert slash command menu
                this.slashCommandActive = true;
                this.showSlashCommands();
                break;
            case "hr":
                if (shouldCreateNewLine) {
                    replacement = "\n---\n";
                } else {
                    replacement = "---\n";
                }
                cursorOffset = replacement.length - selectedText.length;
                break;
            case "summary":
                if (shouldCreateNewLine) {
                    replacement = "\n:::summary  \n\n:::\n";
                } else {
                    replacement = ":::summary  \n\n:::\n";
                }

                cursorOffset = 11;
                break;
            case "table":
                // show table grid input
                this.showTableGridInput();
                break;
        }

        this.insertText(replacement, start, end, cursorOffset);
    }

    isWrapped(text, prefix, suffix) {
        return text.startsWith(prefix) && text.endsWith(suffix);
    }

    getCurrentLine() {
        const value = this.textarea.value;
        const cursorPos = this.textarea.selectionStart;
        let lineStart = cursorPos;
        let lineEnd = cursorPos;

        // Find start of line
        while (lineStart > 0 && value[lineStart - 1] !== "\n") {
            lineStart--;
        }

        // Find end of line
        while (lineEnd < value.length && value[lineEnd] !== "\n") {
            lineEnd++;
        }

        return value.substring(lineStart, lineEnd);
    }

    insertText(text, start, end, cursorOffset = 0) {
        const value = this.textarea.value;
        this.textarea.value =
            value.substring(0, start) + text + value.substring(end);
        this.textarea.focus();

        // Set cursor position after inserted text
        const newPos = start + cursorOffset;
        this.textarea.setSelectionRange(newPos, newPos);

        this.updatePreview();
    }

    handleInput(e) {
        const cursorPos = this.textarea.selectionStart;
        const textBeforeCursor = this.textarea.value.substring(0, cursorPos);

        // if text before cursor ends with '/' or '/ '
        const lastLine = textBeforeCursor.split("\n").pop();

        if (/^\/(?:\s)?$/.test(lastLine)) {
            this.slashCommandActive = true;
            this.showSlashCommands();
        } else if (this.slashCommandActive) {
            this.hideSlashCommands();
            this.slashCommandActive = false;
        }
    }

    handleKeydown(e) {
        // Tab case adding 4 space
        if (e.key === ";" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();

            const currentPos = this.textarea.selectionStart;

            this.insertText("    ", currentPos, currentPos, 4);
        }

        if (e.key === "/" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            const cursorPos = this.textarea.selectionStart;
            const textBeforeCursor = this.textarea.value.substring(
                0,
                cursorPos
            );

            const lastLine = textBeforeCursor.split("\n").pop();

            if (lastLine === "") {
                this.insertText("/ ", cursorPos, cursorPos, 2);
            } else if (lastLine.trim().endsWith("/")) {
                // Set cursor position after one space after slash character
                this.textarea.setSelectionRange(cursorPos + 1, cursorPos + 1);
            } else if (lastLine.trim() !== "") {
                // check if last line doesn't end with / or empty value (string)
                this.insertText("\n/ ", cursorPos, cursorPos, 3);
            }

            this.slashCommandActive = true;
            this.showSlashCommands();
        } else if (e.key === "Escape") {
            e.preventDefault();
            this.hideSlashCommands();
        }

        if (e.key === "b" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            this.handleToolbarAction("bold");
        } else if (e.key === "i" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            this.handleToolbarAction("italic");
        } else if (e.key === "k" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            this.handleToolbarAction("link");
        }

        if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            const start = this.textarea.selectionStart;
            const value = this.textarea.value;
            this.textarea.value =
                value.substring(0, start) + "\n" + value.substring(start);
            this.textarea.setSelectionRange(start + 1, start + 1);
        } else if (e.key === "Enter") {
            this.handleListEnter(e);
        }
    }

    handlePaste(e) {
        const items = Array.from(e.clipboardData?.items || []);
        const imageItems = items.filter((item) =>
            item.type.startsWith("image/")
        );

        if (imageItems.length > 0) {
            e.preventDefault();
            imageItems.forEach((item) => {
                const file = item.getAsFile();
                if (file) {
                    this.uploadImage(file, this.textarea.selectionStart);
                }
            });
        }
    }

    handleFileSelect(e) {
        const files = Array.from(e.target.files);
        files.forEach((file) => {
            if (file.type.startsWith("image/")) {
                this.uploadImage(file);
            }
        });
        // Reset file input
        e.target.value = "";
    }

    handleTextareaDragOver(e) {
        e.preventDefault();
        this.textarea.classList.add("drag-over");
    }

    handleTextareaDragLeave(e) {
        e.preventDefault();
        this.textarea.classList.remove("drag-over");
    }

    handleTextareaDrop(e) {
        e.preventDefault();
        this.textarea.classList.remove("drag-over");

        const files = Array.from(e.dataTransfer.files).filter((file) =>
            file.type.startsWith("image/")
        );
        if (files.length > 0) {
            // Get cursor position from drop coordinates
            const rect = this.textarea.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Estimate cursor position (this is approximate)
            const lineHeight = parseInt(
                getComputedStyle(this.textarea).lineHeight
            );
            const charWidth = 8; // Approximate character width
            const lines = this.textarea.value
                .substring(0, this.textarea.selectionStart)
                .split("\n");
            const line = Math.floor(y / lineHeight);
            const char = Math.floor(x / charWidth);

            files.forEach((file) => {
                this.uploadImage(file, this.textarea.selectionStart);
            });
        }
    }

    async uploadImage(file, insertPos = null) {
        const ENDPOINT = this.options.uploadEndpoint || location.origin;

        console.log(ENDPOINT);

        // Check if url ENDPOINT is set
        if (ENDPOINT === null || ENDPOINT === "") {
            this.showUploadStatus(
                "error",
                "The upload endpoint is not set. Please set the 'uploadEndpoint' option."
            );
            return;
        }

        // Check if file is valid and not too large (20mb)
        if (typeof file !== "object" || !file.type.startsWith("image/")) {
            this.showUploadStatus(
                "error",
                `The file type is not supported. Please choose a file with a type of 'PNG', 'JPG', 'JPEG', 'WEBP', 'AVIF', or 'GIF'.`
            );
        }

        if (file.size > 20 * 1024 * 1024) {
            this.showUploadStatus(
                "error",
                `The file size is too large. Please choose a file less than 20MB.`
            );
        }

        // Insert uploading status text to editor and add 'POINTER-EVENT: NONE' style to prevent cursor from moving in editor
        this.textarea.style.pointerEvents = "none";
        const UPLOADINGTEXT = `<--- Uploading image "${file.name}"... --->\n`;

        // Check if the cursor is at the start of the textarea
        const isAtStart =
            this.textarea.selectionStart === 0 &&
            this.textarea.value.trim() === "";
        if (isAtStart === false) {
            UPLOADINGTEXT = `\n${UPLOADINGTEXT}`;
        }

        if (insertPos !== null) {
            const value = this.textarea.value;
            this.textarea.value =
                value.substring(0, insertPos) +
                UPLOADINGTEXT +
                value.substring(insertPos);
            this.textarea.setSelectionRange(
                insertPos + UPLOADINGTEXT.length,
                insertPos + UPLOADINGTEXT.length
            );
        } else {
            const start = this.textarea.selectionStart;
            this.insertText(UPLOADINGTEXT, start, start);
        }

        try {
            const FORMDATA = new FormData();
            FORMDATA.append("file", file);

            const response = await fetch(ENDPOINT, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                    "XSRF-TOKEN": XSRF_TOKEN,
                },
                body: FORMDATA,
                credentials: "include",
            });

            if (!response.ok) {
                throw new Error(
                    `Upload failed: ${response.status} ${response.statusText}`
                );
            }

            const RESULT_RESPONSE = await response.json();

            if (
                (RESULT_RESPONSE.hasOwnProperty("path") &&
                    RESULT_RESPONSE.hasOwnProperty("filename") &&
                    RESULT_RESPONSE.path === undefined &&
                    RESULT_RESPONSE.path === null) ||
                (RESULT_RESPONSE.filename === undefined &&
                    RESULT_RESPONSE.filename === null)
            ) {
                throw new Error(
                    "Response missing URL field and filename (for ALT text) for uploaded file image. Please try again."
                );
            }

            const MARKDOWN = `![${RESULT_RESPONSE.filename}](${RESULT_RESPONSE.path})\n`;
            if (isAtStart === false) {
                MARKDOWN = `\n${MARKDOWN}`;
            }

            // Replace uploading status text with image markdown
            if (insertPos !== null) {
                this.textarea.value = this.textarea.value.replace(
                    UPLOADINGTEXT,
                    MARKDOWN
                );
                this.textarea.setSelectionRange(
                    insertPos + MARKDOWN.length,
                    insertPos + MARKDOWN.length
                );
            } else {
                const start = this.textarea.selectionStart;
                this.insertText(MARKDOWN, start, start);
            }

            // Update preview content
            this.updatePreview();
        } catch (error) {
            console.error(error);

            // Delete uploading status text from editor
            if (isAtStart === false) {
                UPLOADINGTEXT = `\n${UPLOADINGTEXT}`;
            }
            const start = this.textarea.selectionStart;
            this.textarea.value = this.textarea.value.replace(
                UPLOADINGTEXT,
                ""
            );
            this.textarea.setSelectionRange(start, start);

            this.showUploadStatus(
                "error",
                `Upload image failed: ${error.message}`
            );
        } finally {
            // Remove 'POINTER-EVENT: NONE' style
            this.textarea.style.pointerEvents = "auto";

            // Delete or replace uploading status text from editor to empty string
            if (isAtStart === false) {
                UPLOADINGTEXT = `\n${UPLOADINGTEXT}`;
            }
            const start = this.textarea.selectionStart;
            this.textarea.value = this.textarea.value.replace(
                UPLOADINGTEXT,
                ""
            );
            this.textarea.setSelectionRange(start, start);
        }
    }

    showUploadStatus(type, message) {
        // Create upload status element
        const STATUS_ELEMENT = document.createElement("div");
        STATUS_ELEMENT.id = "upload-status-texteditor";
        STATUS_ELEMENT.className = `upload-status ${type}`;
        STATUS_ELEMENT.textContent = message;
        // Append upload status element to body
        document.body.appendChild(STATUS_ELEMENT);

        // Show upload status element
        STATUS_ELEMENT.classList.add("show");

        setTimeout(() => {
            STATUS_ELEMENT.classList.remove("show");
            setTimeout(() => {
                STATUS_ELEMENT.remove();
            }, 500);
        }, 5000);
    }

    updatePreview() {
        const markdown = this.textarea.value;
        const html = SHOWDOWN.makeHtml(markdown.replace(/\r\n/g, "\n").trim());
        this.previewContent.innerHTML = html;

        // Check if Highlight.js is loaded
        if (typeof hljs === "undefined") {
            console.warn(
                "Highlight.js is not loaded. Code blocks will not be highlighted."
            );
            return;
        }
        /**
         * ---------------------
         * HIGHLIGHT CODE BLOCKS
         * --------------------
         */
        document.querySelectorAll("pre code")?.forEach((block) => {
            hljs.highlightElement(block);
        });

        // Initialize line numbers
        hljs.initLineNumbersOnLoad();

        // Adding copy button to code block
        this.previewContent.querySelectorAll("pre")?.forEach((block) => {
            const CODE_ELEMENT = block.querySelector("code");

            const button = document.createElement("button");
            button.className = "copy-btn";
            button.textContent = "Copy";
            button.role = "button";
            button.type = "button";
            button.addEventListener("click", () => {
                navigator.clipboard
                    .writeText(CODE_ELEMENT.innerText)
                    .then(() => {
                        button.innerText = "Copied!";
                        setTimeout(() => (button.innerText = "Copy"), 2000);
                    });
            });
            block.appendChild(button); // Append button to code block
        });
    }

    showSlashCommands() {
        const cursorPos = this.textarea.selectionStart;
        const cursorCoords = this.getCursorCoordinates(cursorPos);

        this.slashCommandsMenu.innerHTML = `
            <h5 class="slash-commands-header" role="heading">Slash Commands / Keyboard Shortcuts</h5>
            <div class="slash-commands-list" role="list">
              <div class="slash-commands-item" role="listitem" data-command="tip">Tip</div>
              <div class="slash-commands-item" role="listitem" data-command="note">Note</div>
              <div class="slash-commands-item" role="listitem" data-command="important">Important</div>
              <div class="slash-commands-item" role="listitem" data-command="warning">Warning</div>
              <div class="slash-commands-item" role="listitem" data-command="caution">Caution</div>
            </div>
            `;

        this.slashCommandsMenu.style.display = "block";
        this.slashCommandsMenu.style.position = "absolute";
        this.slashCommandsMenu.ariaHidden = "false";

        // get viewport dimensions
        const viewport = {
            width: window.innerWidth,
            height: window.innerHeight,
            scrollX: window.pageXOffset || document.documentElement.scrollLeft,
            scrollY: window.pageYOffset || document.documentElement.scrollTop,
        };

        // Get menu dimensions setelah di-render
        const menuRect = this.slashCommandsMenu.getBoundingClientRect();
        const menuWidth = menuRect.width;
        const menuHeight = menuRect.height;

        // Get textarea bounds untuk reference
        const textareaRect = this.textarea.getBoundingClientRect();

        // Calculate optimal position
        const position = this.calculateOptimalPositionForSlashCommands({
            cursorCoords,
            menuWidth,
            menuHeight,
            viewport,
            textareaRect,
        });

        this.slashCommandsMenu.style.top = `${position.top}px`;
        this.slashCommandsMenu.style.left = `${position.left}px`;

        // Adding class
        this.slashCommandsMenu.classList.add(...position.classes);
        this.slashCommandsMenu.style.visibility = "visible";

        this.slashCommandsMenu
            .querySelectorAll(".slash-commands-item")
            .forEach((item) => {
                item.addEventListener("click", (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    this.handleSlashCommand(item.dataset.command);
                    item.removeEventListener("click", this.handleSlashCommand); // Remove event listener
                });
            });

        this.addMobileGesturesForSlashCommands();

        // If user click outside slash commands menu, hide it
        setTimeout(() => {
            const onClickOutside = (e) => {
                if (!this.slashCommandsMenu.contains(e.target)) {
                    this.hideSlashCommands();

                    // Remove all listener
                    document.removeEventListener("click", onClickOutside);
                }
            };
            document.addEventListener("click", onClickOutside);
        }, 50);
    }

    showTableGridInput() {
        const cursorPos = this.textarea.selectionStart;
        const coords = this.getCursorCoordinates(cursorPos);

        if (this.tableGridMenu) {
            this.tableGridMenu.remove();
        }

        const menu = document.createElement("div");
        menu.className = "table-grid-menu";
        menu.innerHTML = `
            <h5 class="table-grid-menu-header" role="heading">Insert Table</h5>
            <div class="table-grid-menu-content">
                <label class="__label" for="table-grid-menu-rows">Rows: <input type="number" class="__input" id="table-grid-menu-rows" min="1" max="25" value="2"></label>
                <label class="__label" for="table-grid-menu-cols">Cols: <input type="number" class="__input" id="table-grid-menu-cols" min="1" max="25" value="2"></label>
                <div class="table-grid-menu-preview" id="table-grid-menu-preview"></div>
            </div>
            <div class="table-grid-menu-footer">
                <button type="button" role="button" class="__button" id="table-grid-menu-insert">Insert Table</button>
            </div>
        `;

        // Styling & positioning
        menu.style.position = "absolute";
        menu.style.top = `${coords.top + 20}px`;
        menu.style.left = `${coords.left}px`;
        this.container.appendChild(menu);

        const rowsInput = menu.querySelector(
            'input[type="number"]#table-grid-menu-rows'
        );
        const colsInput = menu.querySelector(
            'input[type="number"]#table-grid-menu-cols'
        );
        const preview = menu.querySelector("div#table-grid-menu-preview");

        const updatePreview = () => {
            const rows = Math.min(parseInt(rowsInput.value) || 0, 25);
            const cols = Math.min(parseInt(colsInput.value) || 0, 25);
            if (rows < 1 || cols < 1) {
                preview.innerHTML = "<p style='color:red;'>Invalid input</p>";
                return;
            }

            let html =
                '<table class="table-grid-menu-preview-table" id="table-grid-menu-preview-table" role="table">';
            for (let i = 0; i < rows; i++) {
                html += "<tr>";
                for (let j = 0; j < cols; j++) {
                    html += `<td>${i === 0 ? "H" + (j + 1) : ""}</td>`;
                }
                html += "</tr>";
            }
            html += "</table>";
            preview.innerHTML = html;
        };

        rowsInput.addEventListener("input", updatePreview);
        colsInput.addEventListener("input", updatePreview);
        updatePreview();

        // Inserting value of tables grid to editor
        menu.querySelector(
            ".__button#table-grid-menu-insert"
        )?.addEventListener("click", () => {
            const rows = Math.min(parseInt(rowsInput.value), 25);
            const cols = Math.min(parseInt(colsInput.value), 25);
            if (rows < 1 || cols < 1) return;

            let markdown = "|";
            markdown += " Header |".repeat(cols);
            markdown += "\n|";
            markdown += "---|".repeat(cols);
            for (let i = 1; i < rows; i++) {
                markdown += "\n|";
                markdown += " Cell |".repeat(cols);
            }
            this.insertText(
                markdown + "\n",
                cursorPos,
                cursorPos,
                markdown.length + 1
            );
            menu.remove();
        });

        this.tableGridMenu = menu;

        // Close on click outside
        setTimeout(() => {
            const onClickOutside = (e) => {
                if (!menu.contains(e.target)) {
                    menu.remove();

                    // Remove event listener
                    document.removeEventListener("click", onClickOutside);
                    menu.querySelector(
                        ".__button#table-grid-menu-insert"
                    )?.removeEventListener("click", () => {});
                }
            };
            document.addEventListener("click", onClickOutside);
        }, 50);
    }

    calculateOptimalPositionForSlashCommands({
        cursorCoords,
        menuWidth,
        menuHeight,
        viewport,
        textareaRect,
    }) {
        const OFFSET = 10;
        const EDGE_PADDING = 20;

        let top = cursorCoords.top;
        let left = cursorCoords.left;
        const classes = [];

        const rightSpace =
            viewport.width - (cursorCoords.left - viewport.scrollX);
        const leftSpace = cursorCoords.left - viewport.scrollX;

        if (rightSpace >= menuWidth + EDGE_PADDING) {
            left = cursorCoords.left + OFFSET;
            classes.push("position-right");
        } else if (leftSpace >= menuWidth + EDGE_PADDING) {
            left = cursorCoords.left - menuWidth - OFFSET;
            classes.push("position-left");
        } else {
            const availableWidth = viewport.width - EDGE_PADDING * 2;
            const adjustedMenuWidth = Math.min(menuWidth, availableWidth);
            left = viewport.scrollX + (viewport.width - adjustedMenuWidth) / 2;
            classes.push("position-center");

            if (menuWidth > availableWidth) {
                classes.push("scale-width");
            }
        }

        const bottomSpace =
            viewport.height - (cursorCoords.top - viewport.scrollY);
        const topSpace = cursorCoords.top - viewport.scrollY;

        if (bottomSpace >= menuHeight + EDGE_PADDING) {
            top = cursorCoords.top + OFFSET;
            classes.push("position-bottom");
        } else if (topSpace >= menuHeight + EDGE_PADDING) {
            top = cursorCoords.top - menuHeight - OFFSET;
            classes.push("position-top");
        } else {
            if (bottomSpace > topSpace) {
                top = cursorCoords.top + OFFSET;
                classes.push("position-bottom", "constrain-height");
            } else {
                const availableHeight = topSpace - EDGE_PADDING;
                top = viewport.scrollY + EDGE_PADDING;
                classes.push("position-top", "constrain-height");
            }
        }

        left = Math.max(
            viewport.scrollX + EDGE_PADDING,
            Math.min(
                left,
                viewport.scrollX + viewport.width - menuWidth - EDGE_PADDING
            )
        );
        top = Math.max(
            viewport.scrollY + EDGE_PADDING,
            Math.min(
                top,
                viewport.scrollY + viewport.height - menuHeight - EDGE_PADDING
            )
        );

        return { top, left, classes };
    }

    addMobileGesturesForSlashCommands() {
        let startY = 0;
        let currentY = 0;

        this.slashCommandsMenu.addEventListener(
            "touchstart",
            (e) => {
                startY = e.touches[0].clientY;
                currentY = startY;
            },
            { passive: true }
        );

        this.slashCommandsMenu.addEventListener(
            "touchmove",
            (e) => {
                if (!startY) return;

                currentY = e.touches[0].clientY;
                const diffY = startY - currentY;

                // If user swipes up significantly, close menu
                if (diffY > 50) {
                    this.hideSlashCommands();
                }
            },
            { passive: true }
        );

        this.slashCommandsMenu.addEventListener(
            "touchend",
            () => {
                startY = 0;
                currentY = 0;
            },
            { passive: true }
        );
    }

    getCursorCoordinates(pos) {
        // Create temporary div untuk mengukur posisi text yang tepat
        const textareaRect = this.textarea.getBoundingClientRect();
        const style = getComputedStyle(this.textarea);

        // Extract styles
        const lineHeight =
            parseInt(style.lineHeight) || parseInt(style.fontSize) * 1.2;
        const paddingTop = parseInt(style.paddingTop) || 0;
        const paddingLeft = parseInt(style.paddingLeft) || 0;
        const borderLeft = parseInt(style.borderLeftWidth) || 0;
        const borderTop = parseInt(style.borderTopWidth) || 0;

        // Calculate position more accurately
        const text = this.textarea.value.substring(0, pos);
        const lines = text.split("\n");
        const currentLineIndex = lines.length - 1;
        const currentLineText = lines[currentLineIndex];

        // Estimate character width (approximation)
        const charWidth = this.estimateCharWidth();

        // Calculate coordinates
        const top =
            textareaRect.top +
            borderTop +
            paddingTop +
            currentLineIndex * lineHeight +
            window.pageYOffset;

        const left =
            textareaRect.left +
            borderLeft +
            paddingLeft +
            currentLineText.length * charWidth +
            window.pageXOffset;

        return { top, left };
    }

    estimateCharWidth() {
        if (this._cachedCharWidth) return this._cachedCharWidth;

        // Create temporary span untuk mengukur lebar karakter
        const span = document.createElement("span");
        const style = getComputedStyle(this.textarea);

        span.style.font = style.font;
        span.style.fontSize = style.fontSize;
        span.style.fontFamily = style.fontFamily;
        span.style.visibility = "hidden";
        span.style.position = "absolute";
        span.style.top = "-9999px";
        span.textContent = "M".repeat(10); // Use 'M' as it's typically the widest character

        document.body.appendChild(span);
        const width = span.offsetWidth / 10; // Average character width
        document.body.removeChild(span);

        this._cachedCharWidth = width;
        return width;
    }

    hideSlashCommands() {
        this.slashCommandsMenu.style.display = "none";
        this.slashCommandsMenu.ariaHidden = "true";
        this.slashCommandActive = false;
        this.slashCommandsMenu.className = "slash-commands-menu";
        this.slashCommandActive = false;
    }

    handleSlashCommand(command) {
        const start = this.textarea.selectionStart;
        const value = this.textarea.value;

        // Temukan awal baris saat ini
        let lineStart = start;
        while (lineStart > 0 && value[lineStart - 1] !== "\n") {
            lineStart--;
        }

        // Gantikan seluruh baris
        const newValue =
            value.substring(0, lineStart) +
            `> [!${command.toUpperCase()}]\n> ` +
            value.substring(start);

        this.textarea.value = newValue;

        // Set kursor di dalam blok
        const newCursorPos =
            lineStart + `> [!${command.toUpperCase()}]\n> `.length;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
        this.textarea.focus();
        this.updatePreview();
        this.hideSlashCommands();
    }

    handleListEnter(e) {
        const cursorPos = this.textarea.selectionStart;
        const value = this.textarea.value;
        const lines = value.substring(0, cursorPos).split("\n");
        const currentLine = lines[lines.length - 1];
        const indent = currentLine.match(/^(\s*)/)[0].length;

        // Check for list items
        const unorderedMatch = currentLine.match(/^(\s*)[-*+]\s+/);
        const orderedMatch = currentLine.match(/^(\s*)(\d+|[a-zA-Z])(\.\s+)/);
        const taskListMatch = currentLine.match(/^(\s*[-*+]\s+\[( |x)\]\s+)/);
        const romanMatch = currentLine.match(
            /^(\s*)(i{1,4}|iv|v|vi{1,3}|ix|x{1,4}|xl|l|lx{1,3}|xc|c{1,4}|cd|d|dc{1,3}|cm|m{1,4}|I{1,4}|IV|V|VI{1,3}|IX|X{1,4}|XL|L|LX{1,3}|XC|C{1,4}|CD|D|DC{1,3}|CM|M{1,4})(\.\s+)/
        );

        if (unorderedMatch || orderedMatch || taskListMatch || romanMatch) {
            e.preventDefault();
            let prefix = "";
            let newPrefix = "";

            if (unorderedMatch) {
                prefix = unorderedMatch[0];
                newPrefix = prefix;
            } else if (taskListMatch) {
                prefix = taskListMatch[0];
                newPrefix = prefix.replace(/\[( |x)\]/, "[ ]");
            } else if (romanMatch) {
                prefix = romanMatch[0];
                const prefixType = romanMatch[1];
                const counter = romanMatch[2];
                const suffix = romanMatch[3];

                // Handle roman numeral increment
                const nextRoman = this.incrementRoman(counter);
                newPrefix = prefixType + nextRoman + suffix;
            } else if (orderedMatch) {
                prefix = orderedMatch[0];
                const prefixType = orderedMatch[1];
                const counter = orderedMatch[2];
                const suffix = orderedMatch[3];

                if (/^\d+$/.test(counter)) {
                    // Numeric increment
                    newPrefix = prefixType + (parseInt(counter) + 1) + suffix;
                } else if (/^[a-z]$/.test(counter)) {
                    // Lowercase letter increment
                    newPrefix =
                        prefixType +
                        String.fromCharCode(counter.charCodeAt(0) + 1) +
                        suffix;
                } else if (/^[A-Z]$/.test(counter)) {
                    // Uppercase letter increment
                    newPrefix =
                        prefixType +
                        String.fromCharCode(counter.charCodeAt(0) + 1) +
                        suffix;
                } else {
                    newPrefix = prefix;
                }
            }

            // If line is empty, remove list item
            if (currentLine.trim() === prefix.trim()) {
                // Remove the empty list item
                const lineStart = cursorPos - currentLine.length;
                this.textarea.value =
                    value.substring(0, lineStart) + value.substring(cursorPos);
                this.textarea.setSelectionRange(lineStart, lineStart);
            }
            // Otherwise continue list
            else {
                this.insertText(
                    "\n" + newPrefix,
                    cursorPos,
                    cursorPos,
                    newPrefix.length + 1
                );
            }
        }
    }

    incrementRoman(roman) {
        // Convert roman to number first
        const num = this.romanToNumber(roman);

        // Increment and convert back to roman
        const nextNum = num + 1;

        // Determine if uppercase or lowercase
        const isUppercase = roman === roman.toUpperCase();

        return this.numberToRoman(nextNum, isUppercase);
    }

    romanToNumber(roman) {
        const romanValues = {
            i: 1,
            v: 5,
            x: 10,
            l: 50,
            c: 100,
            d: 500,
            m: 1000,
            I: 1,
            V: 5,
            X: 10,
            L: 50,
            C: 100,
            D: 500,
            M: 1000,
        };

        const normalizedRoman = roman.toLowerCase();
        let num = 0;
        let prevValue = 0;

        // Process from right to left
        for (let i = normalizedRoman.length - 1; i >= 0; i--) {
            const currentValue = romanValues[normalizedRoman[i]];

            if (currentValue < prevValue) {
                // Subtractive case (like IV = 4, IX = 9)
                num -= currentValue;
            } else {
                // Additive case
                num += currentValue;
            }

            prevValue = currentValue;
        }

        return num;
    }

    numberToRoman(num, uppercase = false) {
        if (num < 1 || num > 3999) return "";

        const romanNumerals = [
            { value: 1000, symbol: "m" },
            { value: 900, symbol: "cm" },
            { value: 500, symbol: "d" },
            { value: 400, symbol: "cd" },
            { value: 100, symbol: "c" },
            { value: 90, symbol: "xc" },
            { value: 50, symbol: "l" },
            { value: 40, symbol: "xl" },
            { value: 10, symbol: "x" },
            { value: 9, symbol: "ix" },
            { value: 5, symbol: "v" },
            { value: 4, symbol: "iv" },
            { value: 1, symbol: "i" },
        ];

        let result = "";
        for (const numeral of romanNumerals) {
            while (num >= numeral.value) {
                result += numeral.symbol;
                num -= numeral.value;
            }
        }

        return uppercase ? result.toUpperCase() : result;
    }

    setValue(value) {
        // Check if textarea property has beend declared and initialized with HTMLElement instance
        if (
            this.hasOwnProperty("textarea") &&
            (this.textarea instanceof Element ||
                this.textarea instanceof HTMLTextAreaElement)
        ) {
            this.textarea.value = value;
        } else {
            console.error(
                "Failed to set a new value of texteditor, because textarea editor property does'nt declared or initialized ELEMENT/HTMLTEXTAREAELEMENT instance."
            );
        }
    }

    getValue() {
        return this.textarea.value;
    }

    setValue(value) {
        this.textarea.value = value;
        this.updatePreview();
    }

    focus() {
        this.textarea.focus();
    }

    destroy() {
        if (this.container) {
            this.container.innerHTML = "";
        }
    }
}
