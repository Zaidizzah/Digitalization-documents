class DocumentSchemaBuilder {
    /**
     * Class constructor
     *
     * @param {string} submissionURL The URL to which the schema will be submitted
     * @param {string} loadURL The URL from which the schema will be loaded
     * @param {string} csrf_token The CSRF token
     * @param {boolean} isModify Whether the schema is being modified
     *
     * @returns {DocumentSchemaBuilder} The DocumentSchemaBuilder instance
     */
    constructor({
        submissionURL = null,
        loadURL = null,
        loadSavedURL = null,
        csrf_token,
        isModify = false,
    }) {
        this.submissionURL = submissionURL;
        this.loadURL = loadURL;
        this.loadSavedURL = loadSavedURL;
        this.isModify = isModify;
        this.attributes = [];

        // initialis variabel to save previously successfully loaded data
        this.savedAttributes = [];

        // get csrf token
        this.CSRF_TOKEN = csrf_token;

        /**
         * Indicates whether the schema has been saved.
         *
         * @returns {boolean} True if the schema has been saved, false otherwise.
         */
        this.hasSavedSchema = false;

        this.loadSavedSchemaFromServer(this.loadSavedURL);
        this.setupEventListeners();
        this.updateNoAttributesVisibility();

        this.statusSave = {
            hasUnsavedChanges: false,
            saving: false,
            lastSaved: null,
        };

        // Schema validation rules
        this.validationRules = {
            name: {
                pattern: /^[a-zA-Z][a-zA-Z0-9 _]{0,63}$/,
                maxLength: 64,
                reservedWords: [
                    "id",
                    "created_at",
                    "updated_at",
                    "created at",
                    "updated at",
                ],
            },
            types: [
                "text",
                "number",
                "date",
                "time",
                "datetime",
                "email",
                "url",
                "phone",
                "select",
                "textarea",
            ],
        };

        this.schema_config = {
            text: {
                instructions: { type: "textarea", label: "Instructions" },
                minLength: { type: "text", label: "Minimum Length" },
                maxLength: { type: "text", label: "Maximum Length" },
                defaultValue: { type: "text", label: "Default Value" },
            },
            number: {
                instructions: { type: "textarea", label: "Instructions" },
                min: { type: "text", label: "Minimum Value" },
                max: { type: "text", label: "Maximum Value" },
                step: { type: "text", label: "Step Value" },
                defaultValue: { type: "text", label: "Default Value" },
            },
            date: {
                instructions: { type: "textarea", label: "Instructions" },
                minDate: { type: "date", label: "Minimum Date" },
                maxDate: { type: "date", label: "Maximum Date" },
                defaultValue: { type: "date", label: "Default Value" },
            },
            time: {
                instructions: { type: "textarea", label: "Instructions" },
                minTime: { type: "time", label: "Minimum Time" },
                maxTime: { type: "time", label: "Maximum Time" },
                defaultValue: { type: "time", label: "Default Value" },
            },
            datetime: {
                instructions: { type: "textarea", label: "Instructions" },
                minDateTime: {
                    type: "datetime-local",
                    label: "Minimum Date & Time",
                },
                maxDateTime: {
                    type: "datetime-local",
                    label: "Maximum Date & Time",
                },
                defaultValue: {
                    type: "datetime-local",
                    label: "Default Value",
                },
            },
            email: {
                instructions: { type: "textarea", label: "Instructions" },
                minLength: { type: "text", label: "Minimum Length" },
                maxLength: { type: "text", label: "Maximum Length" },
                defaultValue: { type: "email", label: "Default Value" },
            },
            url: {
                instructions: { type: "textarea", label: "Instructions" },
                minLength: { type: "text", label: "Minimum Length" },
                maxLength: { type: "text", label: "Maximum Length" },
                defaultValue: { type: "url", label: "Default Value" },
            },
            phone: {
                instructions: { type: "textarea", label: "Instructions" },
                defaultValue: { type: "tel", label: "Default Value" },
            },
            select: {
                instructions: { type: "textarea", label: "Instructions" },
                options: { type: "textarea", label: "Options (one per line)" },
                defaultValue: { type: "text", label: "Default Value" },
            },
            textarea: {
                instructions: { type: "textarea", label: "Instructions" },
                minLength: { type: "text", label: "Minimum Length" },
                defaultValue: { type: "text", label: "Default Value" },
            },
        };
    }

    /**
     * Loads the schema attributes from the saved schema.
     *
     * @param {string} url - The URL to fetch the schema attributes from.
     *
     * @returns {Promise<void>}
     */
    async attributeLoadHandler(url) {
        // check url is not null
        if (!url) {
            toast(
                "Invalid load URL. Please check your load URL and try again.",
                "error"
            );

            return false;
        }

        // Fetching to load/get the attributes schema to the database
        LOADER.show(true);

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.CSRF_TOKEN,
                    "XSRF-TOKEN": XSRF_TOKEN,
                },
                credentials: "include",
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to load saved schema. Please try again.`
                );
            }

            const data = await response.json();

            if (data.hasOwnProperty("success") && data.success !== true) {
                throw new Error(data.message);
            }

            toast(data.message, data.success ? "success" : "error");

            if (data.success) {
                if (this.isModify)
                    // convert object to array of attributes
                    data.schema = Object.entries(data.schema).map(
                        ([key, value]) => {
                            if (
                                !value.hasOwnProperty("type") ||
                                !value.hasOwnProperty("required") ||
                                !value.hasOwnProperty("unique") ||
                                !value.hasOwnProperty("rules")
                            ) {
                                throw new Error(
                                    "Invalid schema structure. maybe some fields are missing. Please check your schema and try again."
                                );
                            }

                            return {
                                id: value.id,
                                sequence_number: value.sequence_number,
                                name: key,
                                type: value.type,
                                required: value.required,
                                unique: value.unique,
                                rules: value.rules,
                            };
                        }
                    );

                // Check if schema structure is valid
                this.validateAttributes(data.schema);

                // save current loaded data to savedAttributes variable
                this.savedAttributes = data.schema;

                this.resetAttributes();
                this.generateSchemaUI(data.schema);

                return true;
            } else {
                return false;
            }
        } catch (error) {
            // Display error
            toast(error.message, "error");

            console.error(error);
            return false;
        } finally {
            LOADER.hide();
        }
    }

    /**
     * Submits the given schema to the server and updates the UI to show saved status.
     * @param {object} schema - The schema to be submitted.
     * @returns {Promise<void>}
     */
    async attributeSubmissionHandler(schema, url = null) {
        // check url is not null
        if (!url) {
            toast(
                "Invalid submission URL. Please check your submission URL and try again.",
                "error"
            );
            return false;
        }

        // check if schema is not empty
        if (!schema) {
            toast("No attributes defined.", "info");
            return false;
        }

        try {
            // check if schema is empty and the attributes
            this.validateAttributes(schema);

            // Fetching or submitting/saving the attributes schema to the database
            LOADER.show(true);
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.CSRF_TOKEN,
                    "XSRF-TOKEN": XSRF_TOKEN,
                },
                credentials: "include",
                body: JSON.stringify({ schema: schema }),
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to save schema attributes, details: ${data.message}. please try again.`
                );
            }

            const data = await response.json();

            if (data.hasOwnProperty("success") && data.success !== true) {
                throw new Error(data.message);
            }

            toast(data.message, data.success ? "success" : "error");

            const schemaStatus = document.getElementById("schema-status");
            if (schemaStatus)
                if (data.success) {
                    // changes the saving status
                    this.statusSave.saving = true;
                    this.statusSave.hasUnsavedChanges = false;
                    this.statusSave.lastSaved = new Date();

                    // changes the message of title tile.
                    this.updateStatusSchema();

                    if (!this.isModify) {
                        // change the status saved title
                        document.getElementById(
                            "schema-saved-status"
                        ).innerHTML =
                            '(<span class="text-success">You have saved schema.</span>)';

                        // change status saved schema to database
                        this.hasSavedSchema = true;
                    }

                    return true;
                } else {
                    return false;
                }
        } catch (error) {
            // Display the error message
            toast(`${error.message}`, "error");

            console.error(error);
            return false;
        } finally {
            LOADER.hide();
        }
    }

    /**
     * Generates the UI for the given schema attributes.
     *
     * @param {object[]} schema - The schema attributes to be generated.
     */
    generateSchemaUI(schema) {
        schema.forEach((attribute) => {
            this.addAttribute();
            const row =
                document.getElementById("attribute-lists").lastElementChild;

            // insert and set attribute id
            row.querySelector(".attribute-config").insertAdjacentHTML(
                "afterbegin",
                `<input type="hidden" class="attribute-id" aria-hidden="true" value="${attribute.id}">
                <input type="hidden" class="attribute-sequence-number" aria-hidden="true" value="${attribute.sequence_number}">`
            );

            // Set basic attributes
            const attributeName = row.querySelector(
                ".attribute-config .attribute-name"
            );
            attributeName.value = attribute.name;

            // remove name pattern exist
            attributeName.pattern = attributeName.pattern.replace(
                new RegExp(
                    `[\\$\\|](${attribute.name}|${attribute.name.replace(
                        /\s+/g,
                        "_"
                    )})`,
                    "gi"
                ),
                ""
            );

            const attributeType = row.querySelector(
                ".attribute-config .attribute-type"
            );
            attributeType.value = attribute.type;

            const attributeRequired = row.querySelector(
                ".attribute-config .attribute-required"
            );
            attributeRequired.checked = attribute.required;

            const attributeUnique = row.querySelector(
                ".attribute-config .attribute-unique"
            );
            attributeUnique.checked = attribute.unique;

            // Update rules section
            this.updateAttributeRules(
                Array.from(row.parentNode.children).indexOf(row),
                attribute.type
            );

            // Set rule values
            if (attribute.rules) {
                const rulesSection = row.querySelector(".attribute-rules");
                Object.entries(attribute.rules).forEach(([ruleName, value]) => {
                    const input = rulesSection.querySelector(
                        `[data-rule="${ruleName}"]`
                    );
                    if (input) {
                        if (input.type === "checkbox") {
                            input.checked = value;
                        } else {
                            // handle select attribute
                            if (
                                input.type === "textarea" &&
                                ruleName === "options"
                            ) {
                                if (typeof value === "string")
                                    value = value.split("\n");

                                input.value = value.join("\n");
                            } else {
                                input.value = value;
                            }
                        }
                    }
                });
            }
        });

        this.statusSave.hasUnsavedChanges = false;
        this.updateStatusSchema();
    }

    /**
     * Sets up event listeners for the schema builder UI.
     *
     * Handles the following events:
     *   - Clicking the "Add Attribute" button
     *   - Clicking the "Reset Attributes" button
     *   - Clicking the "Save Schema" button
     *   - Clicking the "Load Schema" button
     *   - Input changes in attribute name, type, required, and unique fields
     *   - Clicking the "Delete Attribute" button
     *   - Clicking the "Toggle Rules" button
     *   - Changes in attribute type field
     *
     * @returns {void}
     */
    setupEventListeners() {
        if (!this.isModify) {
            document
                .getElementById("btn-add-attribute")
                .addEventListener("click", () => {
                    this.statusSave.hasUnsavedChanges = true;
                    this.addAttribute();
                });

            document
                .getElementById("btn-reset-attributes")
                .addEventListener("click", () => {
                    if (!this.isEmptyAttributes()) {
                        if (
                            confirm(
                                "Are you sure you want to reset all attributes?"
                            )
                        ) {
                            this.resetAttributes();
                            this.statusSave.hasUnsavedChanges = false;
                            this.updateStatusSchema();
                        }
                    }
                });

            document
                .getElementById("btn-save-schema-attributes")
                .addEventListener("click", () => {
                    this.saveSchema(this.submissionURL);
                });
        }

        document
            .getElementById("btn-load-schema-attributes")
            .addEventListener("click", (e) => {
                // chek if user has saved the schema before
                if (!this.hasSavedSchema) {
                    toast(
                        "You not have any saved schema. please create a new one first.",
                        "info"
                    );
                    return;
                }

                // check this element has attribute 'data-load-url'
                if (!this.loadURL) {
                    toast(
                        "we can't find data source url to load your saved schema. please create a new one first.",
                        "info"
                    );
                    return;
                }

                this.loadSchema(this.loadURL);
            });

        const attributesList = document.getElementById("attribute-lists");

        // Listen for changes in attributes
        attributesList.addEventListener("input", (e) => {
            if (
                e.target.classList.contains("attribute-name") ||
                e.target.classList.contains("attribute-type") ||
                e.target.classList.contains("attribute-required") ||
                e.target.classList.contains("attribute-unique")
            ) {
                this.statusSave.hasUnsavedChanges = true;
                this.updateStatusSchema();
            }
        });

        if (!this.isModify)
            attributesList.addEventListener("click", (e) => {
                const row = e.target.closest(".attribute-row");
                if (!row) return;

                if (e.target.closest(".delete-attribute")) {
                    const index = Array.from(row.parentNode.children).indexOf(
                        row
                    );
                    if (
                        confirm(
                            "Are you sure you want to delete this attribute?"
                        )
                    ) {
                        this.removeAttribute(index);
                        this.updateStatusSchema();
                    }
                } else if (e.target.closest(".toggle-rules")) {
                    const rulesSection = row.querySelector(".attribute-rules");
                    rulesSection.classList.toggle("show");
                }
            });

        attributesList.addEventListener("change", (e) => {
            const row = e.target.closest(".attribute-row");
            if (!row) return;

            const index = Array.from(row.parentNode.children).indexOf(row);

            if (e.target.classList.contains("attribute-type")) {
                this.updateAttributeRules(index, e.target.value);
                this.statusSave.hasUnsavedChanges = true;
                this.updateStatusSchema();
            }
        });
    }

    /**
     * Adds a new attribute to the list.
     * @returns {void}
     */
    addAttribute() {
        const template = document.getElementById("attribute-template");
        const clone = template.content.cloneNode(true);

        // Add a unique ID to the cloned element
        const elementID = `attribute-row-${Date.now()}`;
        clone.firstElementChild.id = elementID;
        document.getElementById("attribute-lists").appendChild(clone);
        this.updateNoAttributesVisibility();

        if (this.isModify) {
            // disable toggle rules button and delete-attribute button from attribute row
            const toggleRulesButton = document.querySelector(
                `#${elementID} .toggle-rules`
            );
            const deleteAttributeButton = document.querySelector(
                `#${elementID} .delete-attribute`
            );

            toggleRulesButton.disabled = true;
            deleteAttributeButton.disabled = true;

            toggleRulesButton.parentElement.title =
                "If the status is modifyed, this button will be disabled. Please don't delete or re-disable and take action on this attribute.";
        }

        const attributeName = document.querySelector(
            `#${elementID} .attribute-name`
        );

        const existingNames = Array.from(
            document.querySelectorAll(".attribute-name")
        ).map((el) => el.value);

        attributeName.addEventListener("input", () => {
            if (existingNames.includes(attributeName.value)) {
                attributeName.setCustomValidity(
                    "Attribute name already exists."
                );
            } else {
                attributeName.setCustomValidity("");
            }

            this.statusSave.hasUnsavedChanges = true;
            this.updateStatusSchema();

            attributeName.value = this.validateAttributeNameAndReplace(
                attributeName.value
            );
        });

        attributeName.addEventListener("invalid", () => {
            attributeName.setCustomValidity(
                `${attributeName.getAttribute(
                    "data-bs-title"
                )} And check if the attribute name already exists.`
            );
        });

        INITIALIZE_TOOlTIPS();
    }

    validateAttributeName(value) {
        if (!value) throw new Error("Attribute name is required.");

        if (/^[0-9_ \-]|^(?![a-zA-Z])/.test(value))
            throw new Error(
                "Attribute name cannot start with a number, space, underscore, hyphen, or simbols."
            );

        if (/[^a-zA-Z0-9 _]/.test(value))
            throw new Error(
                "Attribute name can only contain letters, numbers, spaces, and underscores."
            );

        if (/ {2,}/.test(value))
            throw new Error(
                "Attribute name cannot contain consecutive spaces."
            );

        if (value.length > 64)
            throw new Error("Attribute name cannot exceed 64 characters.");

        if (this.validateRules.name.pattern.test(value) === false)
            throw new Error(
                "Attribute name must start with a letter. and only letters, numbers, spaces, and underscores are allowed. Must have a value between 1 to 64 characters."
            );
    }

    /**
     * Validates the attribute name by removing any disallowed characters and trimming to 64 characters.
     * If the attribute name starts with a number, underscore, or space, it is cleared.
     * @param {string} value - The value to be validated and replaced.
     * @returns {string} The validated and replaced value.
     */
    validateAttributeNameAndReplace(value) {
        if (!value) return "";

        if (/^[0-9_ \-]|^(?![a-zA-Z])/.test(value)) value = "";

        value = value.replace(/ {2,}/g, " ");

        value = value.replace(/[^a-zA-Z0-9 _]/g, "");

        return value.substring(0, 64);
    }

    /**
     * Removes the attribute at the specified index from the attribute list.
     * Updates the UI to reflect the visibility of the "no attributes" message.
     *
     * @param {number} index - The index of the attribute to be removed.
     */
    removeAttribute(index) {
        if (!this.isModify) {
            const list = document.getElementById("attribute-lists");
            // check if index is valid
            if (index < 0 || index >= list.children.length) {
                toast("Invalid attribute index. Please try again.", "error");
                return;
            }

            list.children[index].remove();

            // check if list children is empty then make attribute this.statusSave.hasUnsavedChanges to false
            if (list.children.length === 0) {
                this.statusSave.hasUnsavedChanges = false;
            } else {
                this.statusSave.hasUnsavedChanges = true;
            }

            this.updateNoAttributesVisibility();
        }
    }

    /**
     * Resets the list of schema attributes in the UI.
     *
     * Clears all attribute entries in the 'attribute-lists' container and
     * updates the visibility of the "no attributes" message accordingly.
     *
     * @returns {void}
     */
    resetAttributes() {
        document.getElementById("attribute-lists").innerHTML = "";
        this.updateNoAttributesVisibility();
    }

    /**
     * Updates the visibility of the "no attributes" message based on the current state of the schema attribute list.
     *
     * Checks if the schema attribute list is empty and sets the display property of the "no attributes" message accordingly.
     *
     * @returns {void}
     */
    updateNoAttributesVisibility() {
        const noAttributes = document.getElementById("no-attributes");
        noAttributes.style.display = this.isEmptyAttributes()
            ? "block"
            : "none";
    }

    /**
     * Updates the attribute rules UI for a specified attribute type.
     *
     * This function dynamically generates and updates the HTML elements
     * for the rules section based on the attribute type and its
     * configuration. It handles different input types and applies
     * necessary constraints and validation patterns for inputs like
     * number, email, and URL. Special cases are managed for attributes
     * with specific rules, such as min, max, or step for numbers, and
     * maxLength for emails and URLs. The function ensures that the
     * appropriate input fields are displayed and validated according to
     * the specified rules.
     *
     * @param {number} index - The index of the attribute in the list.
     * @param {string} type - The type of the attribute for which rules are being updated.
     */
    updateAttributeRules(index, type) {
        const row = document.getElementById("attribute-lists").children[index];
        row.dataset.type = type;
        const rulesSection = row.querySelector(".attribute-rules");
        rulesSection.innerHTML = "";

        if (!type || !this.schema_config[type]) return;

        const rules = this.schema_config[type];
        const container = document.createElement("div");
        container.className = "row g-3 mt-3";

        Object.entries(rules).forEach(([ruleName, ruleConfig]) => {
            const col = document.createElement("div");
            col.className = ruleConfig.type === "textarea" ? "col-12" : "col-4";

            const label = document.createElement("label");
            label.className = "form-label";
            label.textContent = ruleConfig.label;

            let input = document.createElement(
                ruleConfig.type === "textarea" ? "textarea" : "input"
            );
            input.className =
                ruleConfig.type === "textarea"
                    ? "form-control"
                    : "form-control";
            if (ruleConfig.type === "textarea") input.rows = 3;
            else input.type = ruleConfig.type;

            input.dataset.rule = ruleName;

            if (ruleName === "instructions") {
                input.title =
                    "This field is intended for instructions or rules in filling this attribute.";
            }

            if (
                type === "number" &&
                ["max", "min", "step"].includes(ruleName)
            ) {
                input.min = BigInt("-9223372036854775808");
                input.max = BigInt("9223372036854775807");
                input.title = `Please enter a value between ${input.min} and ${input.max} and fill out this field.`;
            }

            if (
                (["email", "url", "text"].includes(type) &&
                    ["maxLength", "minLength"].includes(ruleName)) ||
                (type === "number" &&
                    ["min", "max", "step", "defaultValue"].includes(ruleName))
            ) {
                const minValue = BigInt("-9223372036854775808");
                const maxValue = BigInt("9223372036854775807");

                input.type = "text";
                input.pattern = "^-?[0-9]+$";
                input.inputMode = "numeric";
                input.oninput = () => {
                    input.value = input.value.replace(/[^0-9-]/g, "");
                    if (
                        input.value.indexOf("-") > 0 ||
                        (input.value.match(/-/g) || []).length > 1
                    ) {
                        input.value = input.value.replace(/-/g, "");
                        input.value = "-" + input.value;
                    }

                    if (input.value.startsWith("0") && input.value.length > 1) {
                        input.value = input.value.replace(/^0+/, "0");
                    }

                    if (
                        type === "email" &&
                        ruleName === "maxLength" &&
                        parseInt(input.value, 10) > 254
                    ) {
                        input.value = 254;
                    }

                    if (["min", "max", "step"].includes(ruleName)) {
                        input.value = input.value.replace(/^-0/, "0");

                        if (parseInt(input.value, 10) > maxValue)
                            input.value = maxValue;
                        if (parseInt(input.value, 10) < minValue)
                            input.value = minValue;
                    } else {
                        if (input.value.startsWith("-")) input.value = "0";
                    }

                    input.setCustomValidity(
                        /[^0-9-]/.test(input.value) ? "Invalid number" : ""
                    );
                };
            }

            if (
                (type === "email" && ruleName === "maxLength") ||
                (type === "url" && ruleName === "maxLength")
            ) {
                input.required = true;
            }

            if (type === "email") {
                if (["maxLength", "defaultValue"].includes(ruleName)) {
                    input.title =
                        "Please enter a value less than or equal to 254.";
                }
                if (ruleName === "defaultValue") {
                    input.type = "email";
                    input.inputMode = "email";
                    input.minLength = 6;
                    input.maxLength = 254;
                    input.title =
                        "Please enter a valid email address, and enter a value between 6 and 254.";
                }
            }

            if (type === "url" && ruleName === "defaultValue") {
                input.type = "url";
                input.inputMode = "url";
            }

            if (type === "phone" && ruleConfig.type === "tel") {
                input.inputMode = "tel";
                input.oninput = () => {
                    input.value = input.value.replace(/[^0-9+]/g, "");
                };
            }

            if (
                type === "select" &&
                ruleName !== "instructions" &&
                ruleName !== "defaultValue"
            ) {
                input.required = true;
                input.ariaRequired = "true";
                const labelRequired = document.createElement("span");
                labelRequired.className = "text-danger";
                labelRequired.ariaLabel = "required";
                labelRequired.textContent = "*";
                label.appendChild(labelRequired);
            }

            if (["maxLength", "max"].includes(ruleName)) {
                input.ariaRequired = "true";
                input.required = true;
                const labelRequired = document.createElement("span");
                labelRequired.className = "text-danger";
                labelRequired.ariaLabel = "required";
                labelRequired.textContent = "*";
                label.appendChild(labelRequired);
            }

            col.appendChild(label);
            col.appendChild(input);

            if (type === "select" && ruleName === "defaultValue") {
                const description = document.createElement("p");
                description.className = "form-text text-muted";
                description.textContent =
                    "The default value must be a valid option!";
                col.appendChild(description);
            }

            if (type !== "select" && ruleName === "defaultValue") {
                const description = document.createElement("p");
                description.className = "form-text text-muted";
                description.textContent =
                    "If the attribute is required then the value of this input field can be left empty and the other way around.";
                col.appendChild(description);
            } else if (ruleName === "maxLength") {
                const description = document.createElement("p");
                description.className = "form-text text-muted";
                description.textContent =
                    "The maximum length of the attribute value must be filled.";
                col.appendChild(description);
            } else if (ruleName === "max") {
                const description = document.createElement("p");
                description.className = "form-text text-muted";
                description.textContent =
                    "The maximum value of the attribute value must be filled.";
                col.appendChild(description);
            }

            // add new event listener
            input.addEventListener("input", () => {
                this.statusSave.hasUnsavedChanges = true;

                this.updateStatusSchema();
            });

            container.appendChild(col);
        });

        rulesSection.appendChild(container);
        rulesSection.classList.add("show");

        if (type === "select") {
            document
                .querySelectorAll(
                    `#attribute-lists .attribute-row[data-type='${type}']`
                )
                .forEach((row) => {
                    const textarea = row.querySelector(
                        ".attribute-rules textarea[data-rule='options']"
                    );
                    const input = row.querySelector(
                        ".attribute-rules input[type='text'][data-rule='defaultValue']"
                    );

                    if (textarea && input) {
                        const validateSelectAttribute = () => {
                            if (textarea.value && input.value) {
                                const options = Array.from(
                                    textarea.value.split("\n")
                                ).map((option) => option.trim());
                                input.setCustomValidity(
                                    options.includes(input.value)
                                        ? ""
                                        : "Invalid option, input must be one of: " +
                                              options.join(", ")
                                );
                                input.pattern = options.length
                                    ? `^$|(${options.join("|")})$`
                                    : "";
                            } else {
                                input.setCustomValidity("");
                            }
                        };

                        textarea.oninput = validateSelectAttribute;
                        input.oninput = validateSelectAttribute;
                    }
                });
        }
    }

    /**
     * Updates the status display of the schema attributes.
     *
     * This function reflects the current state of the schema attributes
     * in the UI by updating the text and classes of the schema status
     * element. It indicates whether there are no attributes defined,
     * unsaved changes, or if all changes have been saved.
     */
    updateStatusSchema() {
        const schemaStatus = document.getElementById("schema-status"),
            attributesList = document.getElementById("attribute-lists");

        if (!schemaStatus) return; // Guard clause if element doesn't exist

        if (attributesList.children.length === 0) {
            schemaStatus.classList.remove(
                "text-success",
                "text-warning",
                "text-muted"
            );
            if (this.hasSavedSchema) {
                schemaStatus.classList.add("text-success");
                schemaStatus.textContent = "You have saved schema.";
            } else {
                schemaStatus.classList.add("text-muted");
                schemaStatus.textContent = "No attributes defined.";
            }
        } else if (this.statusSave.hasUnsavedChanges) {
            schemaStatus.classList.remove("text-success", "text-muted");
            schemaStatus.classList.add("text-warning");
            schemaStatus.textContent = "Unsaved changes.";
        } else {
            schemaStatus.classList.remove("text-warning", "text-muted");
            schemaStatus.classList.add("text-success");
            schemaStatus.textContent = "All changes saved.";
        }
    }

    /**
     * Collects and constructs the schema attributes from the UI.
     *
     * This function iterates over each attribute row in the UI, extracting
     * the attribute's name, type, required status, unique status, and any
     * additional rules defined for the attribute. The rules are extracted from
     * inputs within the attribute's rules section, supporting both checkbox
     * and other input types. The constructed attributes are then compiled
     * into a schema array.
     *
     * @returns {Array} An array of attribute objects representing the schema.
     */
    collectSchema() {
        const schema = [];
        const rows = document.getElementById("attribute-lists").children;

        // check if attributes is empty
        if (rows.length === 0) return schema;

        Array.from(rows).forEach((row) => {
            const attribute = {
                name: row.querySelector(".attribute-name").value || null,
                type: row.querySelector(".attribute-type").value || null,
                required: row.querySelector(".attribute-required").checked,
                unique: row.querySelector(".attribute-unique").checked,
                rules: {},
            };

            // Set attribute id if status is modify/isModify
            if (this.isModify) {
                attribute.id = row.querySelector(".attribute-id").value;
                attribute.sequence_number = row.querySelector(
                    ".attribute-sequence-number"
                ).value;
            }

            const rulesSection = row.querySelector(".attribute-rules");
            const ruleInputs = rulesSection.querySelectorAll("input, textarea");

            ruleInputs.forEach((input) => {
                const ruleName = input.dataset.rule;
                if (ruleName) {
                    if (input.type === "checkbox") {
                        attribute.rules[ruleName] = input.checked;
                    } else {
                        attribute.rules[ruleName] = input.value || null;
                    }
                }
            });

            schema.push(attribute);
        });

        return schema;
    }

    /**
     * Validates the structure of a given schema.
     *
     * This function checks a schema for required properties, name format,
     * name length, reserved words, duplicate names, valid types, and
     * valid rules for each type. It throws an error if any of these
     * checks fail.
     *
     * @param {Array} schema - The schema to be validated.
     * @throws {Error} If the schema is invalid.
     * @returns {boolean} Whether the schema is valid.
     */
    /**
     * Validates rules based on type.
     *
     * This function checks a given set of rules against the rules for a given type.
     * It throws an error if any of these checks fail.
     *
     * @param {string} type - The type of the attribute.
     * @param {Object} rules - The rules to be validated.
     * @throws {Error} If the rules are invalid.
     */
    validateAttributes(schema) {
        const attributeNames = new Set();

        if (!schema) throw new Error("Schema is not defined.");

        if (typeof schema !== "object" && typeof schema !== "array")
            throw new Error(
                "Invalid schema format, must be an array. maybe the schema is corrupted. Please try again."
            );

        return schema.every((attribute, index) => {
            // Check required properties
            if (
                !attribute.hasOwnProperty("name") ||
                !attribute.hasOwnProperty("type") ||
                !attribute.hasOwnProperty("required") ||
                !attribute.hasOwnProperty("unique") ||
                !attribute.hasOwnProperty("rules")
            ) {
                throw new Error(
                    `Attribute at index ${index} missing required properties`
                );
            }

            // Validate name
            if (!this.validationRules.name.pattern.test(attribute.name)) {
                throw new Error(
                    `Invalid attribute name format: ${attribute.name}`
                );
            }

            if (attribute.name.length > this.validationRules.name.maxLength) {
                throw new Error(`Attribute name too long: ${attribute.name}`);
            }

            if (
                this.validationRules.name.reservedWords.includes(
                    attribute.name.toLowerCase()
                )
            ) {
                throw new Error(
                    `Reserved word used as attribute name: ${attribute.name}`
                );
            }

            // Check for duplicate names
            if (attributeNames.has(attribute.name)) {
                throw new Error(`Duplicate attribute name: ${attribute.name}`);
            }
            attributeNames.add(attribute.name);

            // Validate type
            if (!this.validationRules.types.includes(attribute.type)) {
                throw new Error(`Invalid attribute type: ${attribute.type}`);
            }

            // Validate rules based on type
            if (attribute.rules) {
                try {
                    this.validateRules(
                        attribute.type,
                        attribute.rules,
                        attribute.name
                    );
                } catch (error) {
                    if (error instanceof ValidationError) {
                        this.focusErrorInput(attribute.name, error.rule);
                    }

                    throw error;
                }
            }

            return true;
        });
    }

    /**
     * Validates rules based on type.
     *
     * This function checks a given set of rules against the rules for a given type.
     * It throws an error if any of these checks fail.
     *
     * @param {string} type - The type of the attribute.
     * @param {Object} rules - The rules to be validated.
     * @param {string} attributeName - The name of the attribute (for better error messages)
     * @throws {Error} If the rules are invalid.
     * @returns {boolean} Whether the rules are valid.
     */
    validateRules(type, rules, attributeName = "Unknown") {
        const config = this.schema_config[type];
        if (!config) return false;

        // --- Common value type checks ---
        for (const [ruleName, value] of Object.entries(rules)) {
            if (!config[ruleName]) {
                throw new ValidationError(
                    `Invalid rule "${ruleName}" for "${attributeName}" (type: ${type})`,
                    ruleName
                );
            }

            if (
                ["minLength", "maxLength", "min", "max", "step"].includes(
                    ruleName
                )
            ) {
                if (
                    value !== null &&
                    value !== "" &&
                    !Number.isInteger(Number(value))
                ) {
                    throw new ValidationError(
                        `Rule "${ruleName}" on "${attributeName}" must be an integer`,
                        ruleName
                    );
                }
            }

            if (
                type === "select" &&
                ruleName === "options" &&
                (!value || value.trim() === "")
            ) {
                throw new ValidationError(
                    `"${attributeName}" must have at least one option`,
                    ruleName
                );
            }
        }

        // --- Date/Time-based validation ---
        if (["time", "date", "datetime"].includes(type)) {
            const map = {
                time: ["minTime", "maxTime"],
                date: ["minDate", "maxDate"],
                datetime: ["minDateTime", "maxDateTime"],
            };
            const [minKey, maxKey] = map[type];
            const minDate = this.parseDateValue(type, rules[minKey]);
            const maxDate = this.parseDateValue(type, rules[maxKey]);

            if (minDate && maxDate) {
                const diff = maxDate - minDate;
                if (diff <= 0) {
                    throw new ValidationError(
                        `${maxKey} must be greater than ${minKey} in "${attributeName}"`,
                        maxKey
                    );
                }
                if (type !== "date" && diff < 60000) {
                    throw new ValidationError(
                        `${maxKey} must be at least 1 minute greater than ${minKey} in "${attributeName}"`,
                        maxKey
                    );
                }
            }
        }

        // --- Numeric / length-based rules ---
        if (["number", "text", "email", "url", "textarea"].includes(type)) {
            const minRule = rules.min !== undefined ? "min" : "minLength";
            const maxRule = rules.max !== undefined ? "max" : "maxLength";

            const min = Number(rules[minRule] ?? null);
            const max = Number(rules[maxRule] ?? null);

            if (!isNaN(min) && !isNaN(max)) {
                if (min >= max) {
                    throw new ValidationError(
                        `Minimum (${min}) must be less than Maximum (${max}) in "${attributeName}"`,
                        minRule
                    );
                }
                if (max - min < 1) {
                    throw new ValidationError(
                        `Maximum (${max}) must be at least 1 greater than Minimum (${min}) in "${attributeName}"`,
                        maxRule
                    );
                }
            }
        }

        return true;
    }

    /**
     * Checks if the schema is empty (i.e., no attributes defined).
     *
     * @return {boolean} True if the schema is empty, false otherwise.
     */
    isEmptyAttributes() {
        const rows = document.getElementById("attribute-lists").children;
        return rows.length === 0;
    }

    /**
     * Loads the schema attributes from the server and updates the UI to show loaded status.
     *
     * @param {string} url - The URL to fetch the schema attributes from.
     *
     * @returns {boolean} True if the schema is loaded successfully, false otherwise.
     */
    loadSchema(url = null) {
        // check if url is not null
        if (!url) {
            toast(
                "Invalid load URL. Please check your load URL and try again.",
                "error"
            );
            return false;
        }

        // Fetching to load/get the attributes schema from the database
        return this.attributeLoadHandler(url);
    }

    async loadSavedSchemaFromServer(url = null) {
        const container = document.querySelector(
            '.tile[aria-label="Tile section of attributes list"]'
        );

        // check if url is not null
        if (url === null || url === "") {
            toast(
                "Invalid load saved URL. For now you are not able to load saved schema attributes from previous saving. Please check your load saved URL and try again.",
                "error"
            );
            return;
        }

        // check if container is not null
        if (container === null) {
            return;
        }

        const SCHEMA_STATUS_ELEMENT = document.createElement("small");

        SCHEMA_STATUS_ELEMENT.className =
            "caption small font-italic text-muted";
        SCHEMA_STATUS_ELEMENT.id = "schema-status";
        SCHEMA_STATUS_ELEMENT.textContent = "No attributes defined.";

        if (container.querySelector(".tile-title-w-btn")) {
            container
                .querySelector(".tile-title-w-btn")
                .appendChild(SCHEMA_STATUS_ELEMENT);
        } else {
            return;
        }

        LOADER.show(true);

        // Fetching to load/get the attributes schema from the database
        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.CSRF_TOKEN,
                    "XSRF-TOKEN": XSRF_TOKEN,
                },
                credentials: "include",
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to load saved schema status. Please check your load saved URL and refresh this page again.`
                );
            }

            const data = await response.json();

            if (data.hasOwnProperty("success") && data.success !== true) {
                throw new Error(data.message);
            }

            toast(data.message, data.success ? "success" : "error");

            SCHEMA_STATUS_ELEMENT.textContent = data.message;
            SCHEMA_STATUS_ELEMENT.classList.remove("text-muted");
            SCHEMA_STATUS_ELEMENT.classList.add("text-success");

            // update the hasSavedSchema attribute
            this.hasSavedSchema = true;
        } catch (error) {
            // Display error
            toast(error.message, "error");

            console.error(error);
        } finally {
            LOADER.hide();
        }
    }

    /**
     * Submits the given schema to the server and updates the UI to show saved status.
     *
     * This function first checks if the schema is empty, and if so, displays an info
     * message and returns false. If the schema is not empty, it validates the attributes
     * and if any attribute is invalid, displays an error message and returns false.
     *
     * If all attributes are valid, the function collects the schema attributes from the
     * UI and submits them to the server. It disables the save button, displays a "Saving"
     * message, and after 1.5 seconds, resets the button and message.
     *
     * @returns {boolean} True if the schema is valid and saved successfully, false otherwise.
     */
    saveSchema(url = null) {
        // check if url is not null
        if (!url) {
            toast(
                "Invalid submission URL. Please check your submission URL and try again.",
                "error"
            );
            return false;
        }

        if (this.isEmptyAttributes()) {
            toast("No attributes defined.", "info");
            return false;
        }

        const schema = this.collectSchema();

        // Fetching or submitting the attributes schema to the server
        return this.attributeSubmissionHandler(schema, url);
    }

    /**
     * Helper: parse time/date/datetime safely into Date
     */
    parseDateValue(type, value) {
        if (!value) return null;
        if (type === "time") return new Date(`1970-01-01T${value}`);
        if (type === "date") return new Date(`${value}T00:00:00`);
        return new Date(value); // datetime
    }

    /**
     * Helper: focus and highlight the input field related to a specific rule
     */
    focusErrorInput(attributeName, ruleName) {
        const attributeRow = Array.from(
            document.querySelectorAll(".attribute-row")
        ).find(
            (row) =>
                row.querySelector(".attribute-name").value === attributeName
        );

        if (!attributeRow) return;
        const input = attributeRow.querySelector(`[data-rule="${ruleName}"]`);
        if (!input) return;

        input.focus();
        input.scrollIntoView({ behavior: "smooth", block: "center" });
        input.classList.add("border", "border-danger", "invalid");

        setTimeout(() => {
            input.classList.remove("border", "border-danger", "invalid");
        }, 2000);
    }
}
