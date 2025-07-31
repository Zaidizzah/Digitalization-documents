<head aria-label="Head Section">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta
    http-equiv="X-UA-Compatible"
    content="ie=edge, chrome=1, edge=1"
    />
    <meta
    name="description"
    content="Digitalization Documents - Platform pengarsipan dokumen digital yang terpercaya dan aman"
    />
    <meta
    name="author"
    content="Digitalization Documents"
    />
    <meta name="keywords" content="{{ strtolower(config('app.name')) }}, digitalization documents, documents digital, digital access, documents by type, type documents">
    <link
      rel="canonical"
      href="{{ config("app.url") }}"
    />

    <meta
      property="og:site_name"
      content="Digitalization Documents"
    />
    <meta
      property="og:title"
      content="Digitalization Documents - Platform pengarsipan dokumen digital yang terpercaya dan aman"
    />
    <meta
      property="og:description"
      content="Digitalization Documents - Merupakan platform pengarsipan dokumen digital yang terstruktur dan bisa diakses dimana saja serta mudah untuk dikustomisasi sesuai kebutuhan."
    />
    <!--
    <meta
      property="og:image"
      content=""
    /> -->
    <meta
      property="og:url"
      content="{{ config("app.url") }}"
    />
    <meta
      property="og:type"
      content="website"
    />
    <meta
      property="og:locale"
      content="id_ID"
    />

    <meta
      name="robots"
      content="index, follow"
    />
    <title>{{ config('app.name') }} | {{ $title }}</title>
    
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vali-admin-master/css/main.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('resources/plugins/imgpreview/css/imgpreview.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Styles CSS for specific page -->
    @generate_tags('link', $css)
    <!-- Styles CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('resources/apps/styles.css') }}">
</head>