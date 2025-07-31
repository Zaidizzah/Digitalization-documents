<!DOCTYPE html>
<html lang="en">
    <!-- Including Head Section & Essential CSS -->
    @include("partials.head", ["title" => $title, "css" => $css ?? []])

    <body class="app" aria-label="Main Navigation">
        <!-- Including Header Section -->
        @include("partials.header")

        <!-- Including Sidebar Section -->
        @include("partials.sidebar", ['on_user_guide' => $on_user_guide ?? false])

        <!-- Main Content -->
        <main class="app-content">

            <!-- Including Breadcrumb Section -->
            @include("partials.breadcrumb", ["subtitle" => $subtitle, "icon_page" => $icon_page, "description_page" => $description_page, "breadcrumb" => $breadcrumb])
            
            <!-- Including Content Section -->
            @yield("content")

            <!-- Including alert Section -->
            @includeWhen($errors->any() || session()->has('message'), "partials.alert")
        </main>

        <!-- Including Footer Section & Essential JS -->
        @include("partials.footer", ["javascript" => $javascript ?? []])
    </body>
</html>