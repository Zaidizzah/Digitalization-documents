<nav class="nav-container mb-3" id="document-menu">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('settings.index') }}" class="nav-link {{ set_active('settings.*') }}" title="Manage application config variables">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.93-3.34a.5.5 0 0 0-.6-.22l-2.39.96a7.07 7.07 0 0 0-1.63-.94l-.36-2.53A.5.5 0 0 0 14 2h-4a.5.5 0 0 0-.5.42l-.36 2.53c-.57.2-1.11.46-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.1 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.04.3-.06.61-.06.94s.02.64.06.94L2.22 14.48a.5.5 0 0 0-.12.64l1.93 3.34c.14.25.44.35.6.22l2.39-.96c.52.48 1.06.74 1.63.94l.36 2.53A.5.5 0 0 0 10 22h4a.5.5 0 0 0 .5-.42l.36-2.53c.57-.2 1.11-.46 1.63-.94l2.39.96c.16.13.46.03.6-.22l1.93-3.34a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                </svg>
                Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('userguides.index') }}" class="nav-link {{ set_active('userguides.index', 'userguides.create', 'userguides.edit') }}" title="User Guides">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    <path d="M21 6V4c0-.6-.4-1-1-1h-1v3h2zm-3-3H6c-.6 0-1 .4-1 1v2h13V3zm0 4H5v10c0 .6.4 1 1 1h12c.6 0 1-.4 1-1V7z" opacity="0.3"/>
                </svg>
                User Guides
            </a>
        </li>
    </ul>
</nav>