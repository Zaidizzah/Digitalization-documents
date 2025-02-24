<nav class="nav-container mb-3" id="document-menu">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('documents.files.index') }}" class="nav-link {{ set_active('documents.files.index') }}" aria-label="Manage files" title="Manage files">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V6h5.17l2 2H20v10z"/>
                </svg>
                Files
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('documents.index') }}" class="nav-link {{ set_active('documents.index') }}" aria-label="Browse document types" title="Browse document types">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/>
                    <path d="M8 12h8v2H8zm0 4h8v2H8zm0-8h3v2H8z"/>
                </svg>
                Document Types
            </a>
        </li>
    </ul>
</nav>