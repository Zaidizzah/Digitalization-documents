<?php

if (!function_exists('highlight')) {

    /**
     * Highlight the search term
     * 
     * @param string $text
     * @param string $search
     * @return string
     */
    function highlight(string $text, ?string $search): string
    {
        if (empty($search)) {
            return $text;
        }

        return preg_replace('/(' . preg_quote($search, '/') . ')/i', '<mark>$1</mark>', $text);
    }
}

if (!function_exists('route_check')) {

    /** 
     * Check if route is active
     * 
     * @param string|array $route
     * @return bool
     */
    function route_check(string|array ...$route): bool
    {
        return request()->routeIs(...$route);
    }
}

if (!function_exists('set_active')) {

    /**
     * Set active class for current route
     * 
     * @param string|array $route
     * @return string
     */
    function set_active(string|array ...$route): string
    {
        return request()->routeIs(...$route) ? 'active' : '';
    }
}

if (!function_exists('is_active_query')) {

    /**
     * Set active class for current route
     * 
     * @param string|array $route
     * @return string
     */
    function is_active_query(string|array ...$route): string
    {
        return request()->fullUrlIs(...$route) ? 'active' : '';
    }
}

if (!function_exists('generate_captcha')) {

    /**
     * Generate captcha
     * 
     * @return array
     */
    function generate_captcha(): array
    {
        $key = 'captcha_' . uniqid();
        $image = imagecreatetruecolor(100, 30);
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $fontPath = public_path() . '/fonts/DeJavuSans.ttf';
        $text = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);

        imagefilledrectangle($image, 0, 0, 100, 30, $backgroundColor);
        imagettftext($image, 20, 0, 10, 25, $textColor, $fontPath, $text);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return [
            'key' => $key,
            'text' => $text,
            'image' => 'data:image/png;base64,' . base64_encode($imageData),
        ];
    }
}

if (!function_exists('toast')) {

    /**
     * Display a toast message
     * 
     * @param string $message
     * @param string $type
     * @return string
     */
    function toast(string $message, string $type = 'success', bool $canHide = true, int $duration = 15000): string
    {
        $message = highlight($message, request('search'));

        $toastID = time();
        return "<div id=\"toast-{$toastID}\" class=\"toast bs-shadow {$type} fade\" role=\"alert\" aria-live=\"assertive\" aria-atomic=\"true\"" . ($canHide ? " data-bs-autohide=\"true\" data-bs-delay=\"" . $duration . "\"" : "data-bs-autohide=\"false\"") . " data-bs-duration=\"" . $duration . "\" can-hide=\"{$canHide}\">
            <div class=\"toast-header\">
                <strong class=\"me-auto\">" . ucfirst($type) . "!</strong>
                <button type=\"button\" class=\"btn-close bg-light-subtle\" data-bs-dismiss=\"toast\" aria-label=\"Close\"></button>
            </div>
            <div class=\"toast-body\">
                $message
            </div>
        </div>";
    }
}

if (!function_exists('build_resource_array')) {
    /**
     * Build an associative array representing a resource.
     *
     * This function creates an array that encapsulates various details
     * about a resource, including its title, subtitle, icon, description,
     * breadcrumb navigation, CSS stylesheets, and JavaScript files.
     *
     * @param string $title The title of the resource.
     * @param string $subtitle The subtitle of the resource.
     * @param string $icon The icon representing the resource.
     * @param string $description A brief description of the resource.
     * @param array $breadcrumb An array representing the breadcrumb trail for navigation.
     * @param array $css An array of CSS files related to the resource.
     * @param array $js An array of JavaScript files related to the resource.
     * 
     * @return array An associative array containing the resource details.
     */
    function build_resource_array(
        string $title,
        string $subtitle,
        string $icon,
        string $description,
        array $breadcrumb,
        array|null $css = null,
        array|null $js = null
    ): array {
        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'icon_page' => $icon,
            'description_page' => $description,
            'breadcrumb' => $breadcrumb,
            'css' => $css ?? [],
            'javascript' => $js ?? [],
        ];
    }
}

if (!function_exists('generate_tags')) {

    /**
     * Generate HTML tags for scripts or links based on the provided configuration.
     *
     * @param string $type The type of tags to generate ('link' for CSS, 'script' for JS).
     * @param array $tags An array of configurations for each tag.
     *                     Each configuration should be an associative array with
     *                     keys corresponding to the attributes of the tag.
     *                     For 'link', valid keys are 'href', 'rel', 'type', 'media',
     *                     'base_path', 'integrity', 'crossorigin'.
     *                     For 'script', valid keys are 'src', 'async', 'defer', 'type',
     *                     'integrity', 'crossorigin', 'base_path', 'inline'.
     *
     * @return string Returns a string of concatenated HTML tags.
     *                Returns an empty string if no valid tags are provided.
     */
    function generate_tags(string $type, ?array $tags): string
    {
        // Validate input
        if (empty($tags)) {
            return '';
        }

        // Default configuration for link (CSS)
        $linkDefaults = [
            'href' => null,       // Source CSS
            'rel' => 'stylesheet', // Default rel for stylesheet
            'type' => 'text/css', // CSS type
            'media' => 'all',     // Target media
            'base_path' => '',    // Base path for local files
            'integrity' => null,  // Resource integrity
            'crossorigin' => null, // Cross-origin settings
        ];

        // Default configuration for script
        $scriptDefaults = [
            'src' => null,        // Source script
            'async' => false,     // Async loading
            'defer' => false,     // Defer loading
            'type' => 'text/javascript', // Script type
            'integrity' => null,  // Script integrity
            'crossorigin' => null, // Cross-origin settings
            'base_path' => '',    // Base path for local scripts
            'inline' => null,     // Inline content
        ];

        // Default configuration for meta tags
        $metaDefaults = [
            'name' => null,       // Meta name
            'content' => null,    // Meta content
            'property' => null,   // Meta property
            'http-equiv' => null, // Meta http-equiv
            'charset' => null,    // Meta charset
        ];

        // Select default configuration based on type
        $defaultConfig = $type === 'link' ? $linkDefaults : ($type === 'script' ? $scriptDefaults : $metaDefaults);

        // Container for generated tags
        $generatedTags = [];

        // Process each tag configuration
        foreach ($tags as $tagConfig) {
            // Merge default config with provided configuration
            $config = array_merge($defaultConfig, $tagConfig);

            // Process link tags
            if ($type === 'link') {
                // Validate href source
                if (empty($config['href'])) {
                    continue;
                }

                // Determine URL
                $url = $config['href'];
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    // If not an absolute URL, append base path
                    $url = rtrim($config['base_path'], '/') . '/' . ltrim($url, '/');
                }

                // Prepare additional attributes
                $additionalAttributes = [];

                if ($config['integrity']) {
                    $additionalAttributes[] = sprintf('integrity="%s"', htmlspecialchars($config['integrity']));
                }

                if ($config['crossorigin']) {
                    $additionalAttributes[] = sprintf('crossorigin="%s"', htmlspecialchars($config['crossorigin']));
                }

                // Create link tag
                $generatedTags[] = sprintf(
                    '<link rel="%s" type="%s" href="%s" media="%s"%s>',
                    htmlspecialchars($config['rel']),
                    htmlspecialchars($config['type']),
                    htmlspecialchars($url),
                    htmlspecialchars($config['media']),
                    $additionalAttributes ? ' ' . implode(' ', $additionalAttributes) : ''
                );
            }
            // Process meta tags
            else if ($type === 'meta') {
                // Prepare additional attributes
                $additionalAttributes = [];

                if ($config['name']) {
                    $additionalAttributes[] = sprintf('name="%s"', htmlspecialchars($config['name']));
                } elseif ($config['property']) {
                    $additionalAttributes[] = sprintf('property="%s"', htmlspecialchars($config['property']));
                } elseif ($config['http-equiv']) {
                    $additionalAttributes[] = sprintf('http-equiv="%s"', htmlspecialchars($config['http-equiv']));
                } elseif ($config['charset']) {
                    $additionalAttributes[] = sprintf('charset="%s"', htmlspecialchars($config['charset']));
                }

                // Create meta tag
                $generatedTags[] = sprintf(
                    '<meta%s content="%s">',
                    $additionalAttributes ? ' ' . implode(' ', $additionalAttributes) : '',
                    htmlspecialchars($config['content'])
                );
            }
            // Process script tags
            else {
                // Prioritize inline script if present
                if (isset($config['inline']) && $config['inline'] !== null) {
                    $generatedTags[] = sprintf(
                        '<script type="%s">%s</script>',
                        htmlspecialchars($config['type']),
                        $config['inline']
                    );
                    continue;
                }

                // Validate script source
                if (empty($config['src'])) {
                    continue;
                }

                // Determine URL
                $url = $config['src'];
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    // If not an absolute URL, append base path
                    $url = rtrim($config['base_path'], '/') . '/' . ltrim($url, '/');
                }

                // Prepare additional attributes
                $additionalAttributes = [];

                if ($config['async']) {
                    $additionalAttributes[] = 'async';
                }

                if ($config['defer']) {
                    $additionalAttributes[] = 'defer';
                }

                if ($config['integrity']) {
                    $additionalAttributes[] = sprintf('integrity="%s"', htmlspecialchars($config['integrity']));
                }

                if ($config['crossorigin']) {
                    $additionalAttributes[] = sprintf('crossorigin="%s"', htmlspecialchars($config['crossorigin']));
                }

                // Create script tag
                $generatedTags[] = sprintf(
                    '<script type="%s" src="%s"%s></script>',
                    htmlspecialchars($config['type']),
                    htmlspecialchars($url),
                    $additionalAttributes ? ' ' . implode(' ', $additionalAttributes) : ''
                );
            }
        }

        // Return concatenated tags
        return implode("\n", $generatedTags);
    }
}

if (!function_exists('generate_month_name')) {

    /**
     * Generate month name.
     *
     * @param string|int $month
     * @return string
     */
    function generate_month_name(string|int $month)
    {
        if (empty($month)) {
            return '';
        }

        return date('F', mktime(0, 0, 0, $month, 1));
    }
}

if (!function_exists('error_validation_response_custom')) {
    /**
     * Format validation errors into a custom response format.
     *
     * @param \Illuminate\Support\MessageBag $errors
     * @return array
     */
    function error_validation_response_custom(\Illuminate\Support\MessageBag $errors): array
    {
        $formatted_errors = [];

        foreach ($errors->messages() as $key => $messages) {
            $formatted_errors[] = [
                'field' => $key,
                'messages' => $messages
            ];
        }

        return $formatted_errors;
    }
}

if (!function_exists('note')) {
    /**
     * Generate a note message.
     *
     * @param string $message
     * @return string
     */
    function note(string $message, string $title = 'Note'): string
    {
        return <<<HTML
            <div class="note-container mb-3">
                <div class="note-header">
                    {$title}
                </div>
                <div class="note-content">
                    {$message}
                </div>
            </div>
            HTML;
    }
}

if (!function_exists('is_admin')) {
    /**
     * Checks if the current authenticated user is an Admin.
     * 
     * This function simply checks the role of the current authenticated user and returns true if
     * the user is an Admin, otherwise false.
     * 
     * @return bool True if the user is an Admin, otherwise false.
     */
    function is_admin()
    {
        return auth()->user()->role === 'Admin';
    }
}

if (!function_exists('format_size_file')) {
    /**
     * Format a file size into a human-readable format.
     *
     * @param string|int $size The file size in bytes.
     * @return string The formatted file size, e.g. 1.23MB.
     */
    function format_size_file(string|int $size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((int) $size) - 1) / 3);
        return sprintf("%.2f", (int) $size / pow(1024, $factor)) . $units[$factor];
    }
}
