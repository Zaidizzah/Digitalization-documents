@extends("layouts.main")

@section("content")

    {!! note("Name of the attribute can't be 'id', 'file_id', 'file id', 'created at', 'created_at', 'updated at', or 'updated_at'. That name's of the attribute has been added.") !!}

    <form action="{{ route("documents.store") }}" method="POST" id="form-document-type" enctype="multipart/form-data">
        <div class="tile" aria-label="Tile section of users" aria-labelledby="tile-users-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-users-label"><i class="bi bi-file-earmark-plus"></i> {{ $subtitle }}</h3>
                    <small class="caption small font-italic fs-5">Manage document types.</small>
                </div>
            </div>
            <div class="tile-body">
                @csrf

                <div class="form-group row g-1 mb-3">
                    <label for="name" class="form-label col-sm-3">Name<span aria-label="required" class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control" id="name"
                        minlength="1"
                        maxlength="64"
                        pattern="^(?!.* {2})[a-zA-Z][a-zA-Z0-9_ ]{0,63}$"
                        title="Only letters and numbers are allowed, and maximum of 64 characters."
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-custom-class="custom-tooltip"
                        data-bs-title="The name must start with a letter, and only letters, spaces and numbers are allowed, and maximum of 64 characters."
                        value="{{ old('name') }}" aria-label="Name" aria-required="true" autofocus required>
                        <p class="form-text text-muted">Fill the abbreviation name of the document type.</p>
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="long_name" class="form-label col-sm-3">Long Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="long_name" class="form-control" id="long_name" value="{{ old('long_name') }}" aria-label="Long Name" aria-required="false">
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="description" class="form-label col-sm-3">Description</label>
                    <div class="col-sm-9">
                        <textarea name="description" class="form-control" id="description" rows="3" autocapitalize="sentences" aria-label="Description" aria-required="false">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>  
        </div>
            
        <!-- \Attributes Section/ -->
        <div class="tile" aria-label="Tile section of attributes list" aria-labelledby="tile-attibutes-list-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-attibutes-list-label"><i class="bi bi-list"></i> Schema Attributes</h3>
                    <small class="caption small font-italic fs-5">Manage schema attributes.</small>
                </div>
            </div>
            <div class="tile-body">
                <div class="no-attributes" id="no-attributes" aria-hidden="true" aria-label="No attributes">
                    <p class="paragraph no-attributes-text text-muted">No attributes defined.</p>
                </div>

                <div id="attribute-lists" class="attribute-lists" aria-label="Attributes list"></div>
            </div>
            <div class="tile-footer">
                <button type="button" role="button" 
                id="btn-add-attribute"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to add new attribute."
                class="btn btn-primary btn-sm"><i class="bi bi-plus-circle fs-5"></i> New Attribute</button>
                <button type="button" role="button"
                id="btn-reset-attributes"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to reset all attribute."
                class="btn btn-secondary btn-sm"><i class="bi bi-dash-circle fs-5"></i> Reset Attribute</button>
                <button type="button" role="button"
                id="btn-save-schema-attributes"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to save schema attributes."
                class="btn btn-primary btn-sm"><i class="bi bi-save fs-5"></i> Save Schema</button>
                <button type="button" role="button"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to load previous schema attributes or get schema attributes from previous saving if available."
                id="btn-load-schema-attributes" class="btn btn-primary btn-sm"><i class="bi bi-arrow-clockwise fs-5"></i> Load Schema</button>
            </div>
        </div>
        <!-- \Attributes Section/ -->

        <div class="tile" aria-label="Tile section of buttons">
            <div class="tile-body">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to create new document type"><i class="bi bi-plus-square fs-5"></i> Create</button>
                <button type="reset" role="button" class="btn btn-secondary btn-sm" title="Button: to reset form"><i class="bi bi-dash-square fs-5"></i> Reset</button>
            </div>
        </div>
    </form>

    @include("vendors.schemabuilder.template")

@endsection
