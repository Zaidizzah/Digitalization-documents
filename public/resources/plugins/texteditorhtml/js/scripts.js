class TextEditorHTML {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.querySelector(`#${containerId}`);
        this.options = {
            placeholder: "Type your contents here...",
            uploadEndpoint: "/api/upload",
            showSettings: false,
            showFooter: true,
            minHeight: "400px",
            ...options,
        };

        this.init();
    }

    init() {
        if (!this.container) {
            console.error(`Container with id "${this.containerId}" not found`);
            return;
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
                            <div class="tabs">
                                <button class="tab active" data-tab="write">Write</button>
                                <button class="tab" data-tab="preview">Preview</button>
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
                              <textarea class="editor-textarea" placeholder="${
                                  this.options.placeholder
                              }" style="min-height: ${
            this.options.minHeight
        };"></textarea>
                              <div class="preview-content"></div>
                          </div>

                          ${
                              this.options.showSettings
                                  ? `
                          <div class="settings-panel">
                              <label>
                                  <strong>Upload Endpoint:</strong>
                                  <input type="text" class="upload-endpoint" value="${this.options.uploadEndpoint}" placeholder="e.g., /api/upload or https://your-api.com/upload">
                              </label>
                              <small>Configure the API endpoint where images will be uploaded. The endpoint should accept multipart/form-data and return JSON with 'url' field.</small>
                          </div>
                          `
                                  : ""
                          }

                          ${
                              this.options.showFooter
                                  ? `
                          <div class="footer">
                              <p>Styling with Markdown is supported. Use ____text____ for bold/italic. Drag & drop images directly into the editor.</p>
                          </div>
                          `
                                  : ""
                          }

                          <input type="file" class="file-input" accept="image/*" multiple style="display: none;">
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
        this.uploadEndpointInput =
            this.container.querySelector(".upload-endpoint");

        // Initialize SlashCommands manu
        this.slashCommandsMenu = document.createElement("div");
        this.slashCommandsMenu.className = "slash-commands-menu";
        document.body.appendChild(this.slashCommandsMenu); // add to body
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
            btn.addEventListener("click", (e) => {
                e.preventDefault();
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

        // Click outside to close slash commands menu
        document.addEventListener("click", (e) => {
            if (
                !this.slashCommandsMenu.contains(e.target) &&
                e.target !== this.textarea
            ) {
                this.hideSlashCommands();
            }
        });
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

    handleToolbarAction(action) {
        const start = this.textarea.selectionStart;
        const end = this.textarea.selectionEnd;
        const selectedText = this.textarea.value.substring(start, end);
        let replacement = "";
        let cursorOffset = 0;
        const value = this.textarea.value;
        const currentLine = this.getCurrentLine();

        // Check if we need to create a new line for block-level elements
        const blockElements = ["header", "quote", "task-list", "image"];
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
        }

        this.insertText(replacement, start, end, cursorOffset);
    }

    isWrapped(text, prefix, suffix) {
        return text.startsWith(prefix) && text.endsWith(suffix);
    }

    getCurrentLine() {
        const cursorPos = this.textarea.selectionStart;
        const value = this.textarea.value;
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

        // if text before cursor ends with '/'
        const lastLine = textBeforeCursor.split("\n").pop();

        if (lastLine.startsWith("/")) {
            this.slashCommandActive = true;
            this.showSlashCommands();
        } else if (this.slashCommandActive) {
            this.hideSlashCommands();
            this.slashCommandActive = false;
        }
    }

    handleKeydown(e) {
        if (e.key === "/" && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            // Perbaikan: tambahkan slash jika belum ada
            const cursorPos = this.textarea.selectionStart;
            const textBeforeCursor = this.textarea.value.substring(
                0,
                cursorPos
            );

            if (!textBeforeCursor.endsWith("/")) {
                this.insertText("/", cursorPos, cursorPos);
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
        const endpoint = this.uploadEndpointInput
            ? this.uploadEndpointInput.value.trim() ||
              this.options.uploadEndpoint
            : this.options.uploadEndpoint;

        this.showUploadStatus("progress", `Uploading ${file.name}...`);

        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("filename", file.name);

            const response = await fetch(endpoint, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error(
                    `Upload failed: ${response.status} ${response.statusText}`
                );
            }

            const result = await response.json();

            if (!result.url && !result.path && !result.file_url) {
                throw new Error("Server response missing URL field");
            }

            const imageUrl =
                result.url ||
                result.path ||
                result.file_url ||
                result.data?.url;
            const altText = result.filename || file.name;

            const markdown = `![${altText}](${imageUrl})\n`;

            if (insertPos !== null) {
                const value = this.textarea.value;
                this.textarea.value =
                    value.substring(0, insertPos) +
                    markdown +
                    value.substring(insertPos);
                this.textarea.setSelectionRange(
                    insertPos + markdown.length,
                    insertPos + markdown.length
                );
            } else {
                const start = this.textarea.selectionStart;
                this.insertText(markdown, start, start);
            }

            this.updatePreview();
            this.showUploadStatus(
                "success",
                `✓ ${file.name} uploaded successfully!`
            );
        } catch (error) {
            console.error("Upload error:", error);
            this.showUploadStatus(
                "error",
                `❌ Upload failed: ${error.message}`
            );
        }
    }

    showUploadStatus(type, message) {
        const statusEl = document.getElementById("uploadStatus");
        statusEl.className = `upload-status ${type} show`;
        statusEl.textContent = message;

        setTimeout(
            () => {
                statusEl.classList.remove("show");
            },
            type === "error" ? 5000 : 3000
        );
    }

    updatePreview() {
        const markdown = this.textarea.value;
        const html = this.markdownToHtml(markdown);
        this.previewContent.innerHTML = html;
    }

    showSlashCommands() {
        const cursorPos = this.textarea.selectionStart;
        const cursorCoords = this.getCursorCoordinates(cursorPos);

        this.slashCommandsMenu.innerHTML = `
            <h5 class="slash-commands-header">Slash Commands / Keyboard Shortcuts</h5>
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
        this.slashCommandsMenu.style.top = `${cursorCoords.top + 20}px`;
        this.slashCommandsMenu.style.left = `${cursorCoords.left}px`;

        this.slashCommandsMenu
            .querySelectorAll(".slash-commands-item")
            .forEach((item) => {
                item.addEventListener("click", (e) => {
                    e.stopPropagation();
                    this.handleSlashCommand(item.dataset.command);
                });
            });
    }

    getCursorCoordinates(pos) {
        const textareaRect = this.textarea.getBoundingClientRect();
        const style = getComputedStyle(this.textarea);
        const lineHeight = parseInt(style.lineHeight);
        const paddingTop = parseInt(style.paddingTop);
        const paddingLeft = parseInt(style.paddingLeft);

        // Hitung baris dan kolom
        const text = this.textarea.value.substring(0, pos);
        const lines = text.split("\n");
        const currentLine = lines[lines.length - 1];
        const lineIndex = lines.length - 1;

        return {
            top:
                textareaRect.top +
                paddingTop +
                lineIndex * lineHeight +
                window.scrollY,
            left:
                textareaRect.left +
                paddingLeft +
                currentLine.length * 8 +
                window.scrollX,
        };
    }

    hideSlashCommands() {
        this.slashCommandsMenu.style.display = "none";
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

    markdownToHtml(markdown) {
        const lines = markdown.split("\n");
        let html = "";
        let inParagraph = false;
        let listStack = []; // { type: 'ul'|'ol'|'task', indent: number, style: string }
        let inBlockquote = false;
        let inAlert = false;
        let inCodeBlock = false;
        let alertType = "";

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            const trimmedLine = line.trim();
            const nextLine = i < lines.length - 1 ? lines[i + 1] : "";
            const indent = line.match(/^\s*/)[0].length;

            // Skip empty lines
            if (!trimmedLine) {
                if (inParagraph) {
                    html += "</p>";
                    inParagraph = false;
                }
                continue;
            }

            // Handle code blocks
            if (trimmedLine.startsWith("```")) {
                // Close any open lists
                this.closeAllLists(listStack, html);
                listStack = [];

                if (!inCodeBlock) {
                    // Start of code block
                    html += "<pre><code>";
                    inCodeBlock = true;
                } else {
                    // End of code block
                    html += "</code></pre>";
                    inCodeBlock = false;
                }
                continue;
            }

            // If inside code block, preserve original formatting
            if (inCodeBlock) {
                html += line + "\n";
                continue;
            }

            // Handle headers
            if (trimmedLine.match(/^#{1,6} /)) {
                this.closeAllLists(listStack, (content) => {
                    html += content;
                });
                listStack = [];

                if (inParagraph) {
                    html += "</p>";
                    inParagraph = false;
                }

                const level = trimmedLine.match(/^(#+)/)[1].length;
                const text = this.processInlineFormatting(
                    trimmedLine.substring(level + 1).trim()
                );
                html += `<h${level}>${text}</h${level}>`;
                continue;
            }

            // Handle alert blocks
            if (trimmedLine.startsWith("> [!") && trimmedLine.endsWith("]")) {
                this.closeAllLists(listStack, (content) => {
                    html += content;
                });
                listStack = [];

                if (inParagraph) {
                    html += "</p>";
                    inParagraph = false;
                }

                alertType = trimmedLine.match(
                    /\[!(TIP|NOTE|IMPORTANT|WARNING|CAUTION)\]/
                )[1];
                html += `<div class="alert alert-${alertType.toLowerCase()}" role="alert">
                     <div class="alert-heading"><img class="alert-heading-icon" src="https://github.githubassets.com/images/icons/emoji/${alertType.toLowerCase()}.png" width="24px" height="24px" alt="${alertType}"><h4>${alertType}</h4></div>
                     <div class="alert-content">`;
                inAlert = true;
                continue;
            }

            // Handle blockquotes
            if (trimmedLine.startsWith("> ") && !inAlert) {
                this.closeAllLists(listStack, (content) => {
                    html += content;
                });
                listStack = [];

                if (inParagraph) {
                    html += "</p>";
                    inParagraph = false;
                }

                if (!inBlockquote) {
                    html += "<blockquote>";
                    inBlockquote = true;
                }
                const blockquoteContent = this.processInlineFormatting(
                    trimmedLine.substring(2).trim()
                );
                html += blockquoteContent;
                if (!nextLine.trim().startsWith("> ")) {
                    html += "</blockquote>";
                    inBlockquote = false;
                } else {
                    html += "<br>";
                }
                continue;
            }

            // Improved list detection with better regex patterns
            const taskListMatch = /^(\s*)[-*+]\s+\[([x ])\]\s+(.*)$/.exec(line);
            const unorderedMatch = !taskListMatch
                ? /^(\s*)[-*+]\s+(.*)$/.exec(line)
                : null;

            // Separate roman numerals from alphabetical to avoid conflicts
            const romanMatch =
                /^(\s*)(i{1,4}|i{0,3}v|i{0,3}x|x{1,3}|x{0,2}l|x{0,2}c|c{1,3}|c{0,2}d|c{0,2}m|I{1,4}|I{0,3}V|I{0,3}X|X{1,3}|X{0,2}L|X{0,2}C|C{1,3}|C{0,2}D|C{0,2}M)\.\s+(.*)$/.exec(
                    line
                );
            const orderedMatch = !romanMatch
                ? /^(\s*)([0-9]+|[a-zA-Z])\.\s+(.*)$/.exec(line)
                : null;

            const isListItem =
                taskListMatch || unorderedMatch || orderedMatch || romanMatch;

            if (isListItem) {
                if (inParagraph) {
                    html += "</p>";
                    inParagraph = false;
                }

                let listType,
                    listStyle = "",
                    listContent,
                    currentIndent;

                if (taskListMatch) {
                    listType = "task";
                    currentIndent = taskListMatch[1].length;
                    const checked = taskListMatch[2] === "x";
                    listContent = this.processInlineFormatting(
                        taskListMatch[3]
                    );

                    // Manage nested lists
                    this.manageListNesting(
                        listStack,
                        listType,
                        currentIndent,
                        listStyle,
                        (content) => {
                            html += content;
                        }
                    );

                    html += `<li><input type="checkbox" disabled${
                        checked ? " checked" : ""
                    }> ${listContent}</li>`;
                } else if (unorderedMatch) {
                    listType = "ul";
                    currentIndent = unorderedMatch[1].length;
                    listContent = this.processInlineFormatting(
                        unorderedMatch[2]
                    );

                    this.manageListNesting(
                        listStack,
                        listType,
                        currentIndent,
                        listStyle,
                        (content) => {
                            html += content;
                        }
                    );

                    html += `<li>${listContent}</li>`;
                } else if (orderedMatch) {
                    listType = "ol";
                    currentIndent = orderedMatch[1].length;
                    const marker = orderedMatch[2];
                    listContent = this.processInlineFormatting(orderedMatch[3]);

                    // Determine list style based on marker
                    if (/^[0-9]+$/.test(marker)) {
                        listStyle = ' style="list-style-type: decimal"';
                    } else if (/^[a-z]$/.test(marker)) {
                        listStyle = ' style="list-style-type: lower-alpha"';
                    } else if (/^[A-Z]$/.test(marker)) {
                        listStyle = ' style="list-style-type: upper-alpha"';
                    }

                    this.manageListNesting(
                        listStack,
                        listType,
                        currentIndent,
                        listStyle,
                        (content) => {
                            html += content;
                        }
                    );

                    html += `<li>${listContent}</li>`;
                } else if (romanMatch) {
                    listType = "ol";
                    currentIndent = romanMatch[1].length;
                    const marker = romanMatch[2];
                    listContent = this.processInlineFormatting(romanMatch[3]);

                    // Determine roman numeral style
                    if (/^[ivxlcdm]+$/.test(marker)) {
                        listStyle = ' style="list-style-type: lower-roman"';
                    } else if (/^[IVXLCDM]+$/.test(marker)) {
                        listStyle = ' style="list-style-type: upper-roman"';
                    }

                    this.manageListNesting(
                        listStack,
                        listType,
                        currentIndent,
                        listStyle,
                        (content) => {
                            html += content;
                        }
                    );

                    html += `<li>${listContent}</li>`;
                }

                // Check if we need to close lists for next non-list item
                const nextLineIndent = nextLine
                    ? nextLine.match(/^\s*/)[0].length
                    : 0;
                const nextIsListItem =
                    nextLine &&
                    (/^(\s*)[-*+]\s+\[([x ])\]\s+/.test(nextLine) ||
                        /^(\s*)[-*+]\s+/.test(nextLine) ||
                        /^(\s*)([0-9]+|[a-zA-Z])\.\s+/.test(nextLine) ||
                        /^(\s*)(i{1,4}|i{0,3}v|i{0,3}x|x{1,3}|x{0,2}l|x{0,2}c|c{1,3}|c{0,2}d|c{0,2}m|I{1,4}|I{0,3}V|I{0,3}X|X{1,3}|X{0,2}L|X{0,2}C|C{1,3}|C{0,2}D|C{0,2}M)\.\s+/.test(
                            nextLine
                        ));

                if (
                    !nextLine.trim() ||
                    (!nextIsListItem && nextLineIndent <= currentIndent)
                ) {
                    // Close lists that are deeper than next line
                    while (
                        listStack.length > 0 &&
                        listStack[listStack.length - 1].indent >= nextLineIndent
                    ) {
                        const { type } = listStack.pop();
                        html +=
                            type === "ul" || type === "task"
                                ? "</ul>"
                                : "</ol>";
                    }
                }

                continue;
            } else if (listStack.length > 0) {
                // Close all lists when encountering non-list item
                this.closeAllLists(listStack, (content) => {
                    html += content;
                });
                listStack = [];
            }

            // Handle alert content
            if (inAlert) {
                let alertContent;
                if (trimmedLine.startsWith("> ")) {
                    alertContent = this.processInlineFormatting(
                        trimmedLine.substring(2)
                    );
                } else {
                    alertContent = this.processInlineFormatting(trimmedLine);
                }
                html += alertContent;

                if (!nextLine || !nextLine.trim().startsWith(">")) {
                    html += "</div></div>";
                    inAlert = false;
                } else {
                    html += "<br>";
                }
                continue;
            }

            // Handle regular text with inline formatting
            const processedLine = this.processInlineFormatting(trimmedLine);

            if (!inParagraph) {
                html += "<p>";
                inParagraph = true;
            }

            html += processedLine;

            // Add space between lines in same paragraph
            if (
                nextLine &&
                nextLine.trim() &&
                !nextLine.match(
                    /^#{1,6} |^> |^[-*+] |^[0-9]+\. |^[a-zA-Z]\. |^(i{1,4}|i{0,3}v|i{0,3}x|x{1,3}|x{0,2}l|x{0,2}c|c{1,3}|c{0,2}d|c{0,2}m|I{1,4}|I{0,3}V|I{0,3}X|X{1,3}|X{0,2}L|X{0,2}C|C{1,3}|C{0,2}D|C{0,2}M)\. |^```/i
                )
            ) {
                html += " ";
            } else {
                html += "</p>";
                inParagraph = false;
            }
        }

        // Close any open tags
        if (inParagraph) html += "</p>";
        if (inBlockquote) html += "</blockquote>";
        if (inAlert) html += "</div></div>";
        if (inCodeBlock) html += "</code></pre>";

        // Close any remaining lists
        this.closeAllLists(listStack, (content) => {
            html += content;
        });

        return html;
    }

    // Helper method for processing inline formatting
    processInlineFormatting(text) {
        return text
            .replace(/\*\*\*(.*?)\*\*\*/g, "<strong><em>$1</em></strong>")
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            .replace(/\*(.*?)\*/g, "<em>$1</em>")
            .replace(/__(.*?)__/g, "<strong>$1</strong>")
            .replace(/_(.*?)_/g, "<em>$1</em>")
            .replace(/`(.*?)`/g, "<code>$1</code>")
            .replace(
                /\[([^\]]+)\]\(([^)]+)\)/g,
                '<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>'
            )
            .replace(
                /!\[([^\]]*)\]\(([^)]+)\)/g,
                '<img src="$2" loading="lazy" alt="$1">'
            );
    }

    // Helper method for managing list nesting
    manageListNesting(
        listStack,
        listType,
        currentIndent,
        listStyle,
        htmlAppender
    ) {
        // Close deeper nested lists
        while (
            listStack.length > 0 &&
            listStack[listStack.length - 1].indent > currentIndent
        ) {
            const { type } = listStack.pop();
            htmlAppender(type === "ul" || type === "task" ? "</ul>" : "</ol>");
        }

        // Check if we need to open a new list or if we're continuing the same list
        const needNewList =
            listStack.length === 0 ||
            listStack[listStack.length - 1].indent < currentIndent ||
            listStack[listStack.length - 1].type !== listType ||
            (listType === "ol" &&
                listStack[listStack.length - 1].style !== listStyle);

        if (needNewList) {
            // Close same-level list if different type
            if (
                listStack.length > 0 &&
                listStack[listStack.length - 1].indent === currentIndent
            ) {
                const { type } = listStack.pop();
                htmlAppender(
                    type === "ul" || type === "task" ? "</ul>" : "</ol>"
                );
            }

            // Open new list
            if (listType === "ul" || listType === "task") {
                htmlAppender(
                    `<ul${listType === "task" ? ' class="task-list"' : ""}>`
                );
            } else {
                htmlAppender(`<ol${listStyle}>`);
            }

            listStack.push({
                type: listType,
                indent: currentIndent,
                style: listStyle,
            });
        }
    }

    // Helper method for closing all lists
    closeAllLists(listStack, htmlAppender) {
        while (listStack.length > 0) {
            const { type } = listStack.pop();
            htmlAppender(type === "ul" || type === "task" ? "</ul>" : "</ol>");
        }
    }
    // Helper method for processing inline formatting
    processInlineFormatting(text) {
        return text
            .replace(/\*\*\*(.*?)\*\*\*/g, "<strong><em>$1</em></strong>")
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            .replace(/\*(.*?)\*/g, "<em>$1</em>")
            .replace(/__(.*?)__/g, "<strong>$1</strong>")
            .replace(/_(.*?)_/g, "<em>$1</em>")
            .replace(/`(.*?)`/g, "<code>$1</code>")
            .replace(
                /\[([^\]]+)\]\(([^)]+)\)/g,
                '<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>'
            )
            .replace(
                /!\[([^\]]*)\]\(([^)]+)\)/g,
                '<img src="$2" loading="lazy" alt="$1">'
            );
    }

    // Helper method for managing list nesting
    manageListNesting(
        listStack,
        listType,
        currentIndent,
        listStyle,
        htmlAppender
    ) {
        // Close deeper nested lists
        while (
            listStack.length > 0 &&
            listStack[listStack.length - 1].indent > currentIndent
        ) {
            const { type } = listStack.pop();
            htmlAppender(type === "ul" || type === "task" ? "</ul>" : "</ol>");
        }

        // Check if we need to open a new list or if we're continuing the same list
        const needNewList =
            listStack.length === 0 ||
            listStack[listStack.length - 1].indent < currentIndent ||
            listStack[listStack.length - 1].type !== listType ||
            (listType === "ol" &&
                listStack[listStack.length - 1].style !== listStyle);

        if (needNewList) {
            // Close same-level list if different type
            if (
                listStack.length > 0 &&
                listStack[listStack.length - 1].indent === currentIndent
            ) {
                const { type } = listStack.pop();
                htmlAppender(
                    type === "ul" || type === "task" ? "</ul>" : "</ol>"
                );
            }

            // Open new list
            if (listType === "ul" || listType === "task") {
                htmlAppender(
                    `<ul${listType === "task" ? ' class="task-list"' : ""}>`
                );
            } else {
                htmlAppender(`<ol${listStyle}>`);
            }

            listStack.push({
                type: listType,
                indent: currentIndent,
                style: listStyle,
            });
        }
    }

    // Helper method for closing all lists
    closeAllLists(listStack, htmlAppender) {
        while (listStack.length > 0) {
            const { type } = listStack.pop();
            htmlAppender(type === "ul" || type === "task" ? "</ul>" : "</ol>");
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
