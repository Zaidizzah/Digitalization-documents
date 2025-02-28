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
use League\CommonMark\Node\Block\Document;

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

        $file_data = FileModel::filesWithFilter($req->search, $req->type, $name);
        $inputs = [
            'type' => $req->type,
            'search' => $req->search,
        ];

        if ($req->ajax()) {
            return view('apps.files.list', compact('file_data'))->render();
        }
        
        $resources['type'] = $name;
        $resources['input'] = $inputs;
        $resources['files'] = $file_data;
        $resources['document'] = DocumentType::where('is_active', 1)->get();

        return view('apps.files.index', $resources);
    }

    public function extract_text_from_file(string $file_path)
    {
        return File::get($file_path);
    }

    /**
     * Handle Edit filename and document type.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rename(Request $req)
    {
        $req->validate([
            'name' => 'required|unique:files,name,'.$req->id.',id',
            'id' => 'required|exists:files,id',
            'document_type_id' => 'exists:document_types,id',
        ]);

        $file = FileModel::find($req->id);
        $file->name = $req->name;
        $file->document_type_id = $req->document_type_id;
        $file->save();

        return redirect()->back()->with('message', toast('File has been renamed successfully'));
    }

    /**
     * Handle the file upload of the specified document type if variable has valid value.
     *
     * @param string|null $name The name of the document type.
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request, ?string $name = null)
    {
        if (auth()->user()->role !== 'Admin') {
            return $this->error_response("You do not have permission to access this resources.", null, Response::HTTP_FORBIDDEN);
        }

        // check if file request exist
        if (!$request->hasFile('file')) {
            return $this->error_response("Sorry, we couldn't find a file to upload. Please try again.");
        }

        // validate request
        $validator = Validator::make($request->all(), $this->VALIDATION_RULES);

        if ($validator->fails()) {
            return $this->validation_error(
                "Sorry, value of file is invalid. Please try again.",
                error_validation_response_custom($validator->errors())
            );
        }

        if ($request->document_type) {
            $document_type = DocumentType::where('name', $request->document_type)->where('is_active', 1)->first();

            if (empty($document_type)) return $this->error_response("Sorry, we couldn't find a document type with the name '$request->document_type'. Please try again.", null, Response::HTTP_NOT_FOUND);

            // check if directory exist 
            if (!Storage::disk('local')->exists(self::PARENT_OF_FILES_DIRECTORY . "/{$request->document_type}")) {
                Storage::disk('local')->makeDirectory(self::PARENT_OF_FILES_DIRECTORY . "/{$request->document_type}");
            }
        }

        // check if directory 'documents/files/temp' is exist
        if (!Storage::disk('local')->exists(self::PARENT_OF_TEMP_FILES_DIRECTORY)) {
            Storage::disk('local')->makeDirectory(self::PARENT_OF_TEMP_FILES_DIRECTORY);
        }

        try {
            // upload file
            $file = $request->file('file');

            // store file with encrypted name
            $stored_file = Storage::disk('local')->put($request->document_type ? self::PARENT_OF_FILES_DIRECTORY . "/{$request->document_type}" : self::PARENT_OF_TEMP_FILES_DIRECTORY, $file);

            DB::beginTransaction();
            if (is_bool($stored_file) && $stored_file === false) {
                throw new \RuntimeException("Sorry, we couldn't upload the file: '{$file->getClientOriginalName()}'. Please try again.", Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $file_name = substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 0, 255);
            // check if name has been taken before
            $existingFiles = FileModel::select('name')->where('name', 'LIKE', "$file_name%")
                ->pluck('name')
                ->toArray();

            if (in_array($file_name, $existingFiles)) {
                $counter = 1;
                while (in_array("$file_name ($counter)", $existingFiles)) {
                    $counter++;
                }
                $file_name = "$file_name ($counter)";
            }

            // save metadata of file
            $file_data = [
                'user_id' => auth()->user()->id,
                'document_type_id' => $document_type->id ?? null,
                'path' => $stored_file,
                'name' => $file_name,
                'encrypted_name' => pathinfo($file->hashName(), PATHINFO_FILENAME),
                'size' => $file->getSize(),
                'type' => $file->getClientMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ];

            // save metadata of file to database
            $uploaded_file = new FileModel();
            $uploaded_file->fill($file_data);
            $uploaded_file->save();

            // metadata to return to client
            $metadata_uploaded_file = view('apps.files.list', array('file_data' => array($uploaded_file)))->render();

            DB::commit();

            // return response
            return $this->success_response("File: {$file->getClientOriginalName()} uploaded successfully.", ['file' => $metadata_uploaded_file], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete uploaded file
            if (is_string($stored_file) && Storage::disk('local')->exists($stored_file)) Storage::disk('local')->delete($stored_file);

            return $this->error_response("Sorry, we couldn't upload the file. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $req, $name = null){
        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

            if (empty($document_type)) {
                return redirect()->back()->with('message', toast("Sorry, we couldn't find a document type with the name '$name'. Please try again.", 'error'));
            }
        }

        try {
            $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();
            Storage::delete($file->path);
    
            FileModel::destroy($file->id);
            return redirect()->back()->with('message', toast("File {$file->name}.{$file->extension} has deleted successfully"));
        } catch (\Throwable $th) {
            return redirect()->back()->with('message', toast("Sorry, we have trouble while deleting this file: ".$th->getMessage(), 'error'));
        }
    }
    public function download(Request $req, $name = null){
        if ($name) {
            $document_type = DocumentType::where('name', $name)->where('is_active', 1)->first();

            if (empty($document_type)) {
                return redirect()->back()->with('message', toast("Sorry, we couldn't find a document type with the name '$name'. Please try again.", 'error'));
            }
        }

        try {
            $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();
            return Storage::download($file->path, $file->name.'.'.$file->extension);
        } catch (\Throwable $th) {
            return redirect()->back()->with('message', toast("Sorry, we have trouble while downloading this file: ".$th->getMessage(), 'error'));
        }
    }
    public function preview(Request $req, $name = null){
        $file = FileModel::where('encrypted_name', $req->file)->firstOrFail();
        $resources = build_resource_array(
            "Manage document files",
            "Manage document files",
            "<i class=\"bi bi-file-earmark-text\"></i> ",
            "A page for managing document files and displaying a list of document files.",
            [
                "Dashboard" => route('dashboard.index'),
                "Documents" => route('documents.index'),
                "Manage document files" => route('documents.files.index')
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
        $resources['file'] = $file;
        return view('apps.files.preview', $resources);
    }
    public function files($name, $filename){
        $file = FileModel::where('encrypted_name', $name)->firstOrFail();
        return response(Storage::get($file->path), headers:[
            'Content-type' => $file->type
        ]);
    }
}
