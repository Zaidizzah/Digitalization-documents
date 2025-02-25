@extends("layouts.main")

@section("content")

    @include('partials.document-menu')

    <div class="row flex-wrap-reverse" id="row-file-list" aria-label="File list container">
        <!-- File List -->
        <div class="col-md-8">
            <div class="tile shadow-none" id="tile-file-list" tabindex="0" aria-label="Tile section of files list" aria-labelledby="tile-files-list-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-files-list-label"><i class="bi bi-files"></i> List of files</h3>
                        <small class="caption small font-italic fs-5">Manage and displaying a list of files.</small>
                    </div>
                </div>
                <div class="tile-body">
                    {{-- <div class="no-file-available" aria-label="No file available" title="No file available">
                        <div class="text-center">
                            <h5 class="mb-3">No file available.</h5>
                        </div>
                    </div> --}}

                    <div class="file-list" id="file-list" aria-label="File list" title="File list">
                        @foreach ($files as $f)
                            <div class="file-item uploaded-file-item p-3">
                                <div class="dropdown">
                                    <button class="file-browse btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#"
                                                aria-label="Browse file {{ $f->name }}.{{ $f->extension }}" title="Browse file {{ $f->name }}.{{ $f->extension }}" 
                                                data-bs-toggle="modal" data-bs-target="#modal_files"
                                                data-file-id="{{ $f->id }}" data-file-name="{{ $f->name }}" data-file-extension="{{ $f->extension }}" 
                                                data-file-size="{{ format_size_file($f->size) }}" {{-- data-file-preview-uri="{{ route('documents.files.root.preview') }}" --}}
                                                data-file-uploaded-at="{{ date('d F Y, H:i A', strtotime($f->created_at)) }}" data-file-modified-at="{{ date('d F Y, H:i A', strtotime($f->updated_at)) }}" 
                                                data-file-labeled="kk" data-file-abbr="kartu kaluarga"
                                                aria-label="File {{ $f->name }}" title="File {{ $f->name }}.{{ $f->extension }}">
                                                <i class="bi bi-search fs-5"></i>Info
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('documents.files.root.preview', $f->encrypted_name) }}" role="button" class="dropdown-item" aria-label="Preview file {{ $f->name }}" title="Button: to preview file {{ $f->name }}"><i class="bi bi-eye fs-5"></i> Preview</a>
                                        </li>
                                        <li>
                                            <a href="{{ route('documents.files.root.download', $f->encrypted_name) }}" role="button" class="dropdown-item" aria-label="Download file {{ $f->name }}" title="Button: to download file {{ $f->name }}"><i class="bi bi-download fs-5"></i> Download</a>
                                        </li>
                                        <li>    
                                            <a href="#" role="button" class="dropdown-item" aria-label="Edit file {{ $f->name }}" 
                                                title="Button: to edit file {{ $f->name }}"
                                                data-file-id="{{ $f->id }}" data-file-name="{{ $f->name }}" data-file-document="{{ $f->document_type_id }}"
                                                data-bs-toggle="modal" data-bs-target="#modal_files_edit">
                                                <i class="bi bi-pencil-square fs-5"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('documents.files.root.delete', $f->encrypted_name) }}" role="button" class="dropdown-item" aria-label="Delete file {{ $f->name }}" title="Button: to delete file {{ $f->name }}" onclick="return confirm('Are you sure to delete this file?')">
                                                <i class="bi bi-trash fs-5"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-3">
                                        <i class="bi bi-files text-primary"></i>
                                    </div>
                                    <div class="file-info" aria-label="File info" title="Size: {{ format_size_file($f->size) }} -- Uploaded at {{ date('d F Y, H:i A', strtotime($f->created_at)) }}">
                                        <div class="fw-semibold"><span>{{ $f->name }}.{{ $f->extension }}</span></div>
                                        <div class="small text-muted">
                                            <span>{{ format_size_file($f->size) }} -- Uploaded on {{ date('d F Y, H:i A', strtotime($f->created_at)) }}.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="col-md-4">
            <div class="sticky-panel">
                <!-- Note for uploading files -->
                {!! note("You can upload multiple files at once but the <u>duplicate files will be renamed automatically</u>. The file size limit is 20MB. And the allowed file types are PDF, PNG, JPG, JPEG, and WEBP.") !!}

                <div class="tile shadow-none" id="tile-upload-file" tabindex="1" aria-label="Tile section of upload files" aria-labelledby="tile-upload-file-label">
                    <div class="tile-title-w-btn flex-wrap">  
                        <div class="tile-title flex-nowrap">
                            <h3 class="title" id="tile-upload-file-label"><i class="bi bi-upload"></i> Upload files</h3>
                            <small class="caption small font-italic fs-5">Upload files zone.</small>
                        </div>
                    </div>
                    <div class="tile-body">
                        <div
                            class="upload-zone"
                            id="upload-zone"
                            aria-label="Upload zone"
                            title="Drag and drop files here or click to browse"
                        >
                            <input
                            type="file"
                            name="file[]"
                            id="file-input"
                            class="d-none"
                            multiple
                            accept="image/png, image/jpg, image/jpeg, image/webp, application/pdf"
                            />
                            <i
                            class="bi bi-cloud-upload text-primary mb-3"
                            ></i>
                            <p class="mb-1">Drag and drop files here</p>
                            <p class="small text-muted mb-0">or click to browse</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popover dialog for ${file.name} -->
    <div class="modal fade" tabindex="-1" id="modal_files">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dialog-label">Dialog Label</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body dialog-content">
                    <div class="dialog-content-metadata">
                        <div class="meta-item">
                            <span class="meta-label">Name:</span>
                            <span class="meta-value" id="name">${file.name}.${file.extension}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Labeled:</span>
                            <span class="meta-value" id="labeled"></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Size:</span>
                            <span class="meta-value" id="size">${file.size}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Uploaded at:</span>
                            <span class="meta-value" id="uploaded_at">${file.uploaded_at}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Modified at:</span>
                            <span class="meta-value" id="modified_at">${file.modified_at}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- modal edit file --}}
    <div class="modal fade" tabindex="-1" id="modal_files_edit">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dialog-label">Edit File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('documents.files.root.rename') }}" method="post">
                    @csrf
                    <div class="modal-body dialog-content">
                        <div class="dialog-content-metadata">
                            <div class="meta-item">
                                <span class="meta-label">Name:</span>
                                <input type="text" name="name" class="form-control" id="" placeholder="Enter name">
                                <input type="hidden" name="id">
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Labeled:</span>
                                <select name="document_type_id" id="" class="form-control">
                                    <option value="">None</option>
                                    @foreach ($document as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" role="button" class="btn btn-secondary" aria-label="Cancel Editing" title="Button: to Cancel Editing" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" role="button" class="btn btn-primary btn-sm" aria-label="Save changes for file ${file.name}" title="Button: to save changes for file ${file.name}"><i class="bi bi-save fs-5"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection