<template id="attribute-template" aria-label="Attribute template" aria-hidden="true">
    <div class="attribute-row">
        <div class="row g-3 align-items-center attribute-config flex-nowrap">
            <div class="col-4">
                <input
                    type="text"
                    class="form-control attribute-name"
                    maxlength="64"
                    pattern="^(?!id$|file_id$|file id$|created_at$|updated_at$|created at$|updated at${{ isset($except_attributes_name) ? "|{$except_attributes_name}$"  : '' }})(?!.* {2})[a-zA-Z0-9_ ]{1,64}$"
                    title="Only letters and numbers are allowed, and maximum of 64 characters."
                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                    data-bs-custom-class="custom-tooltip"
                    data-bs-title="Only letters, spaces and numbers are allowed, and maximum of 64 characters."
                    placeholder="Attribute Name"
                    aria-label="Attribute name"
                    aria-required="true"
                    required
                />
            </div>
            <div class="col-3">
                <select class="form-select attribute-type" aria-label="Attribute type" aria-required="true" required>
                    <option
                        value=""
                        selected
                        disabled
                        >
                        -- Select type --
                    </option>
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="time">Time</option>
                    <option value="datetime">DateTime</option>
                    <option value="email">Email</option>
                    <option value="url">URL</option>
                    <option value="phone">Phone</option>
                    <option value="select">Select</option>
                    <option value="textarea">Textarea</option>
                </select>
            </div>
            <div class="col-2">
                <div class="checkbox-wrapper text-nowrap">
                    <input type="checkbox" class="switch attribute-required" value="required" aria-label="Attribute required" aria-required="false" />
                    <label class="form-check-label text-primary">Required</label>
                </div>
            </div>
            <div class="col-2">
                <div class="checkbox-wrapper text-nowrap">
                    <input type="checkbox" class="switch attribute-unique" value="unique" aria-label="Attribute unique" aria-required="false" />
                    <label class="form-check-label text-primary">Unique</label>
                </div>
            </div>
            <div class="col-1">
                <div class="btn-group flex-nowrap">
                    <button
                    type="button"
                    role="button"
                    tabindex="-1"
                    class="btn btn-outline-primary btn-sm btn-icon toggle-rules"
                    title="Button: to toggle display of attribute section"
                    >
                    <i class="bi bi-gear"></i>
                    </button>
                    <button
                    type="button"
                    role="button"
                    tabindex="-1"
                    class="btn btn-outline-danger btn-sm btn-icon delete-attribute"
                    title="Button: to delete attribute section"
                    >
                    <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="attribute-rules"></div>
    </div>
</template>