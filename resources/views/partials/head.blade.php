<head aria-label="Head Section">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="{{ config('app.name') }}">
    <meta name="description" content="Manage and make your documents digital">
    <meta name="keywords" content="{{ strtolower(config('app.name')) }}, digitalization documents, documents digital, digital access, documents by type, type documents">
    <title>{{ config('app.name') }} | {{ $title }}</title>
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vali-admin-master/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('resources/plugins/imgpreview/css/imgpreview.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Styles CSS for specific page -->
    @generate_tags('link', $css)
    <!-- Styles CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('resources/apps/main/css/styles.css') }}">
</head>