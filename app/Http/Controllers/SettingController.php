<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct() {}

    /**
     * Display a listing of the resource for user guide page.
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function index(Request $request)
    {
        $resources = build_resource_array(
            // List of data for the page
            'Configuration / Settings',
            'Configuration / Settings',
            '<i class="bi bi-gear"></i> ',
            'A page for configuring the application varibles system.',
            [
                'Dashboard' => route('dashboard.index'),
                'Settings' => route('settings.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ]
            ]
        );

        return view('apps.settings.index', $resources);
    }

    public function user_guide(Request $request)
    {
        $resources = build_resource_array(
            // List of data for the page
            'User Guide',
            'User Guide',
            '<i class="bi bi-code-square"></i> ',
            'A page for configuring the user guide.',
            [
                'Dashboard' => route('dashboard.index'),
                'User Guide' => route('userguides.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/plugins/texteditorhtml/css/')
                ]
            ],
            [
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/plugins/texteditorhtml/js/')
                ],
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/apps/userguides/js/')
                ]
            ]
        );

        return view('apps.userguides.index', $resources);
    }
}
