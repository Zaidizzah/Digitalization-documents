<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resources = build_resource_array(
            "Dashboard",
            "Dashboard",
            "<i class=\"bi bi-speedometer\"></i> ",
            "A page for displaying a statistic of the system or application.",
            [
                'Dashboard' => route('dashboard.index')
            ]
        );

        return view('apps.dashboard.index', $resources);
    }
}
