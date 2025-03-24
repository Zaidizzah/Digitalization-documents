<nav class="nav-container mb-3" id="document-menu">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('documents.files.root.index') }}" class="nav-link {{ set_active('documents.files.root.*') }}" title="Manage files">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 6h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V6h5.17l2 2H20v10z"/>
                </svg>
                Files
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('documents.index') }}" class="nav-link {{ set_active('documents.index') }}" title="Browse document types">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <rect x="4" y="2" width="12" height="16" rx="1" ry="1" fill="white" stroke="#333" stroke-width="1.2"/>
                    
                    <path d="M12 2 L16 6 L12 6 Z" fill="#e0e0e0" stroke="#333" stroke-width="1"/>
                    
                    <rect x="6" y="8" width="8" height="1" fill="#555"/>
                    <rect x="6" y="10" width="8" height="1" fill="#555"/>
                    <rect x="6" y="12" width="6" height="1" fill="#555"/>
                    
                    <rect x="8" y="16" width="4" height="4" fill="#4285F4" rx="1" ry="1"/>
                    <rect x="14" y="14" width="4" height="6" fill="#34A853" rx="1" ry="1"/>
                    <rect x="20" y="12" width="4" height="8" fill="#EA4335" rx="1" ry="1" opacity="0.8"/>
                </svg>
                Document Types
            </a>
        </li>
    </ul>
</nav>