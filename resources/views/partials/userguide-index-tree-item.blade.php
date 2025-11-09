@php
    // set route to activate, edit, and delete method
    $ROUTE = [
        'switch-status' => ['route' => (route_check('userguides.index') ? 'userguides.switch-status' : 'userguides.switch-status.named')],
        'activate' => ['route' => (route_check('userguides.index') ? 'userguides.activate' : 'userguides.activate.named')],
        'deactivate' => ['route' => (route_check('userguides.index') ? 'userguides.deactivate' : 'userguides.deactivate.named')],
        'edit' => ['route' => (route_check('userguides.index') ? 'userguides.edit' : 'userguides.edit.named')],
        'delete' => ['route' => (route_check('userguides.index') ? 'userguides.destroy' : 'userguides.destroy.named')]
    ];

    foreach($ROUTE as $key => $value) {
        $ROUTE[$key]['params'] = [
            'id' => $user_guide->id,
        ];  
        if (route_check('userguides.index.named')) $ROUTE[$key]['params']['name'] = $user_guide->document_type->name;
    }
@endphp

<div class="tree-item level-{{ $level }}" role="treeitem" data-id="{{ $user_guide->id }}">
    <div class="tree-item-content">
        @if($user_guide->children->count() > 0)
            <button class="btn expand-btn" type="button" role="button" aria-controls="--tree-item-children-{{ $user_guide->id }}">
                <i class="bi bi-chevron-right"></i>
            </button>
        @else
            <div style="width: 32px;"></div>
        @endif
        
        <div class="item-icon">
            <i class="bi bi-{{ $user_guide->children->count() > 0 ? 'folder' : 'file-text' }}"></i>
        </div>
        
        <div class="item-details">
            <p class="item-title">{{ $user_guide->title }}</p>
            <p class="item-slug" {!! Str::length($user_guide->path) > 40 ? "title=\"Actual length: " . Str::length($user_guide->path) . " characters\"" : "" !!}>{{ Str::limit($user_guide->path, 40) }}</p>
            <p class="item-created-at">Created on <time datetime="{{ $user_guide->created_at }}">{{ $user_guide->created_at->format('d F Y, H:i A') }}</time></p>
        </div>
        
        <div class="item-meta">
            <span class="badge badge-{{ $user_guide->document_type !== NULL && $user_guide->document_type !== NULL ? "specific" : "general" }}">
                {{ ($user_guide->document_type !== NULL && $user_guide->document_type !== NULL) ? "Document type {$user_guide->document_type->name}" : "General" }}
            </span>
            <form action="{{ route($ROUTE[((int) $user_guide->is_active === 0 ? 'activate' : 'deactivate')]['route'], $ROUTE['activate']['params']) }}" class="d-inline form-activate-user-guide-data" data-title="{{ $user_guide->title }}" data-switch-to="{{ $user_guide->is_active ? 'Inactive' : 'Active' }}" method="post">
                @csrf

                @method('PUT')
                <button type="submit" role="button" class="badge badge-{{ $user_guide->is_active ? "active" : "inactive" }}" title="Button: to toggle visibility user guide data">
                    {{ $user_guide->is_active ? "Active" : "Inactive" }}
                </button>
            </form>
            
            <div class="action-buttons">
                <a href="{{ route('userguides.show.dynamic', $user_guide->path) }}" class="btn btn-icon btn-view" role="button" title="View the user guide content">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route($ROUTE['edit']['route'], $ROUTE['edit']['params']) }}" class="btn btn-icon btn-edit" role="button" title="Edit the user guide content">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route($ROUTE['delete']['route'], $ROUTE['delete']['params']) }}" class="d-inline form-delete-user-guide-data" data-title="{{ $user_guide->title }}" method="post">
                    @csrf

                    @method('DELETE')
                    <button class="btn btn-icon btn-delete" type="submit" role="button" title="Delete the user guide content">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    @if($user_guide->children->count() > 0)
        <div class="children-container" id="--tree-item-children-{{ $user_guide->id }}">
            @if ($user_guide->children !== NULL && $user_guide->children instanceof Illuminate\Database\Eloquent\Collection)
                @foreach($user_guide->children as $child)
                    @include('partials.userguide-index-tree-item', ['document_type' => $document_type, 'user_guide' => $child, 'level' => ($level ?? 0) + 1])
                @endforeach
            @endif
        </div>
    @endif
</div>