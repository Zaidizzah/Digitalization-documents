<?php

namespace App\Http\Controllers;

use App\Exports\TableExport;
use App\Imports\TableImport;
use Illuminate\Http\Request;
use App\Http\Controllers\FileController;
use App\Models\DocumentType;
use App\Models\File as FileModel;
use App\Traits\ApiResponse;
use App\Services\SchemaBuilder;
use App\Services\OcrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Support\Facades\Schema;
use App\Models\DynamicModel;

class DocumentTypeActionController extends FileController
{
    use ApiResponse;

    private const PARENT_OF_DOCUMENTS_DIRECTORY = 'documents';
    private const PARENT_OF_FILES_DIRECTORY = 'documents/files';
    private const PARENT_OF_TEMP_FILES_DIRECTORY = 'documents/files/temp_uploads';

    /**
     * Handle file upload and recognition using OCR.
     *
     * This function receives a file from the request, uploads it temporarily to the server,
     * processes it using OCR to extract text, and then deletes the temporary file.
     * If the upload or OCR process fails, an error response is returned.
     *
     * @param \Illuminate\Http\Request $req The HTTP request containing the file to be recognized.
     * @return \Illuminate\Http\Response A JSON response with recognition results or an error message.
     */
    public function recognize_file_client(Request $req)
    {
        // Check if the request contains a type of 'multipart/form-data' or type 'application/x-www-form-urlencoded'
        if (strpos($req->header('Content-Type'), 'multipart/form-data') === FALSE) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }
        // Check if the request contains a file input
        if (!$req->hasFile('file') || empty($req->file('file'))) {
            return $this->error_response("File not found", null, Response::HTTP_NOT_FOUND);
        }

        $file = $req->file('file');

        // This process only: store the file temporarily on the server then the file enters the recognizing / OCR process after completion delete the related file and send the results
        $file_name = $file->getClientOriginalName();
        $stored_file = Storage::disk('local')->put(self::PARENT_OF_TEMP_FILES_DIRECTORY, $file);

        if (is_bool($stored_file) && $stored_file === false) {
            return $this->error_response("Sorry, we couldn't upload file: '{$file_name}'. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            if (method_exists(OcrService::class, 'process_file')) {
                return $this->error_response("OCR service is error or unvailable, please contact dev support to fix this.", null, Response::HTTP_SERVICE_UNAVAILABLE);
            }

            $OCR_result = OcrService::process_file($stored_file, $file_name);

            // delete uploaded file
            if (Storage::disk('local')->exists($stored_file)) Storage::disk('local')->delete($stored_file);

            return $this->success_response("File: {$file_name} has been recognized successfully.", [
                'result' => $OCR_result
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // delete uploaded file
            if (Storage::disk('local')->exists($stored_file)) Storage::disk('local')->delete($stored_file);

            return $this->error_response($e->getMessage(), null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     *
     * This function is responsible for displaying the data of a given document type.
     * It retrieves the data from the database and renders the view for displaying the data.
     * The function also handles potential exceptions during the retrieve process.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $name The name of the document type to display.
     *
     * @return \Illuminate\Http\Response Returns the view with the document type data.
     */
    public function browse(Request $request, string $name)
    {
        // get document type by name
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->back()->with('message', toast('Sorry, we couldn\'t find table for document type \'' . $name . '\', please create a valid table for this document type and try again.', 'error'));
        }

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        try {
            // check if document type has schema attributes and table
            if (empty($document_type->schema_form) || empty($document_type->schema_table) || empty($document_type->table_name)) {
                throw new \InvalidArgumentException("Sorry, we couldn't find schema for document type '$name', please create a valid schema for this document type and try again.", Response::HTTP_BAD_REQUEST);
            }

            $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name);
            $DOCUMENT_TYPE_DATA = $DYNAMIC_DOCUMENT_TYPE_MODEL->leftJoin('files', 'files.id', '=', "$document_type->table_name.file_id")
                ->select(
                    "{$document_type->table_name}.*",
                    'files.name as file_name',
                    'files.extension as file_extension',
                    'files.encrypted_name as file_encrypted_name',
                )->when($request->search ?? false, function ($query, $search) use ($request, $document_type) {
                    $columns = Schema::getColumnListing($document_type->table_name);
                    $excludedColumns = 'id';
                    $fileColumns = ['file_id' => ['files.name', 'files.extension']];

                    $requestedColumn = $request->column;

                    $isValidColumn = in_array($requestedColumn, $columns) || array_key_exists($requestedColumn, $fileColumns);

                    $query->where(function ($query) use ($search, $columns, $excludedColumns, $document_type, $fileColumns, $requestedColumn, $isValidColumn) {
                        if ($requestedColumn && $isValidColumn) {
                            if (isset($fileColumns[$requestedColumn])) {
                                foreach ($fileColumns[$requestedColumn] as $fileColumn) {
                                    $query->orWhere($fileColumn, 'like', "%$search%");
                                }
                            } else {
                                $query->orWhere("{$document_type->table_name}.$requestedColumn", 'like', "%$search%");
                            }
                        } else {
                            foreach ($columns as $column) {
                                if ($column !== $excludedColumns) {
                                    $query->orWhere("{$document_type->table_name}.$column", 'like', "%$search%");
                                }
                            }

                            $query->orWhere('files.name', 'like', "%$search%")
                                ->orWhere('files.extension', 'like', "%$search%")
                                ->orWhereRaw("DATE_FORMAT({$document_type->table_name}.created_at, '%d %F %Y, %H:%i %A') like ?", ["%$search%"])
                                ->orWhereRaw("DATE_FORMAT({$document_type->table_name}.updated_at, '%d %F %Y, %H:%i %A') like ?", ["%$search%"]);
                        }
                    });
                })->when($request->action === 'attach', function ($query) use ($document_type) {
                    $query->whereNull("{$document_type->table_name}.file_id");
                })
                ->paginate(25)
                ->appends(request()->query());

            // check if attach action on inactive document type
            if ($request->action === 'attach' && $document_type->is_active === 0) {
                return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast('Document type \'' . $name . '\' is inactive, please activate document type and try again.', 'error'));
            }

            // check if data is empty for attach action
            if ($request->action === 'attach' && $DOCUMENT_TYPE_DATA->isEmpty()) {
                return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast('Sorry, we couldn\'t find any data where file is not attached for document type \'' . $name . '\' data, please create unattached file data and try again.', 'error'));
            }

            $LIST_DOCUMENT_TYPE_DATA = SchemaBuilder::create_table_thead_from_schema_in_html($document_type->table_name, $document_type->schema_form) . "\n" . SchemaBuilder::create_table_tbody_from_schema_in_html($name, $document_type->table_name, $DOCUMENT_TYPE_DATA, $document_type->schema_table, $request->action);
        } catch (\Exception $e) {
            return redirect()->back()->with('message', toast($e->getMessage(), 'error'));
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
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'browse.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'href' => 'browse.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $resources['list_document_data'] = $LIST_DOCUMENT_TYPE_DATA;
        $resources['pagination'] = $DOCUMENT_TYPE_DATA;
        $resources['document_type'] = $document_type;

        $resources['columns_name'] = SchemaBuilder::get_form_columns_name_from_schema_representation($document_type->table_name, $document_type->schema_form, true);
        // delete 'id' value from columns name
        array_shift($resources['columns_name']);

        // if action hass value 'attach' pass data attached file to view
        $resources['attached_file'] = FileModel::when($request->action === 'attach' && $request->file, function ($query) use ($request) {
            return $query->where('encrypted_name', $request->file);
        })->firstOrFail();

        return view('apps.documents.browse', $resources);
    }

    /**
     * Attach a file to the specified document type data.
     *
     * This function validates the input request to ensure the presence and existence of the file ID
     * and document data IDs. If the validation succeeds, it updates the document type data by associating
     * the specified file ID with the provided document data IDs. It redirects with a success message upon
     * successful association, otherwise, it provides an error message.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing file and document data IDs.
     * @param string $name The name of the document type to which the file will be attached.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document browse page with a success or error message.
     */
    public function attach(Request $request, string $name)
    {
        $request_data = $request->only(['file_id', 'data_id']);
        $document_type = DocumentType::where('name', $name)->firstOrFail();

        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;

        if (empty($document_type->schema_form)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create a valid schema for this document type and try again.", 'error'));
        }

        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name)->__setFillableFields(array_column($document_type->schema_form, 'name'));

        // Create custom attribute for better message in client side
        $attribute_names = [];
        if (Arr::has($request_data, 'data_id') && is_array($request_data['data_id'])) {
            foreach ($request_data['data_id'] as $index => $value) {
                $attribute_names["'data_id'.$index"] = "data id Field " . ($index + 1);
            }
        }

        $validator = Validator::make($request_data, [
            'file_id' => 'required|exists:files,id',
            'data_id' => 'required|array',
            'data_id.*' => "required|exists:{$document_type->table_name},id"
        ]);
        $validator->setAttributeNames($attribute_names);

        if ($validator->fails()) {
            return redirect()->back()->with('message', toast("Invalid attaching file to document type '$name' data.", 'error'))->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $file = FileModel::where('id', $validated['file_id'])->first();

        $result = $DYNAMIC_DOCUMENT_TYPE_MODEL->whereIn('id', $validated['data_id'])->update([
            'file_id' => $validated['file_id']
        ]);

        if ($result) {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Successfully attaching file {$file->name}.{$file->extension} to document type '$name' data.", 'success'));
        } else {
            return redirect()->back()->with('message', toast("Failed attaching file {$file->name}.{$file->extension} to document type '$name' data.", 'error'));
        }
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

            $list_data_schema_attribute = SchemaBuilder::create_table_row_for_schema_attributes_in_html($name, $document_type->schema_form);
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
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'structure.css',
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
                    'base_path' => asset('/resources/apps/')
                ]
            ],
            [
                [
                    'src' => 'setting.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $resources['document_type'] = $document_type;

        return view('apps.documents.settings', $resources);
    }

    /**
     * Creates an HTML element for displaying the extraction result of a file.
     *
     * This function creates an HTML element that displays the extraction result of a file.
     * The element consists of a header with the file name and the extraction result.
     * The extraction result is wrapped in a paragraph element and escaped using htmlspecialchars.
     * The element also includes a copy button that copies the extraction result to the clipboard.
     *
     * @param string $file_name The name of the file to display.
     * @param string $OCR_result The extraction result of the file.
     * @return string The HTML element for displaying the extraction result of the file.
     */
    private function create_atachment_file_element(string $file_name, string $OCR_result)
    {
        if (empty($OCR_result)) {
            $OCR_result = '<div class="empty-state">No text detected.</div>';
        } else {
            $OCR_result = nl2br(htmlspecialchars($OCR_result, ENT_QUOTES, 'UTF-8'));
        }

        return <<<HTML
                <!-- Atachment file -->
                <div class="extraction-container" aria-label="Atachment file extraction container" aria-labelledby="extraction-container-label">
                    <div class="extraction-header">
                        <div class="ex-h-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                                <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                            </svg>
                        </div>
                        <h2 class="ex-h-title" id="extraction-container-label">Result of extraction document <span class="visually-hidden">file: {$file_name}</span></h2>
                    </div>
                    <div class="extraction-body">
                        <div class="ex-b-title">
                            <h5>File: {$file_name}</h5>
                        </div>
                        <div class="ex-b-result">
                            {$OCR_result}
                        </div>
                        <div class="ex-b-actions">
                            <button class="btn btn-secondary btn-copy-ex-result" type="button" role="button" title="Button: to copy extraction texts/result from file: {$file_name}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M3.5 2a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-12a.5.5 0 0 0-.5-.5H12a.5.5 0 0 1 0-1h.5A1.5 1.5 0 0 1 14 2.5v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14.5v-12A1.5 1.5 0 0 1 3.5 1H4a.5.5 0 0 1 0 1h-.5Z"/>
                                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3Zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3Z"/>
                                </svg>
                                Copy to clipboard
                            </button>
                        </div>
                    </div>
                </div>
            HTML;
    }

    /**
     * Show the form for creating a new resource.
     *
     * This function handles the creation form for a specified document type. It verifies the existence of the document type
     * and its associated table and schema. If valid, it prepares the form and necessary resources for rendering the view.
     * The function also processes file attachments if provided and integrates OCR results into the form.
     * Appropriate error messages are returned for any validation failures.
     *
     * @param Request $request The incoming request object containing form data.
     * @param string $name The name of the document type.
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
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
            return redirect()->back()->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        try {
            if (!$request->has('file') || empty($request->file)) {
                // initialze action
                $action = 'create';

                $INPUT_FORM_ELEMENT = SchemaBuilder::create_form_html($document_type->schema_form, null, $action);

                $FORM_HTML = "<div class=\"divider\">
                        <span class=\"divider-text\">File: Not Initialized</span>
                    </div>

                    <!-- Input field template -->
                    <template id=\"input-field-template\" aria-label=\"Input field template\" aria-hidden=\"true\">
                        <div class=\"input-field-wrapper\" id=\"input-field-1\" aria-label=\"Input field 1\">
                            <span class=\"input-field-title\" aria-labelledby=\"input-field-1\">Input field 1</span>
                            $INPUT_FORM_ELEMENT

                            <!-- Button to remove element input field wrapper -->
                            <div class=\"button-actions\" aria-label=\"Button actions for input fields wrapper\">
                                <button type=\"button\" role=\"button\" class=\"btn btn-danger btn-sm btn-delete-input-field\" title=\"Button: to remove this input field wrapper\"><i class=\"bi bi-trash fs-5\"></i></button> 
                            </div>
                        </div>
                    </template>

                    <!-- Input field template attached file -->
                    <template id=\"input-field-attached-file-template\" aria-label=\"Input field attached file template\" aria-hidden=\"true\">
                        <div class=\"input-field-wrapper attached-file\" id=\"input-field-attached-file-1\" aria-label=\"Input field attached file 1\">
                            <span class=\"input-field-title\" aria-labelledby=\"input-field-attached-file-1\">Input field attached file 1</span>
                            <div class=\"form-group g-3 mb-3\">
                                <label for=\"attached-file-1\" class=\"form-label\">File <span class=\"text-danger\">*</span></label>
                                <div class=\"input-group flex-nowrap\">
                                    <input type=\"file\" class=\"form-control\" id=\"attached-file-1\" aria-label=\"File\" accept=\"image/png, image/jpg, image/jpeg, image/webp, application/pdf\">
                                    <button class=\"btn btn-outline-primary btn-ocr-content-file\" type=\"button\" role=\"button\" title=\"Button: to get content result from file (OCR)\"><i class=\"bi bi-file-earmark-text fs-5\"></i> OCR</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class=\"input-fields-container\" id=\"input-field-container-1\" aria-label=\"Input fields container\">
                        <!-- Input field for file -->
                        <div class=\"input-field-wrapper attached-file\" id=\"input-field-attached-file-1\" aria-label=\"Input field attached file 1\">
                            <span class=\"input-field-title\" aria-labelledby=\"input-field-attached-file-1\">Input field attached file 1</span>

                            <!-- Checkbox for ignoring attached file -->
                            <div class=\"form-check form-switch mb-3\">
                                <input class=\"form-check-input\" type=\"checkbox\" id=\"ignore-attached-file-1\" aria-label=\"Ignore attached file 1\" aria-required=\"false\">
                                <label class=\"form-check-label\" for=\"ignore-attached-file-1\">Ignore attached file</label>
                            </div>

                            <div class=\"attached-file-wrapper form-group g-3 mb-3\">
                                <label for=\"attached-file-1\" class=\"form-label\">File <span class=\"text-danger\">*</span></label>
                                <div class=\"input-group flex-nowrap\">
                                    <input type=\"file\" class=\"form-control attached-file\" name=\"attached_file\" id=\"attached-file-1\" aria-label=\"File\" data-attached-file-id=\"1\" accept=\"image/png, image/jpg, image/jpeg, image/webp, application/pdf\" aria-required=\"true\" required>
                                    <button class=\"btn btn-outline-primary btn-ocr-content-file\" type=\"button\" role=\"button\" disabled title=\"Button: to get content result from file (OCR)\"><i class=\"bi bi-file-earmark-text fs-5\"></i> OCR</button>
                                </div>
                            </div>
                        </div>

                        <div class=\"input-field-wrapper\" id=\"input-field-1\" aria-label=\"Input field 1\">
                            <span class=\"input-field-title\" aria-labelledby=\"input-field-1\">Input field 1</span>

                            <!-- Input for initializing relation to attached file for upcoming feature -->
                            <input type=\"hidden\" name=\"attached_file_id\" disable value=\"1\">

                            $INPUT_FORM_ELEMENT
                        </div>

                        <div class=\"button-actions\" aria-label=\"Button actions for input field 1 container\">
                            <!-- Button for adding new input field for data file -->
                            <button type=\"button\" role=\"button\" class=\"btn btn-primary btn-sm float-end btn-add-input-field\" data-template-id=\"input-field-template\" title=\"Button: to adding new input field\"><i class=\"bi bi-plus-square fs-5\"></i> New</button> 
                        </div>
                    </div>"; // TODO: Upcoming feature is user can added multiple files and multiple data's from each file, TODO: first action is remove disable attribut form input file at top this code.
            } else {
                // initialze action
                $action = 'insert';

                $file_attachment = FileModel::without('document_type')->whereIn('encrypted_name', $request->file)->get();

                if ($file_attachment->isEmpty()) throw new \InvalidArgumentException("Sorry, we couldn't find file for document type '$name', please upload file for this document type and try again.", Response::HTTP_NOT_FOUND);

                // create an element of from with input field and atachement file extraction result
                $index = 1;
                $FORM_HTML = "";
                foreach ($file_attachment as $file) {
                    $INPUT_FORM_ELEMENT = SchemaBuilder::create_form_html($document_type->schema_form, null, $action, $file->id);
                    $TEMPLATE_FORM_ELEMENT = preg_replace_callback(
                        '/\sname=["\']?([^"\']+)["\']?/i',
                        function ($matches) {
                            // $matches[1] is the value of name before
                            return ' data-name="' . $matches[1] . '" disabled';
                        },
                        $INPUT_FORM_ELEMENT
                    );
                    $FORM_HTML .= "<div class=\"divider\">
                            <span class=\"divider-text\">File: {$file->name}.{$file->extension}</span>
                        </div>";

                    $response = OcrService::process_file(self::PARENT_OF_FILES_DIRECTORY . "/{$name}/{$file->encrypted_name}.{$file->extension}", "{$file->name}.{$file->extension}");

                    $FORM_HTML .= $this->create_atachment_file_element("{$file->name}.{$file->extension}", $response['text']);
                    $FORM_HTML .= "<div class=\"input-fields-container\" id=\"input-field-container-$index\" aria-label=\"Input fields container\">
                            <!-- Input field template -->
                            <template id=\"input-field-$index-template\" aria-label=\"Input field $index template\" aria-hidden=\"true\">
                                <div class=\"input-field-wrapper\" aria-label=\"Input field $index\">
                                    <span class=\"input-field-title\">Input field $index</span>
                                    $TEMPLATE_FORM_ELEMENT

                                    <!-- Button to remove element input field wrapper -->
                                    <div class=\"button-actions\" aria-label=\"Button actions for input fields wrapper\">
                                        <button type=\"button\" role=\"button\" class=\"btn btn-danger btn-sm btn-delete-input-field\" title=\"Button: to remove this input field wrapper\"><i class=\"bi bi-trash fs-5\"></i></button> 
                                    </div>
                                </div>
                            </template>

                            <div class=\"input-field-wrapper\" id=\"input-field-$index\" aria-label=\"Input field $index\">
                                <span class=\"input-field-title\">Input field $index</span>
                                $INPUT_FORM_ELEMENT
                            </div>

                            <!-- Button for adding new input field for data file -->
                            <div class=\"button-actions\" aria-label=\"Button actions for input field $index container\">
                                <button type=\"button\" role=\"button\" class=\"btn btn-primary btn-sm float-end btn-add-input-field\" data-template-id=\"input-field-$index-template\" title=\"Button: to adding new input field\"><i class=\"bi bi-plus-square fs-5\"></i> New</button>
                            </div>
                        </div>";

                    $index++;
                }
            }
        } catch (\Throwable $th) {
            // catch error and dsiplaying in page 
            return redirect()->back()->with('message', toast($th->getMessage(), 'error'));
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
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'insert-update-data.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs'
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.min.js'
                ],
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/tesseract.js/4.0.2/tesseract.min.js'
                ],
                [
                    'src' => 'uniqueinputtracker.js',
                    'base_path' => asset('/resources/plugins/uniqueinputtracker/js/')
                ],
                [
                    'src' => 'insert-data.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $resources['document_type'] = $document_type;
        $resources['form_html'] = $FORM_HTML;
        $resources['action'] = $action;
        $resources['recognize_url'] = route('documents.data.recognize', $name);

        return view('apps.documents.insert', $resources);
    }

    /**
     * Retrieves the API token hash for the specified document type.
     *
     * The API token hash is retrieved from the environment variables
     * `OCR_SPACE_API_KEY_HASH` and `OCR_SPACE_SPARE_API_KEY_HASH`.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function get__api_hashing_token(Request $request, string $name)
    {
        // check if request is json request
        if ($request->wantsJson() === false) {
            return $this->error_response("Invalid request.", null, Response::HTTP_BAD_REQUEST);
        }

        // check if document type exists
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

        if ($document_type === null) {
            return $this->error_response("Sorry, we couldn't find a document type with the name '$name'. Please try again.", null, Response::HTTP_NOT_FOUND);
        }

        // checking inveronment variable in .ENV file
        if (env("OCR_SPACE_API_KEY_HASH") !== null && env("OCR_SPACE_SPARE_API_KEY_HASH") !== null) {
            return $this->success_response("API token hash is successfully loaded.", ["OCR_SPACE_API_KEY_HASH" => env("OCR_SPACE_API_KEY_HASH"), "OCR_SPACE_SPARE_API_KEY_HASH" => env("OCR_SPACE_SPARE_API_KEY_HASH")]);
        } else {
            return $this->not_found_response("API token hash is not loaded in environment variable system.");
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * This function validates the request data and retrieves the document type by its name.
     * It checks if the table for the document type exists and if the schema for the document type is not corrupted.
     * The function also handles potential exceptions and provides appropriate error messages.
     * The function validates the request data and checks if the schema for the document type is not corrupted.
     * It inserts the validated data to the table for the document type and provides a success message.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing document type data.
     * @param string $name The name of the document type.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document types index or creation page
     *                                           with an appropriate message based on the outcome.
     */
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
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        $columns_name = array_column($document_type->schema_form, 'name');
        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name)->__setFillableFields($columns_name, true);

        // check if schema not corrupted by compare list column in table and schema
        if (
            !empty(array_diff($columns_name, array_keys($request->all()))) &&
            !empty(array_diff($columns_name, SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name, $document_type->schema_table)))
        ) {
            return redirect()->back()->with('message', toast("Sorry, schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", 'error'));
        }

        try {
            DB::beginTransaction();
            $request_data = $request->only([...$columns_name, 'file_id']);
            $validation_rules = SchemaBuilder::get_validation_rules_from_schema($document_type->table_name, $document_type->schema_form, $columns_name);

            if (is_bool($validation_rules) && !$validation_rules) {
                throw new \RuntimeException("schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", Response::HTTP_PRECONDITION_FAILED);
            }

            // add validation rules for file_id
            $validation_rules['file_id.*'] = 'nullable|exists:files,id';

            foreach ($validation_rules as $rule => $value) {
                if ($rule !== 'file_id.*') {
                    $validation_rules["$rule"] = (in_array('required', $value) ? 'required' : 'nullable') . "|array";

                    // add new array with new key name
                    $validation_rules["$rule.*"] = $value;

                    // delete current array
                    unset($validation_rules[$rule]);
                }
            }

            // Create custom attribute for better message in client side
            $attribute_names = [];
            foreach ($columns_name as $column) {
                if (Arr::has($request_data, $column) && is_array($request_data[$column])) {
                    foreach ($request_data[$column] as $index => $value) {
                        $attribute_names["$column.$index"] = ucfirst(str_replace('_', ' ', $column)) . " Field " . ($index + 1);
                    }
                }
            }

            // get and validate data from table document type
            $validator = Validator::make($request_data, $validation_rules);
            $validator->setAttributeNames($attribute_names);

            if ($validator->fails()) {
                return redirect()->back()->with('message', toast("Invalid creating document type '$name' data, please fill the form correctly.", 'error'))->withErrors($validator);
            }

            $validated = $validator->validated();

            // compare list attribute rule with validated data
            foreach ($document_type->schema_form as $attribute) {
                $key = $attribute['name'];

                if (Arr::has($validated, $key)) {
                    $value = $validated[$key];

                    if (is_array($value)) {
                        foreach ($value as $index => $item) {
                            if (!$attribute['required'] && Arr::has($attribute, 'rules.defaultValue') && (is_null($item) || $item === '')) {
                                $value[$index] = $attribute['rules']['defaultValue'];
                            }
                        }
                    } else {
                        if (!$attribute['required'] && Arr::has($attribute, 'rules.defaultValue') && (is_null($value) || $value === '')) {
                            $value = $attribute['rules']['defaultValue'];
                        }
                    }

                    $validated[$key] = $value;
                }
            }

            if ($request->hasFile('attached_file')) {
                // validate request
                $validator = Validator::make($request->only(['attached_file']), [
                    'attached_file' => FileRule::types(['pdf', 'png', 'jpg', 'jpeg', 'webp'])->max(20 * 1024 * 1024)
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->with('message', toast("File is invalid value, failed to upload file. Please try again."))->withErrors($validator);
                }

                list($FILE, $STORED_PATH, $FILE_MODEL) = $this->process_file('attached_file', $request, $name, $document_type);
            }

            // insert data to table document type
            $data = array_map(function ($index) use ($validated) {
                return array_combine(array_keys($validated), array_column($validated, $index));
            }, range(0, count(reset($validated)) - 1));

            // For create action
            if (isset($FILE_MODEL)) {
                for ($i = 0; $i < count($data); $i++) {
                    $data[$i]['file_id'] = $FILE_MODEL->id ?? null;
                }
            }

            // Insert all data and commit transaction
            $DYNAMIC_DOCUMENT_TYPE_MODEL->insert($data);
            DB::commit();

            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("New data for document type '$name' has been created successfully.", 'success'));
        } catch (\Exception $e) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete uploaded file
            if (isset($STORED_PATH) && is_string($STORED_PATH) && Storage::disk('local')->exists($STORED_PATH)) Storage::disk('local')->delete($STORED_PATH);

            return redirect()->route('documents.data.create', $name)->with('message', toast($e->getMessage(), 'error'))->withInput();
        }
    }


    /**
     * Show the form for editing the specified document type data.
     *
     * This function retrieves and prepares the necessary data for editing a specific record
     * of a document type. It validates the existence of the document type, ensures the
     * associated table and schema are present, and generates the HTML form for editing
     * the record. If any errors occur during these processes, appropriate error messages
     * are generated.
     *
     * @param \Illuminate\Http\Request $request The HTTP request instance.
     * @param string $name The name of the document type to edit.
     * @param mixed $id The ID of the record to edit.
     *
     * @return \Illuminate\Contracts\View\View Returns the view with the form HTML and document type data.
     */
    public function edit(Request $request, string $name, $id)
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
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name)->__setFillableFields(array_column($document_type->schema_form, 'name'), true);

        // get relevant of document type data and related file to update with
        $document_type_data = $DYNAMIC_DOCUMENT_TYPE_MODEL->where('id', $id)->get()->firstOrFail();
        $file_attachment = FileModel::where('id', $document_type_data->file_id)->first();

        try {
            // initialze action
            $action = 'update';

            // get form html to updating data
            $INPUT_FORM_ELEMENT = SchemaBuilder::create_form_html($document_type->schema_form, $document_type_data, $action);
            $TEMPLATE_FORM_ELEMENT = preg_replace_callback(
                '/\sname=["\']?([^"\']+)["\']?/i',
                function ($matches) {
                    // $matches[1] is the value of name before
                    return ' data-name="' . $matches[1] . '" disabled';
                },
                $INPUT_FORM_ELEMENT
            );

            // delete value of each attribute in field input 'input', 'textarea', 'select' - (If select reset option to default value or select option has empty value)
            $RESET_FORM_ELEMENT = function ($TEMPLATE_FORM_ELEMENT) {
                $TEMPLATE_FORM_ELEMENT = preg_replace_callback(
                    '/(<select[^>]*>)(.*?)(<\/select>)/is',
                    function ($matches) {
                        $select_tag = $matches[1]; // Opening tag <select>
                        $options = $matches[2];   // Options <select> ... </select>
                        $closing_tag = $matches[3]; // Closing tag </select>

                        // Delete selected attribute from options
                        $options = preg_replace('/\s+selected(?:=["\']?[^"\']*["\']?)?/i', '', $options);

                        if (preg_match('/<option\s+value=["\']?["\']?\s*(?!disabled)[^>]*>/i', $options)) {
                            // Check if there is an option with an empty value (and not disabled)
                            $options = preg_replace('/(<option\s+value=["\']?["\']?\s*(?!disabled)[^>]*)(>)/i', '$1 selected$2', $options, 1);
                        } else {
                            // Check if there is an option with a value and not disabled
                            if (preg_match('/(<option[^>]*(?!disabled)[^>]*)(>)/i', $options, $first_option)) {
                                $position = strpos($options, $first_option[0]);
                                $replacement = str_replace('>', ' selected>', $first_option[0]);
                                $options = substr_replace(
                                    $options,
                                    $replacement,
                                    $position,
                                    strlen($first_option[0])
                                );
                            }
                        }

                        return "{$select_tag}{$options}{$closing_tag}";
                    },
                    $TEMPLATE_FORM_ELEMENT
                );
                $TEMPLATE_FORM_ELEMENT = preg_replace_callback(
                    '/<input\s+([^>]*?)(\s*\/?>)/is',
                    function ($matches) {
                        $attributes = $matches[1];
                        $closing = $matches[2];

                        $type = '';
                        if (preg_match('/type=["\']?([^"\'\s>]+)["\']?/i', $attributes, $type_match)) {
                            $type = strtolower($type_match[1]);
                        }

                        // If type is checkbox, radio, submit, button, or hidden, don't change
                        $excluded_types = ['checkbox', 'radio', 'submit', 'button'];
                        if (in_array($type, $excluded_types)) {
                            return "<input {$attributes}{$closing}";
                        }

                        // Delete value attribute from attributes if it exists
                        $attributes = preg_replace('/\s+value=["\']?[^"\']*["\']?/i', '', $attributes);

                        return "<input {$attributes} value=\"\"{$closing}";
                    },
                    $TEMPLATE_FORM_ELEMENT
                );
                $TEMPLATE_FORM_ELEMENT = preg_replace_callback(
                    '/(<textarea[^>]*>)(.*?)(<\/textarea>)/is',
                    function ($matches) {
                        $textarea_tag = $matches[1]; // Opening tag <textarea>
                        $closing_tag = $matches[3]; // Closing tag </textarea>

                        // Remove value from textarea
                        return "{$textarea_tag}{$closing_tag}";
                    },
                    $TEMPLATE_FORM_ELEMENT
                );

                return $TEMPLATE_FORM_ELEMENT;
            };

            $FORM_HTML = "<div class=\"divider\">
                            <span class=\"divider-text\">File: " . ($file_attachment !== null ? "{$file_attachment->name}.{$file_attachment->extension}" : "Not Initialzed") . "</span>
                        </div>";

            // check if file attachment is available and not null
            if ($file_attachment !== null) {
                $response = OcrService::process_file(self::PARENT_OF_FILES_DIRECTORY . "/{$name}/{$file_attachment->encrypted_name}.{$file_attachment->extension}", "{$file_attachment->name}.{$file_attachment->extension}");

                $FORM_HTML .= $this->create_atachment_file_element("{$file_attachment->name}.{$file_attachment->extension}", $response['text']);
            }

            $FORM_HTML .= "<!-- Input field template -->
                    <template id=\"input-field-template\" aria-label=\"Input field template\" aria-hidden=\"true\">
                        <div class=\"input-field-wrapper\" id=\"input-field-1\" data-action=\"insert\" aria-label=\"Input field 1\">
                            <span class=\"input-field-title\" aria-labelledby=\"input-field-1\">Input field 1</span>
                            {$RESET_FORM_ELEMENT($TEMPLATE_FORM_ELEMENT)}

                            <!-- Button to remove element input field wrapper -->
                            <div class=\"button-actions\" aria-label=\"Button actions for input fields wrapper\">
                                <button type=\"button\" role=\"button\" class=\"btn btn-danger btn-sm btn-delete-input-field\" title=\"Button: to remove this input field wrapper\"><i class=\"bi bi-trash fs-5\"></i></button> 
                            </div>
                        </div>
                    </template>
                    <div class=\"input-fields-container\" id=\"input-field-container-1\" aria-label=\"Input fields container\">
                        <div class=\"input-field-wrapper\" id=\"input-field-1\" data-action=\"update\" aria-label=\"Input field 1\">
                            <span class=\"input-field-title\">Input field 1</span>
                            $INPUT_FORM_ELEMENT
                        </div>

                        <div class=\"button-actions\" aria-label=\"Button actions for input field 1 container\">
                            <!-- Button for adding new input field for data file -->
                            <button type=\"button\" role=\"button\" class=\"btn btn-primary btn-sm float-end btn-add-input-field\" data-template-id=\"input-field-template\" title=\"Button: to adding new input field\"><i class=\"bi bi-plus-square fs-5\"></i> New</button> 
                        </div>
                    </div>";
        } catch (\Throwable $e) {
            // Get any exception and display in page
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast($e->getMessage(), 'error'));
        }

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // List of data for the page
        $resources = build_resource_array(
            "Edit data to document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Edit data to document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for Editing data to document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name",
                "Edit data to document type $name" => route('documents.data.create', $name)
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'insert-update-data.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'uniqueinputtracker.js',
                    'base_path' => asset('/resources/plugins/uniqueinputtracker/js/')
                ],
                [
                    'src' => 'edit-data.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $resources['id'] = $id;
        $resources['document_type'] = $document_type;
        $resources['form_html'] = $FORM_HTML;
        $resources['action'] = $action;

        return view('apps.documents.edit', $resources);
    }

    /**
     * Handle an incoming request to update data in the document type.
     *
     * This function validates the data from the request and updates the document type
     * if the data is valid. If the data is invalid, it displays an error message and
     * redirects back with the input data.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the data to update.
     * @param string $name The name of the document type to update.
     * @param int $id The ID of the record to update.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success or error message.
     */
    public function update(Request $request, string $name, $id)
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
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        $columns_name = array_column($document_type->schema_form, 'name');
        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name)->__setFillableFields($columns_name, true);

        // check if schema not corrupted by compare list column in table and schema
        if (
            !empty(array_diff($columns_name, array_keys($request->all()))) &&
            !empty(array_diff($columns_name, SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name, $document_type->schema_table)))
        ) {
            return redirect()->back()->with('message', toast("Sorry, schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", 'error'));
        }

        try {
            DB::beginTransaction();
            $request_data = $request->only([...$columns_name, 'id']);
            $validation_rules = SchemaBuilder::get_validation_rules_from_schema($document_type->table_name, $document_type->schema_form, $columns_name, $request->id[0]);

            if (is_bool($validation_rules) && !$validation_rules) {
                throw new \RuntimeException("schema for document type '$name' is corrupted, please update or recreate schema for document type '$name' and try again.", Response::HTTP_PRECONDITION_FAILED);
            }

            // add validation rules for file_id
            $validation_rules['id.*'] = "nullable|exists:{$document_type->table_name},id";

            foreach ($validation_rules as $rule => $value) {
                if ($rule !== 'id.*') {
                    // prepare and add validation to field is array
                    $validation_rules[$rule] = (in_array('required', $value) ? 'required' : 'nullable') . '|array';

                    // add new array with new key name
                    $validation_rules["$rule.*"] = $value;
                    // delete current array
                    unset($validation_rules[$rule]);
                }
            }

            // Create custom attribute for better message in client side
            $attribute_names = [];
            foreach ($columns_name as $column) {
                if (Arr::has($request_data, $column) && is_array($request_data[$column])) {
                    foreach ($request_data[$column] as $index => $value) {
                        $attribute_names["$column.$index"] = ucfirst(str_replace('_', ' ', $column)) . " Field " . ($index + 1);
                    }
                }
            }

            // get and validate data from table document type
            $validator = Validator::make($request_data, $validation_rules);
            $validator->setAttributeNames($attribute_names);

            if ($validator->fails()) {
                return redirect()->back()->with('message', toast("Invalid updating document type '$name' data, please fill the form correctly.", 'error'))->withErrors($validator);
            }

            $validated = $validator->validated();

            // compare list attribute rule with validated data
            foreach ($document_type->schema_form as $attribute) {
                $key = $attribute['name'];

                if (Arr::has($validated, $key)) {
                    $value = $validated[$key];

                    if (is_array($value)) {
                        foreach ($value as $index => $item) {
                            if (!$attribute['required'] && Arr::has($attribute, 'rules.defaultValue') && (is_null($item) || $item === '')) {
                                $value[$index] = $attribute['rules']['defaultValue'];
                            }
                        }
                    } else {
                        if (!$attribute['required'] && Arr::has($attribute, 'rules.defaultValue') && (is_null($value) || $value === '')) {
                            $value = $attribute['rules']['defaultValue'];
                        }
                    }

                    $validated[$key] = $value;
                }
            }

            // insert data to table document type
            $data = array_map(function ($index) use ($validated) {
                return array_combine(array_keys($validated), array_column($validated, $index));
            }, range(0, count(reset($validated)) - 1));

            $result = $DYNAMIC_DOCUMENT_TYPE_MODEL->upsert($data, 'id', $columns_name);
            DB::commit();

            if ($result) {
                return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("New data for document type '$name' has been created successfully.", 'success'));
            } else {
                return redirect()->back()->with('message', toast("Sorry, we couldn't create new data for document type '$name', please try again.", 'error'));
            }
        } catch (\Exception $e) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            return redirect()->route('documents.data.create', $name)->with('message', toast($e->getMessage(), 'error'))->withInput();
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
    public function destroy(...$args): RedirectResponse // That parameter is override effect of destroy func in FileController in PHP
    {
        // get document type by name
        $document_type = DocumentType::where('name', $args[0])->where('is_active', 1)->firstOrFail();
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;

        if (empty($document_type->schema_form)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find schema for document type '{$args[0]}'.", 'error'));
        }

        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name);

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find table for document type '{$args[0]}', please create a valid table for document type '{$args[0]}' and try again.", 'error'));
        }

        // delete data in table
        if ($DYNAMIC_DOCUMENT_TYPE_MODEL->where('id', $args[1])->delete()) {
            return redirect()->back()->with('message', toast("Data in document type '{$args[0]}' has been deleted successfully.", 'success'));
        } else {
            return redirect()->back()->with('message', toast("No data deleted in document type '{$args[0]}'.", 'error'));
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
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;

        if (empty($document_type->schema_form)) {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find schema for document type '$name'.", 'error'));
        }

        $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name);

        // check if table exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table for document type '$name' and try again.", 'error'));
        }

        // delete all data in table
        if ($DYNAMIC_DOCUMENT_TYPE_MODEL->delete()) {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("All data in document type '$name' has been deleted successfully.", 'success'));
        } else {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("No data deleted in document type '$name'.", 'error'));
        }
    }

    /**
     * Export data of a document type to a file.
     *
     * This function is responsible for exporting the data of a given document type to a file.
     * It first checks if the table exists and if the format is valid.
     * The function returns a downloaded file with the exported data.
     *
     * @param \Illuminate\Http\Request $req The request object.
     * @param string $name The name of the document type to export.
     * @return \Illuminate\Http\Response The response object.
     */
    public function export(Request $req, string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if format is valid
        if (!in_array($req->format, ['xlsx', 'xls', 'pdf', 'csv'])) abort(404);

        // check table is exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table/schema for document type '$name' and try again.", 'error'));
        }

        $file_name =  "{$document_type->name}_" . date('Y_m_d_His') . ".{$req->format}";

        return Excel::download(new TableExport($document_type->name, $document_type->table_name, $file_name), $file_name);
    }

    /**
     * Import data into the specified document type.
     *
     * This function handles the import of data from an uploaded file into a specified document type.
     * It retrieves the active document type by its name, and uses the TableImport service to process
     * the file data according to the document type's schema. Upon successful import, it redirects back
     * with a success message; otherwise, it returns with import error messages.
     *
     * @param \Illuminate\Http\Request $req The request object containing the uploaded file.
     * @param string $name The name of the document type to import data into.
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success message or import errors.
     */
    public function import(Request $req, string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check table is exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table/schema for document type '$name' and try again.", 'error'));
        }

        // check if document type has schema attributes and table
        if (empty($document_type->schema_form) || empty($document_type->schema_table) || empty($document_type->table_name)) {
            throw new \InvalidArgumentException("Sorry, we couldn't find schema for document type '$name', please create a valid schema for this document type and try again.", Response::HTTP_BAD_REQUEST);
        }

        $import = new TableImport($document_type->table_name, $document_type->schema_form);
        Excel::import($import, $req->file('data'));

        if ($import->success) {
            return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Importing data in document type '{$name}' has been successfully saved."));
        } else {
            return redirect()->back()->withErrors($import->messages);
        }
    }
}
