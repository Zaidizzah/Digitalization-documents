@extends("layouts.main")

@section("content")

    @include('partials.document-type-action-menu', ["document_type" => $document_type])

    {!! note("Name of the attribute can't be '" .implode('\', \'', explode('$|', $except_attributes_name)). "'. That name's of the attribute has been added.") !!}
    
    <form action="{{ route("documents.insert.schema", $document_type->name) }}" method="post" id="form-insert-document-type">
        @csrf

        <!-- \Attributes Section/ -->
        <div class="tile" aria-label="Tile section of attributes list" aria-labelledby="tile-attibutes-list-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-attibutes-list-label"><i class="bi bi-list"></i> Schema Attributes <span class="small" id="schema-saved-status" aria-label="Attributes list" aria-hidden="true">{!! $has_saved_schema ? '(<span class="text-success">You have saved schema.</span>)' : '' !!}</span></h3>
                    <small class="caption small font-italic fs-5">Manage schema attributes.</small>
                </div>
                <small class="caption small font-italic {{ $has_saved_schema ? 'text-success' : 'text-muted' }}" id="schema-status">{{ $has_saved_schema ? 'You have saved schema.' : 'No attributes defined.' }}</small>
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
                class="btn btn-primary btn-sm" aria-label="Button: add new attribute"><i class="bi bi-plus-circle fs-5"></i> New Attribute</button>
                <button type="button" role="button"
                id="btn-reset-attributes"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to reset all attribute."
                class="btn btn-secondary btn-sm" aria-label="Button: reset all attribute"><i class="bi bi-dash-circle fs-5"></i> Reset Attribute</button>
                <button type="button" role="button"
                id="btn-save-schema-attributes"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to save schema attributes."
                class="btn btn-primary btn-sm" aria-label="Button: save schema attributes"><i class="bi bi-save fs-5"></i> Save Schema</button>
                <button type="button" role="button"
                data-bs-toggle="tooltip" data-bs-placement="bottom"
                data-bs-custom-class="custom-tooltip" 
                data-bs-title="Button: to load previous schema attributes or get schema attributes from previous saving if available."
                id="btn-load-schema-attributes" class="btn btn-primary btn-sm" aria-label="Button: load schema attributes"><i class="bi bi-arrow-clockwise fs-5"></i> Load Schema</button>
            </div>
        </div>
        <!-- \Attributes Section/ -->

        <div class="tile" aria-label="Tile section of buttons">
            <div class="tile-body">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to insert new schema to document type {{ $document_type->name }}"><i class="bi bi-plus-square fs-5"></i> Insert</button>
            </div>
        </div>
    </form>

    @include("vendors.schemabuilder.template", ["except_attributes_name" => $except_attributes_name])

@endsection