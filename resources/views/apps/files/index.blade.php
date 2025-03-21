@extends("layouts.main")

@section("content")

    @if ($document_type !== null)
        @include('partials.document-type-action-menu', ['document_type' => $document_type])
    @else
        @include('partials.document-menu')
    @endif

    @can('role-access', 'Admin')
        <!-- Note for uploading files -->
        {!! note(
            "You can upload multiple files at once but the <u>duplicate files will be renamed automatically</u>. The file size limit is 20MB. And the allowed file types are PDF, PNG, JPG, JPEG, and WEBP.
            If your <span class=\"text-danger\">image faces the wrong way</span>, rotate it before you upload it to the system."
        ) !!}

        <div class="row" id="row-file-zone" aria-label="File upload zone container">
            <!-- Upload zone section -->
            <div class="col-md-12">
                <div class="tile shadow-none" id="tile-upload-file" tabindex="1" aria-label="Tile section of upload files" aria-labelledby="tile-upload-file-label">
                    <div class="tile-title-w-btn flex-wrap">  
                        <div class="tile-title flex-nowrap">
                            <h3 class="title" id="tile-upload-file-label"><i class="bi bi-upload"></i> Upload files</h3>
                            <small class="caption small font-italic fs-5">Upload files zone.</small>
                        </div>
                    </div>
                    <div class="tile-body">
                        <div class="upload-zone" id="upload-zone" aria-label="Upload zone" title="Drag and drop files here or click to browse" >
                            <input type="file" name="file[]" id="file-input" class="d-none" multiple accept="image/png, image/jpg, image/jpeg, image/webp, application/pdf" />
                            <i class="bi bi-cloud-upload text-primary mb-3" ></i>
                            <p class="mb-1">Drag and drop files here</p>
                            <p class="small text-muted mb-0">or click to browse</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="row g-3" id="row-file-list" aria-label="File list container">
        <!-- Folder List -->
        <div class="col-md-4">
            <div class="sticky-panel">
                <div class="tile shadow-none" id="tile-folder-list" tabindex="1" aria-label="Tile section of folder list" aria-labelledby="tile-folder-list-label">
                    <div class="tile-title-w-btn flex-wrap">  
                        <div class="tile-title flex-nowrap">
                            <h3 class="title" id="tile-folder-list-label"><i class="bi bi-folder"></i> List of folder</h3>
                            <small class="caption small font-italic fs-5">Displaying list of folders.</small>
                        </div>
                    </div>
                    <div class="tile-body">
                        <div class="folder-list" id="folder-list" aria-label="Folder list container">
                            <!-- Link for to main folder -->
                            <a href="{{ route('documents.files.root.index') }}" role="button" class="text-decoration-none">
                                <div class="folder-item p-3 {{ $document_type === null ? 'main-folder' : '' }}" aria-label="Main folder" title="Main folder">
                                    <div class="folder-info-wrapper d-flex align-items-center">
                                        <div class="folder-info" aria-label="Info for main folder">
                                            <div class="fw-semibold">
                                                Main
                                            </div>
                                            <div class="small text-muted">
                                                <span>Main folder.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>

                            <!-- List of folders -->
                            @foreach ($document_types as $d)
                                <a href="{{ $document_type === null ? route('documents.files.index', $d->name) : 'javascript:void(0)' }}" role="button" class="text-decoration-none">
                                    <div class="folder-item p-3 {{ $document_type !== null && $document_type->name === $d->name ? 'main-folder' : '' }}" aria-label="Folder {{ $d->name }}" title="Folder {{ $d->name }}">
                                        <div class="folder-info" aria-label="Info for folder {{ $d->name }}">
                                            <div class="fw-semibold">
                                                {{ $d->name }}
                                            </div>
                                            <div class="small text-muted">
                                                <span>Created on <time datetime="{{ $d->created_at }}">{{ date('d F Y, H:i A', strtotime($d->created_at)) }}</time>.</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- File List -->
        <div class="col-md-8">
            {!! note("If too many files are selected, it may take a little or a lot of time to process text extraction through OCR from each file.") !!}

            <div class="tile shadow-none" id="tile-file-list" tabindex="0" aria-label="Tile section of files list" aria-labelledby="tile-files-list-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-files-list-label">
                            <i class="bi bi-files"></i> 
                            List of files
                        </h3>
                        <small class="caption small font-italic fs-5">Manage and displaying a list of files.</small>
                    </div>
                </div>
                <div class="tile-body">
                    <!-- Search file section -->
                    <div class="search-file" id="search-file" aria-label="Search file container">
                        <form action="{{ $document_type === null ? route('documents.files.root.index') : route('documents.files.index', $document_type->name) }}" method="get">
                            <div class="input-group" aria-label="Input group for type file">
                                <label for="type-file" class="input-group-text">Type</label>
                                <select name="type" class="form-control" id="type-file">
                                    <option value="">all</option>
                                    <option value="png" @selected(request('type') === "png")>.png</option>
                                    <option value="jpg" @selected(request('type') === "jpg")>.jpg</option>
                                    <option value="jpeg" @selected(request('type') === "jpeg")>.jpeg</option>
                                    <option value="webp" @selected(request('type') === "webp")>.webp</option>
                                    <option value="pdf" @selected(request('type') === "pdf")>.pdf</option>
                                </select>
                            </div>

                            <div class="group w-100 d-flex gap-2 flex-nowrap">
                                <input type="search" class="form-control" name="search" placeholder="Search" value="{{ request('search') ?? '' }}">
                                <button type="submit" class="btn btn-primary" aria-label="Button: to apply filtering data" title="Button: to apply filtering data">Search</button>
                            </div>
                        </form>
                    </div>

                    @if ($document_type !== null)
                        <!-- Form for inserting file or collecting files data into metadata -->
                        <form action="{{ route('documents.data.create', $document_type->name) }}" method="get" id="form-collecting-files-data">
                    @endif

                        <!-- File list section -->
                        <div class="file-list-container" id="file-list-container" aria-label="File list container">
                            <div class="file-list" id="file-list" data-current-page="{{ $files->currentPage() }}" data-last-page="{{ $files->lastPage() }}" aria-label="File list">
                                @if ($document_type !== null)
                                    <!-- Selected counter status -->
                                    <div class="selected-counter" id="selected-counter" aria-label="Selected counter" title="Selected counter">
                                        <span>Selected file 0 out of 15 (maximum for inserting data)</span>
                                    </div>
                                @endif

                                @if ($files->isEmpty())
                                    <div class="no-file-available" id="no-file-available" aria-label="No file available" title="No file available">
                                        <div class="text-center">
                                            <h5 class="mb-3">
                                                @if (request('search'))
                                                    No file found for <mark>{{ request('search') }}</mark> @if (request('type')) with type <mark>{{ request('type') }}</mark> @endif.
                                                @elseif (request('type'))
                                                    No file found with type <mark>{{ request('type') }}</mark>.
                                                @else
                                                    No file found.
                                                @endif
                                            </h5>
                                        </div>
                                    </div>
                                @else
                                    @foreach ($files as $f)
                                        <div class="file-item p-3" aria-label="File {{ "$f->name.$f->extension" }}" title="File {{ "$f->name.$f->extension" }}">
                                            <div class="file-content">
                                                @can('role-access', 'Admin')
                                                    @if ($document_type !== null) 
                                                        <!-- Checkbox to select file and actions -->
                                                        <div class="checkbox-wrapper">
                                                            <div class="cbx">
                                                                <input type="checkbox" name="file[]" class="cbx-file-id" id="cbx-{{ $loop->index }}" value="{{ $f->encrypted_name }}">
                                                                <label for="cbx-{{ $loop->index }}"></label>
                                                                <svg fill="none" viewBox="0 0 15 14" height="14" width="15">
                                                                    <path d="M2 8.36364L6.23077 12L13 2"></path>
                                                                </svg>
                                                            </div>
                                                            
                                                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                <defs>
                                                                    <filter id="goo-{{ $loop->index }}">
                                                                    <feGaussianBlur result="blur" stdDeviation="4" in="SourceGraphic"></feGaussianBlur>
                                                                    <feColorMatrix result="goo-{{ $loop->index }}" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 22 -7" mode="matrix" in="blur"></feColorMatrix>
                                                                    <feBlend in2="goo-{{ $loop->index }}" in="SourceGraphic"></feBlend>
                                                                    </filter>
                                                                </defs>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                @endcan
            
                                                <div class="file-info" aria-label="Info for file {{ "$f->name.$f->extension" }}">
                                                    <div class="fw-semibold">
                                                        @if (request('search') || request('type'))
                                                            {!! str_replace(
                                                                [request('search'), request('type') ? ".$f->extension" : ''],
                                                                ['<mark>' . request('search') . '</mark>', request('type') ? '<mark>.' . request('type') . '</mark>' : ''],
                                                                "$f->name.$f->extension"
                                                            ) !!}
                                                        @else
                                                            {{ "$f->name.$f->extension" }}
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted">
                                                        <span>{{ format_size_file($f->size) }} - Uploaded on <time datetime="{{ $f->created_at }}">{{ date('d F Y, H:i A', strtotime($f->created_at)) }}</time>.</span>
                                                    </div>
                                                </div>
                                            </div>
    
                                            <div class="dropdown">
                                                <button class="file-browse btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Actions button for file {{ "$f->name.$f->extension" }}" title="Actions button for file {{ "$f->name.$f->extension" }}">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                            aria-label="Browse file {{ "$f->name.$f->extension" }}" title="Button: to browse file {{ "$f->name.$f->extension" }}" 
                                                            data-bs-toggle="modal" data-bs-target="#modal-files"
                                                            data-file-id="{{ $f->id }}" data-file-name="{{ $f->name }}" data-file-extension="{{ $f->extension }}" 
                                                            data-file-size="{{ format_size_file($f->size) }}"
                                                            data-file-uploaded-at="{{ date('d F Y, H:i A', strtotime($f->created_at)) }}" data-file-modified-at="{{ date('d F Y, H:i A', strtotime($f->updated_at)) }}" 
                                                            data-file-document-name="{{ $f->document_type->name ?? '' }}" data-file-document-long-name="{{ $f->document_type->long_name ?? '' }}"
                                                            aria-label="File {{ $f->name }}" title="File {{ "$f->name.$f->extension" }}">
                                                            <i class="bi bi-search fs-5"></i>Info
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ $document_type !== null && $document_type->name ? route('documents.files.preview', [$document_type->name, 'file' => $f->encrypted_name]) : route('documents.files.root.preview', ['file' => $f->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Preview file {{ $f->name }}" title="Button: to preview file {{ $f->name }}"><i class="bi bi-eye fs-5"></i> Preview</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ $document_type !== null && $document_type->name ? route('documents.files.download', [$document_type->name, 'file' => $f->encrypted_name]) : route('documents.files.root.download', ['file' => $f->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Download file {{ $f->name }}" title="Button: to download file {{ $f->name }}"><i class="bi bi-download fs-5"></i> Download</a>
                                                    </li>
                                                    @can('role-access', 'Admin')
                                                        <li>    
                                                            <a href="javascript:void(0)" role="button" class="dropdown-item" aria-label="Edit file {{ $f->name }}" 
                                                                title="Button: to edit file {{ $f->name }}" data-bs-toggle="modal" data-bs-target="#modal-files-edit"
                                                                data-file-id="{{ $f->encrypted_name }}" data-file-name="{{ $f->name }}" data-file-extension="{{ $f->extension }}"
                                                                data-file-document-id="{{ $f->document_type_id }}">
                                                                <i class="bi bi-pencil-square fs-5"></i> Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            @if ($document_type === null)
                                                                <a href="{{ route('documents.files.root.delete', ['file' => $f->encrypted_name]) }}" class="dropdown-item" 
                                                                    aria-label="Delete file {{ $f->name }}" title="Button: to delete file {{ $f->name }}"
                                                                    onclick="return confirm('Are you sure to delete this file?')">
                                                                    <i class="bi bi-trash fs-5"></i> Delete
                                                                </a>
                                                            @else
                                                                <a href="javascript:void(0);" data-file="{{ $f->encrypted_name }}" data-bs-toggle="modal"
                                                                    data-bs-target="#delete_option" role="button" class="dropdown-item" 
                                                                    aria-label="Delete file {{ $f->name }}" title="Button: to delete file {{ $f->name }}">
                                                                    <i class="bi bi-trash fs-5"></i> Delete
                                                                </a>
                                                            @endif
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <div class="text-center d-none" id="loading-files">
                                <h5 class="mb-3">Loading...</h5>
                            </div>
                        </div>

                    @can('role-access', 'Admin')
                        @if ($document_type !== null)
                            <button type="submit" role="button" class="btn btn-primary btn-sm mt-3" aria-label="Button: to collecting files data" title="Button: to collecting files data" disabled><i class="bi bi-plus-square fs-5"></i> Insert</button>
                            
                            <!-- End form -->
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Modal dialog for file -->
    <div class="modal fade" id="modal-files" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal files" aria-labelledby="modal-files-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-files-label"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="dialog-content">
                        <div class="dialog-content-metadata">
                            <div class="row meta-item g-2">
                                <span class="col-sm-2 meta-label">Name:</span>
                                <span class="col-sm-10 meta-value" id="file-name"></span>
                            </div>
                            <div class="row meta-item g-2">
                                <span class="col-sm-2 meta-label">Labeled:</span>
                                <span class="col-sm-10 meta-value" id="file-labeled"></span>
                            </div>
                            <div class="row meta-item g-2">
                                <span class="col-sm-2 meta-label">Size:</span>
                                <span class="col-sm-10 meta-value" id="file-size"></span>
                            </div>
                            <div class="row meta-item g-2">
                                <span class="col-sm-2 meta-label">Uploaded at:</span>
                                <span class="col-sm-10 meta-value" id="file-uploaded-at"></span>
                            </div>
                            <div class="row meta-item g-2">
                                <span class="col-sm-2 meta-label">Modified at:</span>
                                <span class="col-sm-10 meta-value" id="file-modified-at"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('role-access', 'Admin')
        <!-- Modal dialog for edit file -->
        <div class="modal fade" id="modal-files-edit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal edit files" aria-labelledby="modal-files-edit-label" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal-files-edit-label"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('documents.files.rename') }}" method="post">
                        @csrf
                        
                        <div class="modal-body">
                            <div class="dialog-content">
                                <div class="dialog-content-metadata">
                                    <div class="row meta-item g-2">
                                        <label for="name" class="col-sm-1 meta-label">Name:</label>
                                        <div class="col-sm-11 meta-value">
                                            <input type="text" name="name" class="form-control" id="name" maxlength="255" placeholder="Enter name">
                                        </div>
                                    </div>
                                    <div class="row meta-item g-2">
                                        <label for="document-type-id" class="col-sm-1 meta-label">Labeled:</label>
                                        <div class="col-sm-11 meta-value">
                                            <select name="document_type_id" id="document-type-id" class="form-control">
                                                <option class="text-muted" value="" selected>Choose...</option>
                                                @foreach ($document_types as $d)
                                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" role="button" class="btn btn-primary btn-sm" aria-label="Save changes file" title="Button: to save changes file"><i class="bi bi-save fs-5"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @isset($document_type)
            <div class="modal fade" id="delete_option" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal edit files" aria-labelledby="delete_option-label" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="delete_option-label"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Would you like to keep the data that attached to this file? or erase it?
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="#" class="btn btn-danger" id="erase-btn" onclick="return confirm('Are you sure to delete this file and erase all the data?')" data-url="{{ route('documents.files.delete', [$document_type->name, 'erase']) }}">Erase</a>
                            <a href="#" class="btn btn-warning" id="keep-btn" onclick="return confirm('Are you sure to delete this file?')" data-url="{{ route('documents.files.delete', [$document_type->name, 'keep']) }}">Keep</a>
                        </div>
                    </div>
                </div>
            </div>
        @endisset
    @endcan

@endsection