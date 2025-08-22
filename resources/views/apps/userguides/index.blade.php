@extends('layouts.main')

@section('content')

    @include('partials.setting-menu')
    
    <!-- Tree items for user guides data list -->
    <div class="user-guides-trees" id="--user-guides-trees" role="region" aria-labelledby="--user-guides-trees-title" aria-describedby="--user-guides-trees-subtitle">
        <div class="user-guides-trees-header">
            <div class="header-content">
                <h2 id="--user-guides-trees-title">User Guides Management</h1>
                <small id="--user-guides-trees-subtitle">Manage your user guides and documentation</small>
            </div>
    
        
            <a
                href="{{ route('userguides.create') }}"
                class="btn btn-primary btn-sm"
                type="button"
                role="button"
                title="Button: to add new user guide content"
            >
                <i class="bi bi-plus"></i>
                Add Guide
            </a>
        </div>

        <div class="user-guides-trees-content">
            @if ($user_guides->isNotEmpty()) 
                <div class="tree-view" role="tree">
                    @foreach($user_guides as $guide)
                        @include('partials.userguide-index-tree-item', ['user_guide' => $guide, 'level' => 0])
                    @endforeach
                </div>
            @else
                <p style="text-align: center; color: #586069; background-color: #f8fafc; padding: 20px; margin-bottom: 0;">No data found</p>
            @endif
        </div>

        @if ($user_guides->hasPages())
            <div class="user-guides-trees-footer">
                {{ $user_guides->onEachSide(2)->links('vendors.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection