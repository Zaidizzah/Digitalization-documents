<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class userGuideController extends Controller
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
            'User Guide/Docs',
            'User Guide/Docs',
            '<i class="bi bi-folders"></i> ',
            'A page for displaying rules for application and what user must do or cannot do.',
            [
                'Dashboard' => route('dashboard.index'),
                'User Guide' => route('userguide.index')
            ],
        );
        $resources['on_user_guide'] = true; // For esential data for all user guide pages

        return view('apps.user-guide.index', $resources);
    }
}
