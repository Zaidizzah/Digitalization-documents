<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentType;
use App\Models\File as FileModel;
use App\Traits\ApiResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OcrService;

class FileController extends Controller
{
    use ApiResponse;

    private const PARENT_OF_DOCUMENTS_DIRECTORY = 'documents';
    private const PARENT_OF_FILES_DIRECTORY = 'documents/files';
    private const PARENT_OF_TEMP_FILES_DIRECTORY = 'documents/files/temp';
    private $VALIDATION_RULES;

    public function __construct()
    {
        $this->VALIDATION_RULES = [
            'file' => FileRule::types(['pdf', 'png', 'jpg', 'jpeg', 'webp'])->max(20 * 1024 * 1024),
        ];
    }

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
                    'src' => 'modals.js',
                    'base_path' => asset('/resources/apps/files/js/')
                ]
            ]
        );

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();

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
        }])->filesWithFilter($req->only(['type', 'search']), $name)->orderBy('created_at', 'desc')->paginate(25)->appends($req->all());

        // If request is Fetch request send paginating files data to client
        if ($req->ajax()) {
            return $this->success_response("Files loaded successfully.", [
                'files' => view('apps.files.list', ['on_upload' => false, 'files' => $files, 'document_type' => $document_type ?? null])->render(),
            ]);
        }

        $resources['files'] = $files;
        $resources['document_types'] = DocumentType::orderBy('created_at', 'desc')->where('is_active', 1)->get();
        $resources['document_type'] = $document_type ?? null;

        return view('apps.files.index', $resources);
    }

    public function extract_text_from_file(string $file_path)
    {
        return File::get($file_path);
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
     * Handle the file upload of the specified document type if variable has valid value.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name The name of the document type.
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $req, ?string $name = null)
    {
        if (auth()->user()->role !== 'Admin') {
            return $this->error_response("You do not have permission to access this resources.", null, Response::HTTP_FORBIDDEN);
        }

        // check if file request exist
        if (!$req->hasFile('file')) {
            return $this->error_response("Sorry, we couldn't find a file to upload. Please try again.");
        }

        // validate request
        $validator = Validator::make($req->all(), $this->VALIDATION_RULES);

        if ($validator->fails()) {
            return $this->validation_error(
                "Sorry, value of file is invalid. Please try again.",
                error_validation_response_custom($validator->errors())
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
            // upload file
            $file = $req->file('file');

            // store file with encrypted name
            $stored_file = Storage::disk('local')->put($name ? self::PARENT_OF_FILES_DIRECTORY . "/{$name}" : self::PARENT_OF_TEMP_FILES_DIRECTORY, $file);

            DB::beginTransaction();
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
                'extension' => $file->getClientOriginalExtension(),
            ])->save();

            $files = FileModel::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }])->where('id', $uploaded_file->id)->get();

            // metadata to return to client
            $metadata_uploaded_file = view('apps.files.list', ['on_upload' => true, 'files' => $files, 'document_type' => $document_type ?? null])->render();

            // links for pagination
            $paginator = new LengthAwarePaginator([], FileModel::all()->count(), 25, Paginator::resolveCurrentPage(), [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $req->all()
            ]);

            DB::commit();

            // return response
            return $this->success_response("File: {$file->getClientOriginalName()} uploaded successfully.", [
                'files' => $metadata_uploaded_file,
                'links' => $paginator->hasPages() ? $paginator->onEachSide(2)->links('vendors.pagination.custom') : ''
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete uploaded file
            if (is_string($stored_file) && Storage::disk('local')->exists($stored_file)) Storage::disk('local')->delete($stored_file);

            return $this->error_response("Sorry, we couldn't upload the file: {$file->getClientOriginalName()}. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete the specified file from the storage and database.
     *
     * @param \Illuminate\Http\Request $req
     * @param ?string $name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $req, ?string $name = null, $keep = 'keep')
    {
        $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();
            $data = DB::table($document_type->table_name)->where('file_id', $file->id);
            if($keep == 'erase'){
                $data->delete();
            }else{
                $data->update(['file_id' => null]);
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
            return $this->error_response("Unable to retrieve the file at location: {$file->path}.", null, Response::HTTP_NOT_FOUND);
        }

        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->firstOrFail();
        }

        return Storage::download($file->path, "{$file->name}.{$file->extension}");
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
        $file = FileModel::where('encrypted_name', $name)->firstOrFail();

        return response()->stream(function () use ($file) {
            echo Storage::get($file->path);
        }, 200, [
            'Content-Type' => $file->type,
            'Content-Disposition' => 'inline; filename="pengarsipan.pdf"',
        ]);
    }
}
