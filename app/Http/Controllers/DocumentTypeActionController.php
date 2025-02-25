<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Traits\ApiResponse;
use App\Service\SchemaBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Arr;

class DocumentTypeActionController extends Controller
{
    /**
     * Display the specified resource.
     *
     * This function is responsible for displaying the data of a given document type.
     * It retrieves the data from the database and renders the view for displaying the data.
     * The function also handles potential exceptions during the retrieve process.
     *
     * @param string $name The name of the document type to display.
     *
     * @return \Illuminate\Http\Response Returns the view with the document type data.
     */
    public function browse(string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.index')->with('message', toast('Sorry, we couldn\'t find table for document type \'' . $name . '\', please create a valid table for this document type and try again.', 'error'));
        }

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        try {
            // check if document type has schema attributes and table
            if (empty($document_type->schema_form) || empty($document_type->schema_table) || empty($document_type->table_name)) {
                throw new \InvalidArgumentException("Sorry, we couldn't find schema for document type '$name', please create a valid schema for this document type and try again.", Response::HTTP_BAD_REQUEST);
            }

            $document_type_data = DB::table($document_type->table_name)->paginate(25)->appends(request()->query());

            $list_document_data = SchemaBuilder::create_table_thead_from_schema_in_html($document_type->table_name, $document_type->schema_form) . "\n" . SchemaBuilder::create_table_tbody_from_schema_in_html($name, $document_type->table_name, $document_type_data, $document_type->schema_table);
        } catch (\Exception $e) {
            return redirect()->route('documents.index')->with('message', toast($e->getMessage(), 'error'));
        }

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // List of data for the page
        $resources = build_resource_array(
            "Data of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Manage data of document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing data of document type $document_type->abbr and displaying a list data of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name"
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ]
        );

        $resources['list_document_data'] = $list_document_data;
        $resources['pagination'] = $document_type_data;
        $resources['document_type'] = $document_type;

        return view('apps.documents.browse', $resources);
    }

    /**
     * Display the specified schema of a document type and its schema attributes.
     *
     * This function retrieves and displays the details of a document type by its name,
     * including its schema attributes. It checks for the existence of the document type
     * and its associated schema. If found, it prepares the data for rendering in the view.
     * The function handles potential exceptions and provides appropriate error messages.
     *
     * @param string $name The name of the document type to display.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     *         Redirects with an error message if the document type or schema is not found,
     *         otherwise returns the view with the document type data and schema attributes.
     */
    public function structure(string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.index')->with('message', toast('Sorry, we couldn\'t find table for document type \'' . $name . '\', please create a valid table for this document type and try again.', 'error'));
        }

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        try {
            // check if document type has schema attributes and table
            if (empty($document_type->schema_form) || empty($document_type->schema_table) || empty($document_type->table_name)) {
                throw new \InvalidArgumentException("Sorry, we couldn't find schema for document type '$name', please create a valid schema for this document type and try again.", Response::HTTP_BAD_REQUEST);
            }

            $list_data_schema_attribute = SchemaBuilder::create_table_row_for_schema_attributes_in_html($document_type->table_name, $document_type->schema_form);
        } catch (\Exception $e) {
            return redirect()->route('documents.index')->with('message', toast($e->getMessage(), 'error'));
        }

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // List of data for the page
        $resources = build_resource_array(
            "Schema of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Manage schema of document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing schema of document type $document_type->abbr and displaying a schema attributes of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name"
            ],
            [
                [
                    'href' => 'structure.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'structure.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $resources['list_schema_data'] = $list_data_schema_attribute;
        $resources['document_type'] = $document_type;

        return view('apps.documents.structure', $resources);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * This function is responsible for displaying the page for managing document type settings.
     * It validates the request data and retrieves the document type by its name.
     * The function also handles potential exceptions and provides appropriate error messages.
     * The function is also responsible for preparing the data for rendering in the view.
     *
     * @param string $name The name of the document type to display.
     * @return \Illuminate\Http\Response Returns the view with the document type data and schema attributes.
     */
    public function settings(string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // List of data for the page
        $resources = build_resource_array(
            "Settings of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Settings of document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing configuration of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name"
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ]
        );

        $resources['document_type'] = $document_type;

        return view('apps.documents.settings', $resources);
    }

    public function create(Request $request, string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.index')->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table for document type '$name' and try again.", 'error'));
        }

        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check if document type has schema attributes and table
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return redirect()->route('documents.browse', $name)->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        try {
            $form_html = SchemaBuilder::create_form_html($document_type->schema_form, null);
        } catch (\Exception $e) {
            return redirect()->route('documents.browse', $name)->with('message', toast($e->getMessage(), 'error'));
        }

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // List of data for the page
        $resources = build_resource_array(
            "Insert data to document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Insert data to document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for inserting data to document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name",
                "Insert data to document type $name" => route('documents.data.create', $name)
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ]
        );

        $resources['document_type'] = $document_type; 
        $resources['form_html'] = $form_html;

        return view('apps.documents.insert', $resources);
    }

    public function store(Request $request, string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.index')->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table for document type '$name' and try again.", 'error'));
        }

        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check if document type has schema attributes and table
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return redirect()->route('documents.browse', $name)->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        $columns_name = array_column($document_type->schema_form, 'name');

        // check if schema not corrupted by compare list column in table and schema
        if (
            !empty(array_diff($columns_name, array_keys($request->all()))) &&
            !empty(array_diff($columns_name, SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name, $document_type->schema_table)))
        ) {
            return redirect()->back()->with('message', toast("Sorry, schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", 'error'));
        }

        try {
            $validation_rules = SchemaBuilder::get_validation_rules_from_schema($document_type->table_name, $document_type->schema_form, $columns_name);
        } catch (\Exception $e) {
            return redirect()->route('documents.data.create', $name)->with('message', toast($e->getMessage(), 'error'))->withInput();
        }

        if (is_bool($validation_rules) && !$validation_rules) {
            return redirect()->back()->with('message', toast("Sorry, schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", 'error'));
        }

        // add validation rules for file_id
        $validation_rules['file_id'] = 'nullable|exists:files,id';

        // get and validate data from table document type
        $validator = Validator::make($request->only([...$columns_name, 'file_id']), $validation_rules);

        if ($validator->fails()) {
            return redirect()->back()->with('message', toast("Invalid creating document type '$name' data, please fill the form correctly.", 'error'))->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        // insert data to table document type
        $result = DB::table($document_type->table_name)->insert($validated);

        if ($result) {
            return redirect()->route('documents.browse', $name)->with('message', toast("New data for document type '$name' has been created successfully.", 'success'));
        } else {
            return redirect()->back()->with('message', toast("Sorry, we couldn't create new data for document type '$name', please try again.", 'error'));
        }
    }

    /**
     * Delete the specified data in a document type.
     *
     * This function attempts to delete a document type data by its id. It first checks
     * if the associated table exists and then attempts to delete the data in the
     * table.
     * The function returns a redirect response with a success or error message.
     *
     * @param string $name The name of the document type to delete data from.
     * @param int $id The id of the document type data to delete.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document type's browse page with a success or error message.
     */
    public function destroy(string $name, int $id)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.index')->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table for document type '$name' and try again.", 'error'));
        }

        // delete data in table
        if (DB::table($document_type->table_name)->where('id', $id)->delete()) {
            return redirect()->route('documents.browse', $name)->with('message', toast("Data in document type '$name' has been deleted successfully.", 'success'));
        } else {
            return redirect()->route('documents.browse', $name)->with('message', toast("No data deleted in document type '$name'.", 'error'));
        }
    }

    /**
     * Delete all data in a document type.
     *
     * This function is responsible for deleting all data in a given document type.
     * It first checks if the associated table exists and then attempts to delete
     * all data in the table.
     * The function returns a redirect response with a success or error message.
     *
     * @param string $name The name of the document type to delete all data from.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document type's browse page with a success or error message.
     */
    public function destroy_all(string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.browse', $name)->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table for document type '$name' and try again.", 'error'));
        }

        // delete all data in table
        if (DB::table($document_type->table_name)->delete()) {
            return redirect()->route('documents.browse', $name)->with('message', toast("All data in document type '$name' has been deleted successfully.", 'success'));
        } else {
            return redirect()->route('documents.browse', $name)->with('message', toast("No data deleted in document type '$name'.", 'error'));
        }
    }
}
