@extends("layouts.main")

@section("content")

    @includeWhen($user_guide->document_type instanceof \App\Models\DocumentType, 'partials.document-type-action-menu', ['document_type' => $user_guide->document_type])

@endsection