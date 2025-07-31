<?php

namespace App\Http\Controllers;

use App\Interfaces\SearchableContent;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\File;
use App\Models\TempSchema;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * A page for displaying a list of data's/pages related to search query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $resources = build_resource_array(
            // List of data for the page
            'Search',
            "Searching: '<mark>$search</mark>'",
            '<i class="bi bi-search"></i> ',
            'A page for displaying a list of data\'s/pages related to search query.',
            [
                'Dashboard' => route('dashboard.index'),
                'Search' => route('search.index', $search)
            ],
            [
                [
                    'href' => 'styles.css',
                    'base_path' => asset('resources/apps/search/css/')
                ]
            ]
        );

        if ($search === NULL) {
            return redirect()->back()->with('message', toast('Please enter a search term.', 'warning'));
        }

        $searchables = [
            'User' => User::class,
            'File' => File::class,
            'DocumentType' => DocumentType::class,
            'TempSchema' => TempSchema::class
        ];

        $results = [];
        foreach ($searchables as $searchable_key => $searchable_value) {
            if (in_array(SearchableContent::class, class_implements($searchable_value))) {
                $results[$searchable_key] = $searchable_value::search($search);
            }
        }

        // Check if all result fields have at least one value among them, if not assign NULL
        $resources['results'] = !empty(array_filter($results)) ? $results : NULL;

        return view('apps.search.index', $resources)->with('message', toast("Search results for '<mark>$search</mark>' has been found.", 'success'));
    }
}
