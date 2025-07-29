<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\TempSchema;
use App\Traits\ApiResponse;
use App\Services\SchemaBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    private const PARENT_OF_DOCUMENTS_DIRECTORY = 'documents';
    private const PARENT_OF_FILES_DIRECTORY = 'documents/files';
    private const PARENT_OF_TEMP_FILES_DIRECTORY = 'documents/files/temps';
    private array $VALIDATION_RULES;

    public function __construct()
    {
        $this->VALIDATION_RULES = [
            'name' => [
                'required',
                'string',
                'max:' . SchemaBuilder::__get_max_length_for_field_name(),
                'regex:/^(?!.* {2})[a-zA-Z][a-zA-Z0-9_\s]{0,63}$/',
                Rule::unique('document_types', 'name')->where('is_active', 1),
            ],
            'description' => 'nullable|string',
            'long_name' => 'nullable|string|max:125',
        ];
    }

    /**
     * Display a listing of the resource for document types.
     */
    public function index()
    {
        $resources = build_resource_array(
            "Manage document types",
            "Manage document types",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing document types and displaying a list of document types.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ]
            ]
        );

        $document_type = DocumentType::orderBy('created_at', 'desc')->when(is_role('User'), function ($query) {
            $query->where('is_active', 1);
        })->paginate(25)->withQueryString();

        $resources['document_types'] = $document_type;

        return view('apps.documents.index', $resources);
    }

    /**
     * Show the form for creating a new resource for document types.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // variable for detect if user have saved schema in temporary storage
        $has_saved_schema = TempSchema::where('user_id', auth()->user()->id)->exists();

        $resources = build_resource_array(
            "Add document type",
            "Add document type",
            "<i class=\"bi bi-file-earmark-plus\"></i> ",
            "A page for adding new document type.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Add document type" => route('documents.create')
            ],
            [
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'schemabuilder.js',
                    'base_path' => asset('/resources/plugins/schemabuilder/js/')
                ],
                [
                    'src' => 'create.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ],
                [
                    'inline' => <<<JS
                        schemaBuilder.hasSavedSchema = $has_saved_schema;
                    JS
                ]
            ]
        );

        $resources['has_saved_schema'] = $has_saved_schema;

        return view('apps.documents.create', $resources);
    }

    /**
     * Saves the given schema to the temporary storage.
     * 
     * This function takes in a request containing the schema to be saved.
     * It first checks if the user has saved schema before, and if so, updates
     * that schema. If the user has not saved any schema, it creates a new one.
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function save_schema(Request $request)
    {
        // check if request is json request
        if ($request->wantsJson() === false) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }

        if ($request->has('schema')) {
            $validator = Validator::make($request->only('schema'), [
                'schema' => 'required|array'
            ]);

            if ($validator->fails()) return $this->validation_error(
                "Sorry, value of schema is invalid. Please try again.",
                [
                    'errors' => error_validation_response_custom($validator->errors())
                ]
            );

            $validated = $validator->validated();

            // Ensure only one schema is saved per user
            TempSchema::updateOrCreate(
                ['user_id' => auth()->user()->id],
                ['schema' => json_encode($validated['schema'], JSON_PRETTY_PRINT)]
            );

            return $this->success_response("Your schema has been saved successfully.", null, Response::HTTP_CREATED);
        }

        return $this->error_response("Sorry, we are unable to save your schema. Please try again.", null, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Load the given schema into the UI.
     * 
     * This function takes in an optional name parameter and an HTTP request.
     * If the name parameter is set, it attempts to load the schema with the given
     * name from the database. If the name parameter is not set, it attempts to
     * load the schema from the user's temporary schema if it exists.
     * 
     * @param ?string $name The name of the schema to load.
     * @param ?int|string $attribute_id The ID of the attribute to filter the schema.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function load_schema(Request $request, ?string $name = null, string|int|null $attribute_id = null)
    {
        // check if request is json request
        if ($request->wantsJson() === false) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

            if (empty($document_type)) return $this->not_found_response("Sorry, we couldn't find a document type with the name '$name'. Please try again.");

            if (empty($document_type->schema_form) || empty($document_type->schema_table)) return $this->not_found_response("Sorry, we can't load your schema. Please make sure document type '$name' have a valid schema.");

            // getting attributes and conditioning schema
            if ($attribute_id) {
                $schema = SchemaBuilder::filter_schema_attributes_of_document_type(json_decode($document_type->schema_form, true), $attribute_id);
            } else {
                $schema = json_decode($document_type->schema_form, true);
            }

            if (empty($schema)) return $this->error_response("Sorry, we can't load your schema. Document type '$name' may not have a schema yet. Please create a valid schema.", null, Response::HTTP_UNPROCESSABLE_ENTITY);

            return $this->success_response("Schema has been successfully loaded.", ['schema' => $schema]);
        }

        // Checking schema has stored/saved before or not.
        $temp_schema = TempSchema::where('user_id', auth()->user()->id)->first();

        if (empty($temp_schema)) return $this->error_response("Sorry, we can't load your schema. You don't have a saved schema on temporary storage yet.");

        return $this->success_response("Schema has been successfully loaded.", ['schema' => json_decode($temp_schema->schema, true)]);
    }

    /**
     * Store a newly created document type in the database.
     *
     * This function validates the request data, retrieves the temporary schema for
     * the current user, and attempts to create a new document type with the provided
     * information. It handles schema building, table creation, and ensures the document
     * type is saved correctly. If successful, the temporary schema is deleted and the
     * user is redirected with a success message. In case of any error during the process,
     * it rolls back changes and provides an error message.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing document type data.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document types index or creation page
     *                                           with an appropriate message based on the outcome.
     */
    public function store(Request $request)
    {
        // Checking schema has stored/saved before or not.
        $temp_schema = TempSchema::where('user_id', auth()->user()->id)->firstOrFail();

        $temp_schema->schema = json_decode($temp_schema->schema, true) ?? null;

        // check if saved schema is empty or not
        if (empty($temp_schema->schema)) {
            return redirect()->route('documents.create')->with('message', toast('Sorry, we couldn\'t find your saved schema in temporary storage, please try again.', 'error'));
        }

        $validator = Validator::make(
            $request->only(['name', 'description', 'long_name']),
            $this->VALIDATION_RULES
        );

        if ($validator->fails()) {
            return redirect()->route('documents.create')->with('message', toast('Failed to create document type, please fill the form correctly and create valid schema.', "error"))->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        $name = $validated['name'];

        // initialize new document type model
        $document_type = new DocumentType();

        try {
            $schema = SchemaBuilder::handle_schema_for_table_and_form($temp_schema->schema);

            // after all process success, create folder for document type
            if (Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/$name")) {
                Storage::disk('local')->deleteDirectory(self::PARENT_OF_FILES_DIRECTORY . "/$name");
            }

            if (!Storage::disk('local')->makeDirectory(self::PARENT_OF_FILES_DIRECTORY . "/$name")) {
                throw new \RuntimeException(
                    "Failed to make directory in '" . self::PARENT_OF_FILES_DIRECTORY . "/$name' for document type '$name'.",
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // create a table 
            $table_name = SchemaBuilder::create_table($name, $schema['table'], $schema['form']);

            DB::beginTransaction();

            // create new document type
            $document_type->fill([
                'user_id' => auth()->user()->id,
                'name' => $name,
                'table_name' => $table_name,
                'description' => $validated['description'],
                'long_name' => $validated['long_name'],
                'is_active' => true,
                'schema_table' => json_encode($schema['table'], JSON_PRETTY_PRINT),
                'schema_form' => json_encode($schema['form'], JSON_PRETTY_PRINT),
            ]);
            $document_type->save();

            if (empty($document_type)) throw new \RuntimeException("Sorry, we couldn't create document type '$name', please try again.");

            // Delete temp schema
            $temp_schema->delete();

            DB::commit();

            return redirect()->route('documents.index')->with('message', toast("Document type '$name' has been created successfully.", 'success'));
        } catch (\Exception $e) {
            // rollback transaction if any exception occurs and transaction level is active or greater than 0
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete folder of document type
            if (Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/$name")) {
                Storage::disk('local')->deleteDirectory(self::PARENT_OF_FILES_DIRECTORY . "/$name");
            }

            // delete table if exists
            if (SchemaBuilder::table_exists($table_name)) SchemaBuilder::drop_table($table_name);

            // refresh changes value of document type to original value if transaction fails
            $document_type->refresh();

            return redirect()->route('documents.create')
                ->with('message', toast($e->getMessage(), 'error'))
                ->withInput();
        }
    }

    /**
     * Update the schema attributes of the specified document type.
     *
     * This function is responsible for updating the schema attributes of a given document type.
     * It validates the request data and updates the 'schema_form' field of the document type.
     * The function also handles validation errors and exceptions during the update process.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the schema attributes to update.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success or error message.
     */
    public function update_schema_of_document_type(Request $request, string $name)
    {
        // get document type by name
        $document_type = DocumentType::select('id', 'name', 'table_name', 'schema_form', 'schema_table')->where('name', $name)->where('is_active', 1)->firstOrFail();

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check if document type has schema
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return redirect()->route("documents.structure", $name)->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        // get data schema attributes fro temporary storage
        $temp_schema = TempSchema::where('user_id', auth()->user()->id)->first();

        // check if schema has stored/saved before or not.
        if (empty($temp_schema)) return redirect()->back()->with('message', toast('Sorry, we couldn\'t find your saved schema in temporary storage, please create schema and try again.', 'error'));

        // decode schema from json to array schema attributes
        $temp_schema->schema = json_decode($temp_schema->schema, true) ?? null;

        // check if schema is empty
        if (empty($temp_schema->schema)) {
            return redirect()->back()->with('message', toast('Sorry, we couldn\'t find your saved schema in temporary storage, please create schema and try again.', 'error'));
        }

        try {
            // update schema attributes logic
            $new_schema = SchemaBuilder::handle_schema_for_table_and_Form($temp_schema->schema, 'update');

            // update schema attributes
            $updated_schema_form_and_table = SchemaBuilder::update_table($document_type->table_name, $document_type->schema_form, $document_type->schema_table, $new_schema['form'], $new_schema['table']);

            // begin transaction if no exception occurs while updating schema attributes
            DB::beginTransaction();

            // delete temporary schema
            $temp_schema->delete();

            // update schema attributes if all operations succeed
            $document_type->schema_form = json_encode($updated_schema_form_and_table['form'], JSON_PRETTY_PRINT);
            $document_type->schema_table = json_encode($updated_schema_form_and_table['table'], JSON_PRETTY_PRINT);
            $document_type->save();

            DB::commit();

            return redirect()->route("documents.structure", $name)->with('message', toast("Schema attributes for document type '$name' has been updated successfully.", 'success'));
        } catch (\Exception $e) {
            // rollback transaction if any exception occurs and transaction level is active or greater than 0
            if (DB::transactionLevel() > 0) DB::rollBack();

            // refresh changes value of document type to original value if transaction fails
            $document_type->refresh();

            return redirect()->route("documents.structure", $name)->with('message', toast($e->getMessage(), 'error'));
        }
    }

    /**
     * Update the metadata of the specified document type.
     *
     * This function validates and updates the fields 'name', 'description', and 'long_name'
     * for a given document type. If the 'name' field is updated, the corresponding database
     * table is renamed. The function also handles validation errors and exceptions during
     * the update process.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the metadata to update.
     * @param string $id The ID of the document type to update.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success or error message.
     */
    public function update(Request $request, string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->with('example_file')->firstOrFail();

        $validator = Validator::make(
            $request->only(['name', 'description', 'long_name']),
            $this->VALIDATION_RULES
        );

        if ($validator->fails()) {
            return redirect()->route("documents.settings", $name)->with('message', toast("Invalid updating document type '$name'.", 'error'))->withErrors($validator);
        }

        try {
            $validated = $validator->validated();
            $document_type->table_name = Str::snake(trim($validated['name']));
            $original_table_name = $document_type->table_name;

            DB::beginTransaction();
            if ($validated['name'] && $original_table_name !== $document_type->table_name) {
                // changes the name of the table
                SchemaBuilder::rename_table($original_table_name, $document_type->table_name);

                $document_type->name = $validated['name'];
            }

            $document_type->description = $validated['description'];
            $document_type->long_name = $validated['long_name'];

            if ($document_type->isDirty('name')) {
                // rename existing directory
                if (Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/{$name}")) {
                    if (!Storage::disk('local')->move(self::PARENT_OF_FILES_DIRECTORY .  "/$name", self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}")) {
                        throw new \RuntimeException(
                            "Sorry, we couldn't rename directory from previous name '" . self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name} to new name '" . self::PARENT_OF_FILES_DIRECTORY  . "/{$validated['name']}' of document type '$name', please try again.",
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }
                }

                // rename existing example file
                if (Storage::disk('local')->exists($document_type->example_file->file_path)) {
                    if (!Storage::disk('local')->move($document_type->example_file->file_path, str_replace($name, $document_type->name, $document_type->example_file->file_path))) {
                        throw new \RuntimeException(
                            "Sorry, we couldn't rename example file from previous name '" . $document_type->example_file->file_path . " to new name '" . str_replace($name, $document_type->name, $document_type->example_file->file_path) . "' of document type '$name', please try again.",
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }
                }
            }

            // Save updated example file
            $document_type->example_file->name = str_replace($name, $document_type->name, $document_type->example_file->name);
            $document_type->example_file->encrypted_name = str_replace($name, $document_type->name, $document_type->example_file->encrypted_name); // because example file is not encrypted
            $document_type->example_file->path = str_replace($name, $document_type->name, $document_type->example_file->path);
            $document_type->example_file->save();

            // Save updated document type
            $document_type->save();
            DB::commit();

            return redirect()->route("documents.settings", $document_type->table_name)->with('message', toast("Document type '$name' has been updated successfully.", 'success'));
        } catch (\Exception $e) {
            // rollback transaction if any exception occurs and transaction level is active or greater than 0
            if (DB::transactionLevel() > 0) DB::rollBack();

            // rollback renamed table name to previous table name
            if (SchemaBuilder::table_exists($document_type->table_name)) SchemaBuilder::rename_table($document_type->table_name, $original_table_name);

            // rollback renamed directory name to previous directory name
            if (Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}")) {
                Storage::disk('local')->move(self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}", self::PARENT_OF_FILES_DIRECTORY . "/{$name}");
            }

            // rollback renamed example file name to previous example file name
            if (Storage::disk('local')->exists(str_replace($document_type->name, $name, $document_type->example_file->file_path))) {
                Storage::disk('local')->move(str_replace($name, $document_type->name, $document_type->example_file->file_path), str_replace($document_type->name, $name, $document_type->example_file->file_path));
            }

            // refresh all model
            $document_type->refresh();
            $document_type->example_file->refresh();

            return redirect()->route("documents.settings", $name)->with('message', toast($e->getMessage(), 'error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit_schema_of_document_type(string $name, ?string $attribute_id = null)
    {
        // get and validate data from table document type
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        if ($document_type->long_name) $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // create link to load schema attributes
        $load_schema_link = route('documents.schema.load', [$name, $attribute_id]);

        // get schema attributes from table
        $except_attributes_name = SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name);
        $except_attributes_name = implode(
            '$|',
            array_unique(
                array_merge(
                    $except_attributes_name,
                    array_map(function ($column_name) {
                        return str_replace('_', ' ', $column_name);
                    }, $except_attributes_name)
                )
            )
        );

        // List of data for the page
        $resources = build_resource_array(
            "Modifying attributes of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Modifying attributes of document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for modifying attributes of document type $document_type->abbr and displaying a attributes of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name" => route('documents.structure', $name),
                "Modify attributes of document type $name" => URL::current(),
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'schemabuilder.js',
                    'base_path' => asset('/resources/plugins/schemabuilder/js/')
                ],
                [
                    'src' => 'edit.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ],
                [
                    'inline' => <<<JS
                        schemaBuilder.hasSavedSchema = true;
                        schemaBuilder.loadURL = '{$load_schema_link}';
                        schemaBuilder.attributeLoadHandler('{$load_schema_link}');
                    JS
                ]
            ]
        );

        $resources['document_type'] = $document_type;
        $resources['except_attributes_name'] = $except_attributes_name;

        return view('apps.documents.edit-schema', $resources);
    }

    /**
     * Drop a specified attribute of a document type.
     *
     * This function checks if the document type exists and has a schema,
     * decodes the schema from JSON to array,
     * drops the specified attribute from the schema and table,
     * updates the schema attributes if all operations succeed,
     * and saves the document type.
     *
     * @param string $name The name of the document type to drop the attribute from.
     * @param string $attribute_id The ID of the attribute to drop.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects to the document types index or show page
     *                                           with an appropriate message based on the outcome.
     */
    public function delete_schema_of_document_type(string $name, string $attribute_id)
    {
        // get and validate data from table document type
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // check if document type has schema
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return redirect()->route("documents.structure", $name)->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        $columns_name = array_map(function ($column_name) {
            return str_replace('_', ' ', $column_name);
        }, array_intersect(SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name), SchemaBuilder::get_columns_name_from_schema($document_type->schema_table, $attribute_id)));

        try {
            // drop columns from schema attributes and table
            $dropped_schema_form_and_table = SchemaBuilder::drop_column($document_type->table_name, $document_type->schema_table, $document_type->schema_form, $attribute_id);

            // update schema attributes if all operations succeed
            $document_type->schema_form = json_encode($dropped_schema_form_and_table['form'], JSON_PRETTY_PRINT);
            $document_type->schema_table = json_encode($dropped_schema_form_and_table['table'], JSON_PRETTY_PRINT);
            $document_type->save();

            // redirect to index page if all operations succeed and schema is empty
            if (empty(json_decode($document_type->schema_form, true))) {
                return redirect()->route('documents.index')->with('message', toast("Attribute '" . implode('\', \'', $columns_name) . "' has been deleted from document type '$name'.", 'success'));
            }

            return redirect()->route('documents.structure', $name)->with('message', toast("Attribute '" . implode('\', \'', $columns_name) . "' has been deleted from document type '$name'.", 'success'));
        } catch (\Exception $e) {
            // refresh changes value of document type to original value if operation fails
            $document_type->refresh();

            // return error message
            redirect()->route('documents.structure', $name)->with('message', toast($e->getMessage(), 'error'));
        }
    }

    /**
     * Show the form for inserting new attributes of the specified document type.
     *
     * This function is responsible for displaying the page for inserting new attributes of a given document type.
     * It validates the request data and retrieves the document type by its name.
     * The function also handles potential exceptions and provides appropriate error messages.
     * The function is also responsible for preparing the data for rendering in the view.
     *
     * @param string $name The name of the document type to display.
     * @return \Illuminate\Http\Response Returns the view with the document type data and schema attributes.
     */
    public function insert(string $name)
    {
        // get and validate data from table document type
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        if ($document_type->long_name) $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        // variable for detect if user have saved schema in temporary storage
        $has_saved_schema = TempSchema::where('user_id', auth()->user()->id)->exists();

        // get schema attributes from table
        $except_attributes_name = SchemaBuilder::get_table_columns_name_from_schema_representation($document_type->table_name);
        $except_attributes_name = implode(
            '$|',
            array_unique(
                array_merge(
                    $except_attributes_name,
                    array_map(function ($column_name) {
                        return str_replace('_', ' ', $column_name);
                    }, $except_attributes_name)
                )
            )
        );

        // List of data for the page
        $resources = build_resource_array(
            "Insert attributes of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Insert attributes of document type $document_type->abbr",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for insert new attributes of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name" => route('documents.structure', $name),
                "Insert new attributes of document type $name" => URL::current(),
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'schemabuilder.js',
                    'base_path' => asset('/resources/plugins/schemabuilder/js/')
                ],
                [
                    'src' => 'insert.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ],
                [
                    'inline' => <<<JS
                        schemaBuilder.hasSavedSchema = $has_saved_schema;
                    JS
                ]
            ]
        );

        $resources['has_saved_schema'] = $has_saved_schema;
        $resources['document_type'] = $document_type;
        $resources['except_attributes_name'] = $except_attributes_name;

        return view('apps.documents.insert-schema', $resources);
    }

    /**
     * Insert schema attributes into the specified document type.
     *
     * This function is responsible for inserting new schema attributes into a given
     * document type. It validates the presence of a saved temporary schema and updates
     * the schema attributes of the document type accordingly. The function handles the
     * reordering and continuation of schema sequence numbers and ensures the successful
     * update of the document type schema. In case of any error during the process, it
     * rolls back changes and provides an error message.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing schema data.
     * @param string $name The name of the document type to update.
     *
     * @return \Illuminate\Http\RedirectResponse Redirects to the document type view page
     *                                           with a success or error message.
     */
    public function insert_schema_of_document_type(Request $request, string $name)
    {
        // get and validate data from table document type
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // Checking schema has stored/saved before or not.
        $temp_schema = TempSchema::where('user_id', auth()->user()->id)->firstOrFail();

        $temp_schema->schema = json_decode($temp_schema->schema, true);

        // check if saved schema is empty
        if (empty($temp_schema->schema)) {
            return redirect()->back()->with('message', toast('Sorry, we couldn\'t find your saved schema in temporary storage, please try again.', 'error'));
        }

        try {
            $schema = SchemaBuilder::handle_schema_for_table_and_form($temp_schema->schema);

            // reorder the schema sequence number
            $document_type->schema_form = SchemaBuilder::reorder_schema_sequence_number(json_decode($document_type->schema_form, true));
            $document_type->schema_table = SchemaBuilder::reorder_schema_sequence_number(json_decode($document_type->schema_table, true));

            // continue the schema sequence number to continue the previous sequence number 
            $new_schema_form = SchemaBuilder::continues_schema_sequence_number($document_type->schema_form, $schema['form']);
            $new_schema_table = SchemaBuilder::continues_schema_sequence_number($document_type->schema_table, $schema['table']);

            // Insert schema to table document type
            SchemaBuilder::add_column($document_type->table_name, $document_type->schema_table, $new_schema_table);

            DB::beginTransaction();

            // Update schema of document type
            $document_type->schema_form = json_encode(array_merge($document_type->schema_form, $new_schema_form), JSON_PRETTY_PRINT);
            $document_type->schema_table = json_encode(array_merge($document_type->schema_table, $new_schema_table), JSON_PRETTY_PRINT);
            $document_type->save();

            // Delete schema from temporary storage
            $temp_schema->delete();

            DB::commit();

            return redirect()->route('documents.structure', $name)->with('message', toast("Schema of document type '$name' has been updated successfully.", 'success'));
        } catch (\Exception $e) {
            // rollback changes in case of error
            if (DB::transactionLevel() > 0) DB::rollBack();

            $document_type->refresh();

            return redirect()->back()->with('message', toast($e->getMessage(), 'error'));
        }
    }

    /**
     * Remove the specified document type from the database.
     *
     * This function attempts to delete a document type by its name. It first checks
     * if the associated table exists and then drops the table before deleting the
     * document type record. If successful, it redirects to the document types index
     * with a success message. The function logs an error and throws an exception if the
     * table does not exist, and handles any exceptions during the process by redirecting
     * with an error message.
     *
     * @param string|int $id The id of the document type to delete.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document types index with a success or error message.
     */
    public function destroy(string|int $id)
    {
        $document_type = DocumentType::where('is_active', 1)->findOrFail($id);

        // check table exist in database and or directory of document type is exist
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            Log::error(
                sprintf(
                    'Table %s does not exist. Cannot delete document type %s.',
                    $document_type->table_name,
                    $document_type->name
                )
            );

            return redirect()->route('documents.index')->with('message', toast("Table '{$document_type->table_name}' does not exist. Cannot delete document type '{$document_type->name}'.", 'error'));
        }

        if (!Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}")) {
            Log::error(
                sprintf(
                    'Directory %s does not exist. Cannot delete document type %s.',
                    self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}",
                    $document_type->name
                )
            );

            return redirect()->route('documents.index')->with('message', toast("Directory '" . self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}' does not exist. Cannot delete document type '{$document_type->name}'.", 'error'));
        }

        // check table has data and or directory of document type has files
        if (DB::table($document_type->table_name)->count() > 0 || Storage::disk('local')->files(self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}")) {
            // make document type and folder to trash (unactive)
            $document_type->is_active = false;
            $document_type->save();

            $document_type_trashed_name = "{$document_type->name}_trash_" . now('Asia/Jakarta')->format('Y_m_d_His');
            $document_type_trashed_table_name = "{$document_type->table_name}_trash_" . now('Asia/Jakarta')->format('Y_m_d_His');

            // rename document type and folder to trash
            Storage::disk('local')->move(
                self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}",
                self::PARENT_OF_FILES_DIRECTORY . "/$document_type_trashed_name"
            );

            SchemaBuilder::rename_table($document_type->table_name, $document_type_trashed_name);

            // add name of document type to list of trashed document type
            DB::table('document_types_trashed')->insert([
                'user_id' => auth()->user()->id,
                'document_type_id' => $document_type->id,
                'trashed_name' => $document_type_trashed_name,
                'trashed_table_name' => $document_type_trashed_table_name
            ]);

            return redirect()->route('documents.index')->with('message', toast("Document type '{$document_type->name}' has been deleted successfully.", 'success'));
        } else {
            // delete document type and folder if both is empty
            Storage::disk('local')->deleteDirectory(self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}");
            SchemaBuilder::drop_table($document_type->table_name);
            $document_type->delete();

            return redirect()->route('documents.index')->with('message', toast("Document type '{$document_type->name}' has been deleted successfully.", 'success'));
        }
    }

    /**
     * Restore a previously deleted document type.
     *
     * This function retrieves a document type by its name that is currently inactive.
     * It then restores the document type by marking it as active, renaming the associated
     * folder and database table back to their original names, and removing the entry from
     * the trashed document types table.
     *
     * @param string|int $id The id of the document type to restore.
     * @return \Illuminate\Http\RedirectResponse Redirects to the document types index with a success message.
     */
    public function restore(string|int $id)
    {
        $document_type = DocumentType::where('is_active', 0)->findOrFail($id);

        $document_type_trashed_query = DB::table('document_types_trashed')->where('document_type_id', $document_type->id);
        // check if document type is trashed or not
        if (!$document_type_trashed_query->exists()) {
            return redirect()->route('documents.index')->with('message', toast("Document type '{$document_type->name}' is not trashed.", 'error'));
        }

        // check if document type is trashed and not duplicate
        if (DocumentType::where('name', $document_type->name)->where('is_active', 1)->exists()) {
            return redirect()->route('documents.index')->with('message', toast("Document type '{$document_type->name}' already exists and is not trashed or active. Please rename the document type or check if active document type cannot be more than one.", 'error'));
        }

        $document_type_trashed = $document_type_trashed_query->first();

        // make document type to active
        $document_type->is_active = 1;
        $document_type->save();

        // rename document folder
        Storage::disk('local')->move(
            self::PARENT_OF_FILES_DIRECTORY . "/{$document_type_trashed->trashed_name}",
            self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}",
        );

        SchemaBuilder::rename_table($document_type_trashed->trashed_table_name, $document_type->table_name);
        $document_type_trashed_query->delete();

        return redirect()->route('documents.index')->with('message', toast("Document type '{$document_type->name}' has been restored successfully.", 'success'));
    }

    /**
     * Show the page for reordering schema of a document type.
     *
     * This function retrieves a document type by its name that is currently active.
     * It then builds an array of resources to be passed to the view.
     *
     * @param string $name The name of the document type for which to show the reorder
     *                     schema page.
     * @return \Illuminate\View\View The view for reordering the schema of a document type.
     */
    public function reorder(string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        //check if data long_name is not empty set new properti 'abbr'
        $document_type->abbr = $document_type->long_name ? '<abbr title="' . $document_type->long_name . '">' . $name . '</abbr>' : $document_type->name;

        $load_columns_link = route('documents.schema.columns', [$document_type->name]);
        $resources = build_resource_array(
            "Reorder schema of document type $name " . ($document_type->long_name ? ' (' . $document_type->long_name . ')' : ''),
            "Reorder schema of document type $document_type->abbr",
            "<i class=\"bi bi-list\"></i> ",
            "A page for reordering schema of document type $document_type->abbr.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Document type $name" => route('documents.structure', $name),
                "Reorder schema of document type $name"
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'order.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ]
            ],
            [
                [
                    'src' => 'order.js',
                    'base_path' => asset('/resources/apps/documents/js/')
                ],
                [
                    'inline' => <<<JS
                        loadColumnsData('{$load_columns_link}');
                    JS
                ]
            ]
        );

        $resources['document_type'] = $document_type;

        return view('apps.documents.reorder-schema', $resources);
    }

    public function reorder_schema_of_document_type(Request $request, string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check if document type has schema attributes
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find schema for document type '$name', please create schema for this document type and try again.", 'error'));
        }

        // get columns name from schema representation
        $request_data = $request->only(['id', 'sequence']);

        // Create custom attribute for better message in client side
        $attribute_names = [];
        foreach (array_keys($request_data) as $column) {
            if (Arr::has($request_data, $column) && is_array($request_data[$column])) {
                foreach ($request_data[$column] as $index => $value) {
                    $attribute_names["$column.$index"] = ucfirst(str_replace('_', ' ', $column)) . " Field " . ($index + 1);
                }
            }
        }

        $validator = Validator::make($request_data, [
            'id.*' => "required|string",
            'sequence.*' => "required|numeric",
        ]);
        // Validate attributes id exists in schema attributes
        $validator->after(function ($validator) use ($document_type) {
            $ids = array_column($document_type->schema_form, 'id');

            foreach ($validator->getData()['id'] as $index => $id) {
                if (!in_array($id, $ids)) {
                    $validator->errors()->add("id.$index", "Field with id $id doesn't exist");
                }
            }
        });
        $validator->setAttributeNames($attribute_names);

        if ($validator->fails()) {
            return redirect()->back()->with('message', toast($validator->messages()->first(), 'error'))->withErrors($validator);
        }

        $validated = $validator->validated();

        // Change order/sequence number of schema attributes
        $new_schema_form = $document_type->schema_form;
        $new_schema_table = $document_type->schema_table;
        foreach ($document_type->schema_form as $attribute => $value) {
            foreach ($validated['id'] as $key => $id) {
                if ($value['id'] === $id) {
                    // update id attributes
                    $new_schema_form[$attribute]['id'] = substr_replace($new_schema_form[$attribute]['id'], $validated['sequence'][$key], strrpos($new_schema_form[$attribute]['id'], ':') + 1);

                    // update table attributes
                    $new_schema_table[$new_schema_form[$attribute]['name']]['id'] = substr_replace($new_schema_table[$new_schema_form[$attribute]['name']]['id'], $validated['sequence'][$key], strrpos($new_schema_table[$new_schema_form[$attribute]['name']]['id'], ':') + 1);

                    // update sequence number
                    $new_schema_form[$attribute]['sequence_number'] = $validated['sequence'][$key];

                    $new_schema_table[$new_schema_form[$attribute]['name']]['sequence_number'] = $validated['sequence'][$key];
                }
            }
        }

        // update and reordered sequence number of schema attributes
        $document_type->schema_form = json_encode(SchemaBuilder::reorder_schema_sequence_number($new_schema_form), JSON_PRETTY_PRINT);
        $document_type->schema_table = json_encode(SchemaBuilder::reorder_schema_sequence_number($new_schema_table), JSON_PRETTY_PRINT);
        $document_type->save();

        return redirect()->route('documents.structure', $name)->with('message', toast("Schema of document type '$name' has been reordered successfully.", 'success'));
    }

    /**
     * Retrieve the schema attributes of a document type.
     *
     * This function is responsible for retrieving the schema attributes of a given document type.
     * It first checks if the associated table exists and then attempts to decode the schema attributes
     * from the 'schema_form' field of the document type.
     * The function returns a success response with the retrieved schema attributes in the form of a JSON object.
     *
     * @param \Illuminate\Http\Request $request The HTTP request.
     * @param string $name The name of the document type to retrieve its schema attributes.
     * @return \Illuminate\Http\JsonResponse A success response with the retrieved schema attributes.
     */
    public function get__schema_attribute_columns(Request $request, string $name)
    {
        // check if request is json
        if ($request->wantsJson() === false) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }

        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

        // check if document type exists
        if ($document_type === null) {
            return $this->error_response("Sorry, we couldn't find a document type with the name '$name'. Please try again.", null, Response::HTTP_NOT_FOUND);
        }

        $document_type->schema_form = json_decode($document_type->schema_form, true);
        $document_type->schema_table = json_decode($document_type->schema_table, true);

        // check if document type has schema attributes
        if (empty($document_type->schema_form) || empty($document_type->schema_table)) {
            return $this->error_response("Sorry, we couldn't find schema for document type '$name'. Please try again.", null, Response::HTTP_BAD_REQUEST);
        }

        // Get columns data with format { id: 'column_id', name: 'column_name', type: 'column_type', sequence: 'column_sequence' }
        $columns = [];
        foreach ($document_type->schema_form as $column => $value) {
            $columns[] = [
                'id' => $value['id'],
                'name' => $column,
                'type' => strtoupper($value['type']),
                'sequence' => $value['sequence_number']
            ];
        }

        return $this->success_response("Schema for document type '$name' has been retrieved successfully.", ['columns' => $columns]);
    }
}
