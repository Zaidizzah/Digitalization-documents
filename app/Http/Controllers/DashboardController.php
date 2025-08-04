<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\File as FileModel;
use App\Models\DocumentType;
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

        $data = [
            'users' => User::count(),
            'files' => FileModel::count(),
            'document_types' => DocumentType::count(),
            'unlabeled_files' => FileModel::whereNull('document_type_id')->count()
        ];

        return view('apps.dashboard.index', array_merge($resources, $data));
    }
}
