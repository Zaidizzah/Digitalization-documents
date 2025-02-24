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

@endsection