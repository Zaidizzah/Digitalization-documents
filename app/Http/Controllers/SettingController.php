<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\File as FileModel;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class SettingController extends Controller
{
    use ApiResponse;

    private const PARENT_OF_SETTINGS_DIRECTORY = 'settings';

    public function upload(Request $request)
    {
        // Check if the request contains a type of 'multipart/form-data' or type 'application/x-www-form-urlencoded'
        if (strpos($request->header('Content-Type'), 'multipart/form-data') === FALSE) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }
        // check if file request exist
        if (!$request->hasFile('file') || empty($request->file('file'))) {
            return $this->error_response("Sorry, we couldn't find a file to upload. Please try again.");
        }

        // validate request
        $validator = Validator::make(
            $request->only(['file']),
            [
                'file' => FileRule::types(['avif', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'])->max(20 * 1024 * 1024)
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

        // Check if directory 'settings' is exist
        if (!Storage::disk('local')->exists(self::PARENT_OF_SETTINGS_DIRECTORY)) {
            Storage::disk('local')->makeDirectory(self::PARENT_OF_SETTINGS_DIRECTORY);
        }

        // upload file
        $FILE = $request->file('file');

        try {
            // store file with encrypted name
            $STORED_PATH = Storage::disk('local')->put(self::PARENT_OF_SETTINGS_DIRECTORY, $FILE);

            if (is_bool($STORED_PATH) && $STORED_PATH === false) {
                return $this->error_response("Sorry, we couldn't upload file: '{$FILE->getClientOriginalName()}'. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $file_name = substr(pathinfo($FILE->getClientOriginalName(), PATHINFO_FILENAME), 0, 255);
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

            DB::beginTransaction();
            $upload_file = new FileModel();
            $upload_file->fill([
                'user_id' => auth()->user()->id,
                'document_type_id' => NULL,
                'user_guide_id' => NULL,
                'path' => $STORED_PATH,
                'name' => $file_name,
                'encrypted_name' => pathinfo($FILE->hashName(), PATHINFO_FILENAME),
                'size' => $FILE->getSize(),
                'type' => $FILE->getClientMimeType(),
                'extension' => strtolower($FILE->getClientOriginalExtension()),
            ]);
            $upload_file->save();
            DB::commit();

            return $this->success_response("File uploaded successfully", ['path' => route('userguide.content', $upload_file->encrypted_name), 'filename' => $FILE->getClientOriginalName()], Response::HTTP_OK);
        } catch (\Exception $error) {
            // rollback if any error occur
            if (DB::transactionLevel() > 0) DB::rollBack();

            // delete uploaded file
            if (is_string($STORED_PATH) && Storage::disk('local')->exists($STORED_PATH)) Storage::disk('local')->delete($STORED_PATH);

            return $this->error_response("Sorry, we couldn't upload the file: {$FILE->getClientOriginalName()}. Please try again.", null, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Stream the specified file's content for preview.
     *
     * @param string $name The encrypted name of the file to be previewed.
     * @return \Illuminate\Http\Response The streamed file content response.
     */
    public function __get_file_content(Request $request, $encrypted)
    {
        // get file data and checking if file exists
        $file = FileModel::where('encrypted_name', $encrypted)->firstOrFail();

        // Check if file exist
        if (Storage::disk('local')->exists($file->path) === FALSE) {
            abort(404);
        }

        // return file content/stream
        return response()->file(Storage::path($file->path), [
            'Content-Type' => $file->type,
            'Content-Disposition' => "inline; filename=\"{$file->name}.{$file->extension}\"",
        ]);
    }

    /**
     * Display a listing of the resource for user guide page.
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function index(Request $request)
    {
        $resources = build_resource_array(
            // List of data for the page
            'Configuration / Settings',
            'Configuration / Settings',
            '<i class="bi bi-gear"></i> ',
            'A page for configuring the application varibles system.',
            [
                'Dashboard' => route('dashboard.index'),
                'Settings' => route('settings.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ]
            ]
        );

        return view('apps.settings.index', $resources);
    }

    public function user_guide(Request $request)
    {
        $resources = build_resource_array(
            // List of data for the page
            'User Guide',
            'User Guide',
            '<i class="bi bi-code-square"></i> ',
            'A page for configuring the user guide.',
            [
                'Dashboard' => route('dashboard.index'),
                'User Guide' => route('userguides.index')
            ],
            [
                [
                    'href' => 'menu.css',
                    'base_path' => asset('/resources/apps/')
                ],
                [
                    'href' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/default.min.css'
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/plugins/texteditorhtml/css/')
                ]
            ],
            [
                [
                    'src' => 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js',
                ],
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/highlightjs-line-numbers.js@2.8.0/dist/highlightjs-line-numbers.min.js',
                ],
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/showdown@2.1.0/dist/showdown.min.js',
                ],
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/plugins/texteditorhtml/js/')
                ],
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('/resources/apps/userguides/js/')
                ]
            ]
        );

        return view('apps.userguides.index', $resources);
    }
}
