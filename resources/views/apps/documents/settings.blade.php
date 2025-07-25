@extends("layouts.main")

@section("content")

    @include('partials.document-type-action-menu', ["document_type" => $document_type])

    <!-- action choises -->
    <div class="tile" aria-label="Tile section of action options for document type {{ $document_type->name }}" aria-labelledby="tile-action-options-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-action-options-document-type-label">Action options for document type {!! $document_type->abbr ?? $document_type->name !!}</abbr></h3>
                <small class="caption small font-italic fs-5">Action options for modifiying document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
            </div>
        </div>
        <form action="{{ route("documents.update", $document_type->name) }}" method="post">
            <div class="tile-body">
                @csrf

                @method('PUT')
                <div class="form-group row g-1 mb-3">
                    <label for="name" class="form-label col-sm-3">Name<span aria-label="required" class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control" id="name"
                        minlength="1"
                        maxlength="64"
                        pattern="^[a-zA-Z][a-zA-Z0-9_ ]{0,63}$"
                        title="Only letters and numbers are allowed, and maximum of 64 characters."
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-custom-class="custom-tooltip"
                        data-bs-title="The name must start with a letter, and only letters, spaces and numbers are allowed, and maximum of 64 characters."
                        value="{{ $document_type->name }}" aria-label="Name" aria-required="true" required>
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="long_name" class="form-label col-sm-3">Long Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="long_name" class="form-control" value="{{ $document_type->long_name }}" aria-label="Long Name" aria-required="false">
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="description" class="form-label col-sm-3">Description</label>
                    <div class="col-sm-9">
                        <textarea name="description" class="form-control" rows="3" autocapitalize="sentences" aria-label="Description" aria-required="false">{{ $document_type->description }}</textarea>
                    </div>
                </div>
            </div>
            <div class="tile-footer">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to save changes for document type {{ $document_type->name }}" onclick="return confirm('Are you sure you want to change this document type?')"><i class="bi bi-save fs-5"></i>Save</button>
            </div>
        </form>
    </div>

    <!-- Delete data or document type -->
    <div class="tile" aria-label="Tile section of delete data or document type {{ $document_type->name }}" aria-labelledby="tile-delete-data-or-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-delete-data-or-document-type-label">Delete data or document type {!! $document_type->abbr ?? $document_type->name !!}</abbr></h3>
                <small class="caption small font-italic fs-5">Delete data or document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
            </div>
        </div>
        <div class="tile-body">
            <form action="{{ route("documents.data.delete.all", $document_type->name) }}" class="form-delete-all d-inline" method="post" data-id="{{ $document_type->id }}" data-name="{{ $document_type->name }}">
                @csrf

                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" title="Button: to empty the document type or delete all data in document type '{{ $document_type->name }}'">Empty the document type {!! $document_type->abbr ?? $document_type->name !!}</button>
            </form>
            <form action="{{ route("documents.delete", $document_type->id) }}" class="form-delete d-inline" method="post" data-id="{{ $document_type->id }}" data-name="{{ $document_type->name }}">
                @csrf

                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" role="button" title="Button: to delete document type '{{ $document_type->name }}'">Delete document type {!! $document_type->abbr ?? $document_type->name !!}</button>
            </form>
        </div>
    </div>

@endsection