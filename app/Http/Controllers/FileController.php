<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\File as FileModel;
use App\Traits\ApiResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Services\SchemaBuilder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExampleExport;
use App\Models\DynamicModel;

class FileController extends Controller
{
    use ApiResponse;

    private const PARENT_OF_DOCUMENTS_DIRECTORY = 'documents';
    private const PARENT_OF_FILES_DIRECTORY = 'documents/files';
    private const PARENT_OF_TEMP_FILES_DIRECTORY = 'documents/files/temps';

    /**
     * Display a listing of the files.
     *
     * This function retrieves and displays a list of files.
     * It handles any necessary data processing and prepares the data for rendering in the view.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req, ?string $name = null)
    {
        $resources = build_resource_array(
            "Manage document files",
            "Manage document files",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing document files and displaying a list of document files.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Manage document files" => route('documents.files.root.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/files/css/')
                ]
            ],
            [
                [
                    'src' => 'fileuploadmanager.js',
                    'base_path' => asset('/resources/plugins/fileuploadmanager/js/')
                ],
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/apps/files/js/')
                ]
            ]
        );

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();
            $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;

            if (empty($document_type->schema_form)) {
                return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast("Sorry, we couldn't find schema for document type '$name'.", 'error'));
            }

            $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name);

            // check if user need to be attached file to data of document type
            if ($req->action === 'attach' && $DYNAMIC_DOCUMENT_TYPE_MODEL->whereNull('file_id')->count() < 1) {
                return redirect()->route('documents.browse', [$name, 'action' => 'browse'])->with('message', toast('Sorry, we couldn\'t find any data where file is not attached for document type \'' . $name . '\' data, please create unattached file data and try again.', 'error'));
            }

            // change the upload url
            $upload_url = route('documents.files.upload', $document_type->name);
            array_push($resources['javascript'], [
                'inline' => "uploadQueue.uploadUrl = '$upload_url'"
            ]);

            $resources['breadcrumb'] = array_slice($resources['breadcrumb'], 0, array_search("Documents", array_keys($resources['breadcrumb'])) + 1, true)
                + ["Document type {$document_type->name}"]
                + array_slice($resources['breadcrumb'], array_search("Documents", array_keys($resources['breadcrumb'])) + 1, null, true);
        }

        $files = FileModel::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }])->filesWithFilter($req->only(['type', 'search']), $name)->orderBy('created_at', 'desc')->paginate(25)->appends($req->all())->withQueryString();

        // If request is Fetch request send paginating files data to client
        if ($req->wantsJson()) {
            return $this->success_response("Files loaded successfully.", [
                'files' => view('apps.files.list', ['on_upload' => false, 'on_attach' => $req->action === 'attach', 'files' => $files, 'document_type' => $document_type ?? null])->render(),
            ]);
        }

        $resources['files'] = $files;
        $resources['document_types'] = DocumentType::orderBy('created_at', 'desc')->where('is_active', 1)->get();
        $resources['document_type'] = $document_type ?? null;

        return view('apps.files.index', $resources);
    }

    /**
     * Handle Edit filename and document type.
     *
     * @param \Illuminate\Http\Request $req
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rename(Request $req)
    {
        $file = FileModel::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }])->where('encrypted_name', $req->file)->firstOrFail();

        $req->validate([
            'name' => "required|string|max:255|unique:files,name,{$file->id}",
            'document_type_id' => "nullable|numeric|exists:document_types,id"
        ]);

        try {
            DB::beginTransaction();
            // check if file exist
            if (!Storage::exists($file->path)) {
                throw new \RuntimeException("File {$file->name}.{$file->extension} does not exist.", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            if ($file->document_type_id !== $req->document_type_id) {
                $from = $file->path;

                if ($req->document_type_id !== null) {
                    // Move to new document type folder
                    $document_type = DocumentType::where('id', $req->document_type_id)->where('is_active', 1)->first();
                    if ($document_type === null) {
                        throw new \RuntimeException("Specified document type does not exist.", Response::HTTP_NOT_FOUND);
                    }
                    $to = self::PARENT_OF_FILES_DIRECTORY . "/{$document_type->name}/{$file->encrypted_name}.{$file->extension}";
                } else {
                    // Move to main folder
                    $to = self::PARENT_OF_TEMP_FILES_DIRECTORY . "/{$file->encrypted_name}.{$file->extension}";
                }

                if (!Storage::move($from, $to)) {
                    throw new \RuntimeException("File {$file->name}.{$file->extension} cannot be moved from '{$from}' to '{$to}'.", Response::HTTP_INTERNAL_SERVER_ERROR);
                }

                $file->path = $to;
                $message = sprintf(
                    "File %s has been moved to %s.",
                    "{$file->name}.{$file->extension}",
                    $req->document_type_id !== null ? "document type/folder '{$document_type->name}'" : "main folder"
                );
            } else {
                $message = sprintf("File %s has been renamed successfully.", "{$file->name}.{$file->extension}");
            }

            // rename file
            $file->name = $req->name;
            $file->document_type_id = $req->document_type_id;
            $file->save();
            DB::commit();

            return redirect()->back()->with('message', toast($message));
        } catch (\Exception $e) {
            // rollback transaction if any exception occurs and transaction level is active or greater than 0
            if (DB::transactionLevel() > 0) DB::rollBack();

            // refresh file model
            $file->refresh();

            // move file back to original location
            if (Storage::exists($to)) {
                Storage::move($to, $file->path);
            }

            return redirect()->back()->with('message', toast($e->getMessage(), 'error'));
        }
    }

    /**
     * Upload file and store its metadata to the database.
     *
     * This function expects a file request and a document type name (optional).
     * If the document type name is provided, the file is uploaded to the specified
     * document type folder. Otherwise, the file is uploaded to the temporary files
     * folder. The file is then saved to the database with its metadata.
     *
     * @param string $key_name The key name of input file name in \Illuminate\Http\Request
     * @param \Illuminate\Http\Request $req The HTTP request containing the file to be uploaded (previous request).
     * @param string|null $name The name of the document type folder to upload the file to (document type name/previous request parameter).
     * @param \App\Models\DocumentType $document_type The document type model
     * @return array containing current file, stored path, and file model
     * 
     * @throws \RuntimeException If the file cannot be uploaded or saved to the database.
     */
    public function process_file($key_name, $req, $name, &$document_type)
    {
        // upload file
        $file = $req->file($key_name);

        // store file with encrypted name
        $stored_file = Storage::disk('local')->put($name ? self::PARENT_OF_FILES_DIRECTORY . "/{$name}" : self::PARENT_OF_TEMP_FILES_DIRECTORY, $file);

        if (is_bool($stored_file) && $stored_file === false) {
            throw new \RuntimeException("Sorry, we couldn't upload file: '{$file->getClientOriginalName()}'. Please try again.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $file_name = substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 255);
        // check if name has been taken before
        $existing_files = FileModel::select('name')->where('name', 'LIKE', "$file_name%")
            ->pluck('name')
            ->toArray();

        if (in_array($file_name, $existing_files)) {
            $counter = 1;
            while (in_array("$file_name ($counter)", $existing_files)) {
                $counter++;
            }
            $file_name = "$file_name ($counter)";
        }

        // save metadata of file to database
        $uploaded_file = new FileModel();
        $uploaded_file->fill([
            'user_id' => auth()->user()->id,
            'document_type_id' => $document_type->id ?? null,
            'path' => $stored_file,
            'name' => $file_name,
            'encrypted_name' => pathinfo($file->hashName(), PATHINFO_FILENAME),
            'size' => $file->getSize(),
            'type' => $file->getClientMimeType(),
            'extension' => strtolower($file->getClientOriginalExtension()),
        ])->save();

        return [
            $file,
            $stored_file,
            &$uploaded_file
        ];
    }

    /**
     * Handle the file upload of the specified document type if variable has valid value.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name The name of the document type.
     * @return array 
     */
    public function upload(Request $req, ?string $name = null)
    {
        // Check if the request contains a type of 'multipart/form-data' or type 'application/x-www-form-urlencoded'
        if ($req->header('Content-Type') !== 'application/x-www-form-urlencoded' || $req->header('Content-Type') !== 'multipart/form-data') {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }
        // check if file request exist
        if (!$req->hasFile('file') || empty($req->file('file'))) {
            return $this->error_response("Sorry, we couldn't find a file to upload. Please try again.");
        }

        // validate request
        $validator = Validator::make(
            $req->only(['file']),
            [
                'file' => FileRule::types(['pdf', 'png', 'jpg', 'jpeg', 'webp'])->max(20 * 1024 * 1024)
            ]
        );

        if ($validator->fails()) {
            return $this->validation_error(
                "Sorry, value of file is invalid. Please try again.",
                [
                    'errors' => error_validation_response_custom($validator->errors())
                ]
            );
        }

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

            if (empty($document_type)) return $this->error_response("Sorry, we couldn't find a document type with the name '$name'. Please try again.", null, Response::HTTP_NOT_FOUND);

            // check if directory exist 
            if (!Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/{$name}")) {
                Storage::disk('local')->makeDirectory(self::PARENT_OF_FILES_DIRECTORY . "/{$name}");
            }
        }

        // check if directory 'documents/files/temp' is exist
        if (!Storage::disk('local')->exists(self::PARENT_OF_TEMP_FILES_DIRECTORY)) {
            Storage::disk('local')->makeDirectory(self::PARENT_OF_TEMP_FILES_DIRECTORY);
        }

        try {
            DB::beginTransaction();
            list($FILE, $STORED_PATH, $FILE_MODEL) = $this->process_file('file', $req, $name, $document_type);

            $files = FileModel::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }])->where('id', $FILE_MODEL->id)->get();

            // metadata to return to client
            $metadata_uploaded_file = view('apps.files.list', ['on_upload' => true, 'files' => $files, 'document_type' => $document_type ?? null])->render();

            // links for pagination
            // $paginator = new LengthAwarePaginator([], FileModel::all()->count(), 25, Paginator::resolveCurrentPage(), [
            //     'path' => Paginator::resolveCurrentPath(),
            //     'query' => $req->all()
            // ]); // TODO: for links pagination of files

            DB::commit();

            // return response
            return $this->success_response("File: {$FILE->getClientOriginalName()} uploaded successfully.", [
                'files' => $metadata_uploaded_file,
                // 'links' => $paginator->hasPages() ? $paginator->onEachSide(2)->links('vendors.pagination.custom') : '' // TODO: for links pagination of files
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete uploaded file
            if (is_string($STORED_PATH) && Storage::disk('local')->exists($STORED_PATH)) Storage::disk('local')->delete($STORED_PATH);

            return $this->error_response("Sorry, we couldn't upload the file: {$FILE->getClientOriginalName()}. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete the specified file from the storage and database.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $req, ?string $name = null, $keep = 'keep'): RedirectResponse
    {
        $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();
            $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;

            if (empty($document_type->schema_form)) {
                return redirect()->back()->with('message', toast("Sorry, we couldn't find a document type with the name '$name'. Please try again.", 'error'));
            }

            $DYNAMIC_DOCUMENT_TYPE_MODEL = (new DynamicModel())->__setConnection('mysql')->__setTableName($document_type->table_name)->__setFillableFields(array_column($document_type->schema_form, 'name'), true);

            $DATA = $DYNAMIC_DOCUMENT_TYPE_MODEL->where('file_id', $file->id);
            if ($keep == 'erase') {
                $DATA->delete();
            } else {
                $DATA->file_id = null;
                $DATA->save();
            }
        }

        Storage::delete($file->path);
        FileModel::destroy($file->id);

        return redirect()->back()->with('message', toast("File {$file->name}.{$file->extension} has deleted successfully"));
    }

    /**
     * Download the specified file.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $req, ?string $name = null)
    {
        $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();

        if (!Storage::exists($file->path)) {
            return redirect()->back()->with('message', toast("Unable to retrieve the file {$file->name}.{$file->extension}.", 'error'));
        }

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();
        }

        return Storage::download($file->path, "{$file->name}.{$file->extension}");
    }

    /**
     * Download example file for importing data
     *
     * @param string $name The name of the document type.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse 
     */
    public function download_example_file(string $name)
    {
        $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

        // decode schema from json to array schema attributes
        $document_type->schema_form = json_decode($document_type->schema_form, true) ?? null;
        $document_type->schema_table = json_decode($document_type->schema_table, true) ?? null;

        // check table is exists
        if (!SchemaBuilder::table_exists($document_type->table_name)) {
            return redirect()->back()->with('message', toast("Sorry, we couldn't find table for document type '$name', please create a valid table/schema for document type '$name' and try again.", 'error'));
        }

        $file_name = "Example-$name-" . date("Y-m-d-His") . ".xlsx";

        // Create example excel file for importing data
        return Excel::download(new ExampleExport($file_name, SchemaBuilder::get_example_data_for_import($document_type->table_name, $document_type->schema_form)), $file_name);
    }

    /**
     * Display the specified file.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $req, ?string $name = null)
    {
        $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();

        $resources = build_resource_array(
            "Manage document files",
            "Manage document files",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing document files and displaying a list of document files.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Manage document files" => route('documents.files.root.index'),
                "$file->name.$file->extension" => URL::full()
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/documents/css/')
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/files/css/')
                ],
                [
                    'href' => 'preview.css',
                    'base_path' => asset('/resources/apps/files/css/')
                ]
            ],
            [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.10.111/pdf.min.js'
                ],
                [
                    'src' => 'https://unpkg.com/@panzoom/panzoom@4.6.0/dist/panzoom.min.js'
                ],
                [
                    'src' => 'preview.js',
                    'base_path' => asset('/resources/apps/files/js/')
                ]
            ]
        );

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

            $resources['breadcrumb'] = array_slice($resources['breadcrumb'], 0, array_search("Documents", array_keys($resources['breadcrumb'])) + 1, true)
                + ["Document type {$document_type->name}"]
                + array_slice($resources['breadcrumb'], array_search("Documents", array_keys($resources['breadcrumb'])) + 1, null, true);
        }

        $resources['file'] = $file;
        $resources['document_type'] = $document_type ?? null;

        return view('apps.files.preview', $resources);
    }

    /**
     * Stream the specified file's content for preview.
     *
     * @param string $name The encrypted name of the file to be previewed.
     * @return \Illuminate\Http\Response The streamed file content response.
     */
    public function get_file_content($name)
    {
        // get file data and checking if file exists
        $file = FileModel::where('encrypted_name', $name)->firstOrFail();

        // return file content/stream
        return response()->file(Storage::path($file->path), [
            'Content-Type' => $file->type,
            'Content-Disposition' => "inline; filename=\"{$file->name}.{$file->extension}\"",
        ]);
    }
}
