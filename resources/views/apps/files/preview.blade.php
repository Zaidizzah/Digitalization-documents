@extends("layouts.main")

@section("content")

    @include('partials.document-menu')

    <div class="row flex-wrap-reverse" id="row-file-list" aria-label="File list container">
        <!-- File List -->
        <div class="col-md-8">
            <div class="tile shadow-none" id="tile-file-list" tabindex="0" aria-label="Tile section of files list" aria-labelledby="tile-files-list-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-files-list-label"><i class="bi bi-files"></i> File Preview</h3>
                        <small class="caption small font-italic fs-5">{{ $file->name.'.'.$file->extension }}</small>
                    </div>
                </div>
                <div class="tile-body">
                    <!-- File list section -->
                    <div class="file-list" id="file-list" aria-label="File list container" style="overflow-y: unset">
                        @if (strpos($file->type, 'image') === 0)
                            <img src="{{ route('files_iframe', [$file->encrypted_name, $file->name.'.'.$file->extension]) }}" alt="">
                        @else
                            <iframe src="{{ route('files_iframe', [$file->encrypted_name, $file->name.'.'.$file->extension]) }}" frameborder="0" class="w-100 h-100"></iframe>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="col-md-4">
            <div class="sticky-panel">
                <div class="tile shadow-none" id="tile-upload-file" tabindex="1" aria-label="Tile section of upload files" aria-labelledby="tile-upload-file-label">
                    <div class="tile-title-w-btn flex-wrap">  
                        <div class="tile-title flex-nowrap">
                            <h3 class="title" id="tile-upload-file-label">File Info</h3>
                        </div>
                    </div>
                    <div class="tile-body">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <td>Name</td>
                                    <td>{{ $file->name }}.{{ $file->extension }}</td>
                                </tr>
                                <tr>
                                    <td>Type</td>
                                    <td>{{ $file->type }}</td>
                                </tr>
                                <tr>
                                    <td>Size</td>
                                    <td>{{ format_size_file($file->size) }}</td>
                                </tr>
                                <tr>
                                    <td>Last Modified</td>
                                    <td>{{ $file->updated_at->format('d F Y H:i A') }}</td>
                                </tr>
                                <tr>
                                    <td>Uploaded At</td>
                                    <td>{{ $file->created_at->format('d F Y H:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection