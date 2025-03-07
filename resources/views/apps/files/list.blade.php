@foreach ($files as $file)  
    <div class="file-item @if ($on_upload) uploaded-file-item @endif p-3" aria-label="File {{ "$file->name.$file->extension" }}" title="File {{ "$file->name.$file->extension" }}">
        <div class="file-content">
            @if ($document_type !== null) 
                <!-- Checkbox to select file and actions -->
                <div class="checkbox-wrapper">
                    <div class="cbx">
                        <input type="checkbox" name="file[]" class="cbx-file-id" id="cbx-{{ $file->id }}" value="{{ $file->encrypted_name }}">
                        <label for="cbx-{{ $file->id }}"></label>
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
    
            <div class="file-info" aria-label="Info for file {{ "$file->name.$file->extension" }}">
                <div class="fw-semibold">
                    <span>
                        @if (request('search') || request('type'))
                            {!! str_replace(
                                [request('search'), request('type') ? ".$file->extension" : ''],
                                ['<mark>' . request('search') . '</mark>', request('type') ? '<mark>.' . request('type') . '</mark>' : ''],
                                "$file->name.$file->extension"
                            ) !!}
                        @else
                            {{ "$file->name.$file->extension" }}
                        @endif
                    </span>
                </div>
                <div class="small text-muted">
                    <span>{{ format_size_file($file->size) }} - Uploaded on <time datetime="{{ $file->created_at }}">{{ date('d F Y, H:i A', strtotime($file->created_at)) }}</time>.</span>
                </div>
            </div>
        </div>
        <div class="dropdown">
            <button class="file-browse btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Actions button for file {{ "$file->name.$file->extension" }}" title="Actions button for file {{ "$file->name.$file->extension" }}">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="javascript:void(0)"
                        aria-label="Browse file {{ "$file->name.$file->extension" }}" title="Button: to browse file {{ "$file->name.$file->extension" }}" 
                        data-bs-toggle="modal" data-bs-target="#modal-files"
                        data-file-id="{{ $file->id }}" data-file-name="{{ $file->name }}" data-file-extension="{{ $file->extension }}" 
                        data-file-size="{{ format_size_file($file->size) }}"
                        data-file-uploaded-at="{{ date('d F Y, H:i A', strtotime($file->created_at)) }}" data-file-modified-at="{{ date('d F Y, H:i A', strtotime($file->updated_at)) }}" 
                        data-file-document-name="{{ $file->document_type->name ?? '' }}" data-file-document-long-name="{{ $file->document_type->long_name ?? '' }}"
                        aria-label="File {{ $file->name }}" title="File {{ "$file->name.$file->extension" }}">
                        <i class="bi bi-search fs-5"></i>Info
                    </a>
                </li>
                <li>
                    <a href="{{ $document_type !== null && $document_type->name ? route('documents.files.preview', [$document_type->name, 'file' => $file->encrypted_name]) : route('documents.files.root.preview', ['file' => $file->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Preview file {{ $file->name }}" title="Button: to preview file {{ $file->name }}"><i class="bi bi-eye fs-5"></i> Preview</a>
                </li>
                <li>
                    <a href="{{ $document_type !== null && $document_type->name ? route('documents.files.download', [$document_type->name, 'file' => $file->encrypted_name]) : route('documents.files.root.download', ['file' => $file->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Download file {{ $file->name }}" title="Button: to download file {{ $file->name }}"><i class="bi bi-download fs-5"></i> Download</a>
                </li>
                <li>    
                    <a href="javascript:void(0)" role="button" class="dropdown-item" aria-label="Edit file {{ $file->name }}" 
                        title="Button: to edit file {{ $file->name }}"
                        data-file-id="{{ $file->encrypted_name }}" data-file-name="{{ $file->name }}" data-file-extension="{{ $file->extension }}" data-file-document-id="{{ $file->document_type_id }}"
                        data-bs-toggle="modal" data-bs-target="#modal-files-edit">
                        <i class="bi bi-pencil-square fs-5"></i> Edit
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.files.delete', ['file' => $file->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Delete file {{ $file->name }}" title="Button: to delete file {{ $file->name }}" onclick="return confirm('Are you sure to delete this file?')">
                        <i class="bi bi-trash fs-5"></i> Delete
                    </a>
                </li>
            </ul>
        </div>
    </div>
@endforeach