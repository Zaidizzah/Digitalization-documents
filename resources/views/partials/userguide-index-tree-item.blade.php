@php
    // If url variable has a value and has been declared, then adding next url value to evaluate new url for the list of children
    if (isset($url) && $url !== "") $url .= "/{$user_guide->slug}"; else $url = "{$user_guide->slug}";
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
            <div class="item-title">{{ $user_guide->title }}</div>
            <div class="item-slug">{{ $user_guide->slug }}</div>
        </div>
        
        <div class="item-meta">
            <span class="badge badge-{{ $user_guide->document_type_id !== NULL ? 'specific' : 'general' }}">
                {{ ($user_guide->document_type !== NULL && $user_guide->document_type->isNotEmpty()) ? "Document type {$user_guide->document_type->name}" : 'General' }}
            </span>
            <form action="{{ route('userguides.activate', $user_guide->id) }}" class="d-inline form-activate-user-guide-data" data-title="{{ $user_guide->title }}" data-switch-to="{{ $user_guide->is_active ? 'Active' : 'Inactive' }}" method="post">
                @csrf

                @method('PUT')
                <button type="submit" role="button" class="badge badge-{{ $user_guide->is_active ? 'active' : 'inactive' }}" title="Button: to toggle visibility user guide data">
                    {{ $user_guide->is_active ? 'Active' : 'Inactive' }}
                </button>
            </form>
            
            <div class="action-buttons">
                <a href="{{ route('userguides.show.dynamic', $url) }}" class="btn btn-icon btn-view" role="button" title="View the user guide content">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="{{ route('userguides.edit', $user_guide->id) }}" class="btn btn-icon btn-edit" role="button" title="Edit the user guide content">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('userguides.destroy', $user_guide->id) }}" class="d-inline form-delete-user-guide-data" data-title="{{ $user_guide->title }}" method="post">
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
                    @include('partials.userguide-index-tree-item', ['user_guide' => $child, 'level' => ($level ?? 0) + 1, 'url' => $url])
                @endforeach
            @endif
        </div>
    @endif
</div>