@extends('layouts.main')

@section('content')

    @include('partials.document-type-action-menu', ['document_type', $document_type])

    {!! note("Name of the attribute can't be '" . implode('\', \'', explode('$|', $except_attributes_name)) . "'. That name's of the attribute has been added. And if the attribute type is TEXT, EMAIL, URL, NUMBER, DATE, TIME, or DATETIME make sure to Min- and Max- must be valid (e.g Max must be greater than Min, MinTime must be less than MaxTime, or MaxDatTime must be greater than MinDateTime, etc).") !!}

    <form action="{{ route("documents.update.schema", $document_type->name) }}" method="POST" id="form-modify-document-type">
        @csrf

        @method('PUT')
        <!-- \Attributes Section/ -->
        <div class="tile shadow-none" aria-label="Tile section of attributes list" aria-labelledby="tile-attibutes-list-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-attibutes-list-label"><i class="bi bi-list"></i> Schema Attributes</h3>
                    <small class="caption small font-italic fs-5">Manage schema attributes of document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
                </div>
                <small class="caption small font-italic" id="schema-status">No attributes defined.</small>
            </div>

            <div class="tile-body">
                <div class="no-attributes" id="no-attributes" aria-hidden="true" aria-label="No attributes">
                    <p class="paragraph no-attributes-text text-muted">No attributes defined.</p>
                </div>

                <div id="attribute-lists" class="attribute-lists" aria-label="Attributes list"></div>
            </div>
            <div class="tile-footer">
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
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to save changes for document type {{ $document_type->name }}"><i class="bi bi-save fs-5"></i> Save</button>
                <a href="{{ route('documents.structure', $document_type->name) }}" role="button" class="btn btn-secondary btn-sm" title="Button: to cancel this action" onclick="return confirm('Are sure you want to cancel this action?')"><i class="bi bi-dash-square fs-5"></i> Cancel</a>
            </div>
        </div>
    </form>

    @include("vendors.schemabuilder.template")

@endsection
