<nav class="nav-container mb-3" id="document-menu">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('documents.browse', $document_type->name) }}" class="nav-link {{ set_active('documents.browse') }}" aria-label="Browse data of documents {{ $document_type->name }}" title="Browse data of documents {{ $document_type->name }}">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 3h18v18H3V3zm16 16V5H5v14h14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"/>
                </svg>
                Browse
            </a>
        </li>
        @can('role-access', 'Admin')
            <li class="nav-item">
                <a href="{{ route('documents.structure', $document_type->name) }}" class="nav-link {{ set_active('documents.structure', 'documents.insert.schema.page', 'documents.edit.schema') }}" aria-label="Structure of documents {{ $document_type->name }}" title="Structure of documents {{ $document_type->name }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    Structure
                </a>
            </li>
        @endcan
        <li class="nav-item">
            <a href="{{ route('documents.files.index', $document_type->name) }}" class="nav-link {{ set_active('documents.files.*') }}" aria-label="Manage files for documents {{ $document_type->name }}" title="Manage files for documents {{ $document_type->name }}">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V6h5.17l2 2H20v10z"/>
                </svg>
                Files
            </a>
        </li>
        @can('role-access', 'Admin')
            <li class="nav-item">
                <a href="{{ route("documents.data.create", $document_type->name) }}" class="nav-link {{ set_active('documents.data.create') }}" aria-label="Insert data for documents {{ $document_type->name }}" title="Insert data for documents {{ $document_type->name }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 5h2v3h3v2h-3v3h-2v-3H9v-2h3V8z"/>
                    </svg>
                    Insert
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('documents.settings', $document_type->name) }}" class="nav-link {{ set_active('documents.settings') }}" aria-label="Settings or configuration of documents {{ $document_type->name }}" title="Settings or configuration of documents {{ $document_type->name }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.93-3.34a.5.5 0 0 0-.6-.22l-2.39.96a7.07 7.07 0 0 0-1.63-.94l-.36-2.53A.5.5 0 0 0 14 2h-4a.5.5 0 0 0-.5.42l-.36 2.53c-.57.2-1.11.46-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.1 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.04.3-.06.61-.06.94s.02.64.06.94L2.22 14.48a.5.5 0 0 0-.12.64l1.93 3.34c.14.25.44.35.6.22l2.39-.96c.52.48 1.06.74 1.63.94l.36 2.53A.5.5 0 0 0 10 22h4a.5.5 0 0 0 .5-.42l.36-2.53c.57-.2 1.11-.46 1.63-.94l2.39.96c.16.13.46.03.6-.22l1.93-3.34a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                    Settings
                </a>
            </li>
            <li class="nav-item">
                <div class="dropdown">
                    <a href="javascript:void(0)" class="nav-link" data-bs-toggle="dropdown" aria-label="Import data of documents {{ $document_type->name }}" title="Import data of documents {{ $document_type->name }}">
                        <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-11v6h2v-6h-2zm0-4v2h2V7h-2z"/>
                        </svg>
                        Import
                    </a>
                    <div class="dropdown-menu border border-primary p-2" style="min-width: 320px">
                        <p>Import from excel: .xlsx, .xls, and .csv. Make sure all columns are in the correct order. Column No, Attached File, Created At, and Updated At will not be affected. Important: Do not include headings in your file!</p>
                        <form action="{{ route('documents.import', $document_type->name) }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="input-group">
                                <input type="file" name="data" required id="" class="form-control" accept=".xlsx, .xls, .csv">
                                <button class="btn btn-primary" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </li>
        @endcan
        <li class="nav-item">
            <div class="dropdown">
                <a href="javascript:void(0)" class="nav-link" data-bs-toggle="dropdown" aria-label="Export data of documents {{ $document_type->name }}" title="Export data of documents {{ $document_type->name }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M13 8V2H7v6H2l8 8 8-8h-5zM0 18h20v2H0v-2z"/>
                    </svg>
                    Export
                </a>
                <div class="dropdown-menu border border-primary">
                    <a href="{{ route('documents.export', [$document_type->name, 'format' => 'xlsx']) }}" class="dropdown-item">Excel</a>
                    <a href="{{ route('documents.export', [$document_type->name, 'format' => 'csv']) }}" class="dropdown-item">CSV</a>
                    <a href="{{ route('documents.export', [$document_type->name, 'format' => 'pdf']) }}" class="dropdown-item">PDF</a>
                </div>
            </div>
        </li>
    </ul>
</nav>