@extends("layouts.main")

@section("content")

    @include('partials.document-type-action-menu', ["document_type" => $document_type])
        
    <div class="tile" aria-label="Tile section of reorder schema of document type {{ $document_type->name }}" aria-labelledby="tile-schema-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-schema-document-type-label">Reorder schema of document type {!! $document_type->abbr ?? $document_type->name !!}</h3>
                <small class="caption small font-italic fs-5">Displaying list attributes of document type {!! $document_type->abbr ?? $document_type->name !!} for reordering the sequence number.</small>
            </div>
        </div>
        <form action="{{ route("documents.schema.reorder", $document_type->name) }}" method="post" id="form-reorder-schema-attributes-document-type">
            @csrf

            <div class="tile-body">
                <div class="columns-container" id="columns-container" aria-label="Column list container">
                    <!-- Columns are rendered here -->
                </div>
            </div>
            <div class="tile-footer">
                <div class="button-group">
                    <button type="reset" role="button" class="btn btn-secondary btn-sm" id="btn-reset-order" title="Button: to reset attributes order for document type {{ $document_type->name }}"><i class="bi bi-dash-square fs-5"></i> Reset</button>
                    <button type="submit" role="button" class="btn btn-primary btn-sm" id="btn-save-order" title="Button: to save changes of attributes order for document type {{ $document_type->name }}" onclick="return confirm('Are you sure you want to change attributes order for this document type?')"><i class="bi bi-save fs-5"></i> Save Changes</button>
                </div>
            </div>
        </form>
    </div>
    
@endsection