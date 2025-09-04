<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File as FileRule;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\File as FileModel;
use App\Models\UserGuides;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\DocumentType;
use App\Traits\ApiResponse;
use Illuminate\Routing\Events\RouteMatched;

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
        // Chek header value of "Sec-Fetch-Dest"
        if ($request->header('Sec-Fetch-Dest') !== 'image') {
            abort(403, 'Forbidden');
        }

        // Chek Accept headers
        if (! str_contains($request->header('Accept', ''), 'image')) {
            abort(406, 'Not Acceptable');
        }

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
                ],
                [
                    'href' => 'styles.css',
                    'base_path' => asset('/resources/apps/setting/css/')
                ]
            ]
        );

        return view('apps.settings.index', $resources);
    }

    public function __get_user_guide_create_edit__tree_items(Request $request, ?string $name = null)
    {
        // Check if content type of request is want json
        if ($request->wantsJson() === FALSE) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }

        if (empty($name) !== true) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = UserGuides::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }, 'children' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        if (isset($DOCUMENT_TYPE)) $DATA = $DATA->where('document_type_id', $DOCUMENT_TYPE->id);
        else $DATA = $DATA->whereNull('document_type_id');
        $DATA = $DATA->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        // Check if user guide not found or NULL
        if ($DATA->isEmpty()) {
            return $this->not_found_response("List of user guide data's not found.");
        }

        return $this->success_response("List of user guide data's has successfully found.", ['html' => view('partials.userguide-create-edit-tree-item', ['USER_GUIDES' => $DATA, 'DOCUMENT_TYPE' => $DOCUMENT_TYPE ?? NULL])->render()]);
    }

    public function user_guide__index(?string $name = null)
    {
        $resources = build_resource_array(
            'User Guide',
            'User Guide',
            '<i class="bi bi-code-square"></i> ',
            'A page for displaying lists of content for the user guide.',
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
                    'href' => 'styles.css',
                    'base_path' => asset('resources/apps/userguides/css/')
                ]
            ],
            [
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('resources/apps/userguides/js/')
                ]
            ]
        );

        if (empty($name) !== true) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = UserGuides::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }, 'children' => function ($query) {
            $query->orderBy('title', 'desc');
        }]);

        if (isset($DOCUMENT_TYPE)) $DATA = $DATA->where('document_type_id', $DOCUMENT_TYPE->id);
        else $DATA = $DATA->whereNull('document_type_id');
        $DATA = $DATA->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        $resources['user_guides'] = $DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.index', $resources);
    }

    public function user_guide__activate(...$params)
    {
        // Check if length of $params is more than 2
        if (count($params) > 2) {
            abort(404);
        }

        $id = $name = null;
        for ($i = 0; $i < count($params); $i++) {
            if (empty($params[$i]) !== true && is_numeric($params[$i])) {
                $id = (int)$params[$i];
            } else {
                $name = $params[$i];
            }
        }

        $USER_GUIDE = UserGuides::when($name !== NULL, function ($query) use ($name) {
            $query->where(function ($query) use ($name) {
                $query->whereHas('document_type', function ($query) use ($name) {
                    $query->where('name', $name)->where('is_active', 1);
                });
            });
        })->findOrFail($id);

        $status = $USER_GUIDE->is_active === 0 ? "Inactivate" : "Activate";
        $USER_GUIDE->is_active = $status === "Activate" ? 0 : 1; // Change visibility status (active & inactive)
        $USER_GUIDE->save();

        return redirect()->back()->with('message', toast("User guide status has succesfully changed to $status", 'success'));
    }

    public function user_guide__create(?string $name = null)
    {
        $resources = build_resource_array(
            // List of data for the page
            'User Guide',
            'User Guide',
            '<i class="bi bi-code-square"></i> ',
            'A page for configuring the user guide.',
            [
                'Dashboard' => route('dashboard.index'),
                'User Guide' => route('userguides.index'),
                'Create' => route('userguides.create')
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
                    'href' => 'texteditorhtml.css',
                    'base_path' => asset('/resources/plugins/texteditorhtml/css/')
                ],
                [
                    'href' => 'create-edit.css',
                    'base_path' => asset('/resources/apps/userguides/css/')
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
                    'src' => 'texteditorhtml.js',
                    'base_path' => asset('/resources/plugins/texteditorhtml/js/')
                ],
                [
                    'src' => 'create-edit.js',
                    'base_path' => asset('/resources/apps/userguides/js/')
                ]
            ]
        );

        if (empty($name) !== true) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = UserGuides::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }, 'children' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        if (isset($DOCUMENT_TYPE)) {
            $DATA = $DATA->where('document_type_id', $DOCUMENT_TYPE->id);
            $resources['breadcrumb'] = array_merge(array_slice($resources['breadcrumb'], 0, 1, true), ['Documents' => route('documents.index'), "Document type {$DOCUMENT_TYPE->name}"], array_slice($resources['breadcrumb'], 1, null, true));
        } else {
            $DATA = $DATA->whereNull('document_type_id');
        }
        $DATA = $DATA->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        $resources['user_guides'] = $DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.create-edit', $resources);
    }

    public function user_guide__store(Request $request, ?string $name = null)
    {
        if ($name !== null) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();

        // Get document_type_id from parent list 
        if ($request->get('parent_id') !== null) {
            $PARENT_GUIDE = UserGuides::where(function ($query) use ($name) {
                $query->whereHas('document_type', function ($query) use ($name) {
                    $query->where('name', $name)->where('is_active', 1);
                });
            })->findOrFail((int) $request->input('parent_id'));
        }

        $request->validate([
            'title' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:user_guides,id',
            'content' => 'required|string|max:4294967295' // Max length for LONGTEXT type is 4294967295 characters
        ]);

        $SLUG = Str::slug($request->input('title'));
        // Check if slug already exist
        if (UserGuides::where('slug', $SLUG)->exists()) {
            return redirect()->back()->with('message', toast('User guide title already exist!', 'error'))->withInput();
        }

        $DATA = new UserGuides();

        if (isset($PARENT_GUIDE)) {
            if ($PARENT_GUIDE !== NULL && $PARENT_GUIDE->document_type_id !== NULL) $DATA->document_type_id = $PARENT_GUIDE->document_type_id;
        }

        $DATA->title = $request->input('title');
        $DATA->slug = $SLUG;
        $DATA->parent_id = $request->input('parent_id');
        $DATA->document_type_id = $DOCUMENT_TYPE->id ?? NULL;
        $DATA->content = $request->input('content');
        $DATA->save();

        return redirect()->route(($name !== null ? 'userguides.create.named' : 'userguides.create'), $name ?? [])->with('message', toast('User guide was created successfully!', 'success'));
    }

    public function user_guide__edit(mixed ...$params)
    {
        // Check if length of $params is more than 2
        if (count($params) > 2) {
            abort(404);
        }

        $id = $name = null;
        for ($i = 0; $i < count($params); $i++) {
            if (empty($params[$i]) !== true && is_numeric($params[$i])) {
                $id = (int)$params[$i];
            } else {
                $name = $params[$i];
            }
        }

        // Check if $ID and $NAME variable is not empty or NULL 
        $CURRENT_DATA = UserGuides::select('id', 'parent_id', 'title', 'slug', 'content')->findOrFail($id);
        $FORMATED_CONTENT = $CURRENT_DATA->content ? str_replace('`', '\`', e($CURRENT_DATA->content)) : ""; // Formatting or encode special char to prevent an error
        $resources = build_resource_array(
            // List of data for the page
            'User Guide',
            'User Guide',
            '<i class="bi bi-code-square"></i> ',
            'A page for modifyng the current user guide data.',
            [
                'Dashboard' => route('dashboard.index'),
                'User Guide' => route('userguides.index'),
                'Edit' => route('userguides.edit', $id)
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
                    'href' => 'texteditorhtml.css',
                    'base_path' => asset('/resources/plugins/texteditorhtml/css/')
                ],
                [
                    'href' => 'create-edit.css',
                    'base_path' => asset('/resources/apps/userguides/css/')
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
                    'src' => 'texteditorhtml.js',
                    'base_path' => asset('/resources/plugins/texteditorhtml/js/')
                ],
                [
                    'src' => 'create-edit.js',
                    'base_path' => asset('/resources/apps/userguides/js/')
                ],
                [
                    'inline' => <<<JS
                        TEXT_EDITOR_HTML.setValue(`{$FORMATED_CONTENT}`)
                    JS
                ]
            ]
        );

        if (empty($name) !== true) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = UserGuides::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }, 'children' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        if (isset($DOCUMENT_TYPE)) {
            $DATA = $DATA->where('document_type_id', $DOCUMENT_TYPE->id);
            $resources['breadcrumb'] = array_merge(array_slice($resources['breadcrumb'], 0, 1, true), ['Documents' => route('documents.index'), "Document type {$DOCUMENT_TYPE->name}"], array_slice($resources['breadcrumb'], 1, null, true));
        } else {
            $DATA = $DATA->whereNull('document_type_id');
        }
        $DATA = $DATA->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        $resources['user_guides'] = $DATA;
        $resources['CURRENT_DATA'] = $CURRENT_DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.create-edit', $resources);
    }

    public function user_guide__update(Request $request, ...$params)
    {
        // Check if length of $params is more than 2
        if (count($params) > 2) {
            abort(404);
        }

        $id = $name = null;
        for ($i = 0; $i < count($params); $i++) {
            if (empty($params[$i]) !== true && is_numeric($params[$i])) {
                $id = (int)$params[$i];
            } else {
                $name = $params[$i];
            }
        }

        $USER_GUIDE = UserGuides::when($name !== NULL, function ($query) use ($name) {
            $query->where(function ($query) use ($name) {
                $query->whereHas('document_type', function ($query) use ($name) {
                    $query->where('name', $name)->where('is_active', 1);
                });
            });
        })->findOrFail($id);

        $request->validate([
            'title' => "required|string|max:150|unique:user_guides,title,{$USER_GUIDE->id},id",
            'parent_id' => 'nullable|integer|exists:user_guides,id',
            'content' => 'required|string|max:4294967295' // Max length for LONGTEXT type is 4294967295 characters
        ]);

        // Check if slug already exist
        if (UserGuides::where('slug', Str::slug($request->input('title')))->where('id', '!=', $USER_GUIDE->id)->exists()) {
            return redirect()->back()->with('message', toast('User guide title already exist!', 'error'))->withInput();
        }

        $USER_GUIDE->title = $request->input('title');
        if ($USER_GUIDE->parent_id !== (int) $request->input('parent_id') && $USER_GUIDE->id !== (int) $request->input('parent_id')) $USER_GUIDE->parent_id = $request->input('parent_id') ?? NULL; // Prevent updating parent_id if it's not changed and it's not same with the ID
        $USER_GUIDE->content = $request->input('content');
        $USER_GUIDE->is_active = 1;
        $USER_GUIDE->save();

        // Checking route type for redirecting user to correct route
        if (route_check("userguides.update")) {
            return redirect()->route('userguides.edit', $USER_GUIDE->id)->with('message', toast('User guide was updated successfully!', 'success'));
        }
        return redirect()->route('userguides.edit.named', [$name, $USER_GUIDE->id])->with('message', toast('User guide was updated successfully!', 'success'));
    }

    public function user_guide__destroy(...$params)
    {
        // Check if length of $params is more than 2
        if (count($params) > 2) {
            abort(404);
        }

        $id = $name = null;
        for ($i = 0; $i < count($params); $i++) {
            if (empty($params[$i]) !== true && is_numeric($params[$i])) {
                $id = (int)$params[$i];
            } else {
                $name = $params[$i];
            }
        }

        $USER_GUIDE = UserGuides::when($name !== NULL, function ($query) use ($name) {
            $query->where(function ($query) use ($name) {
                $query->whereHas('document_type', function ($query) use ($name) {
                    $query->where('name', $name)->where('is_active', 1);
                });
            });
        })->findOrFail($id);
        $USER_GUIDE->delete();

        return redirect()->back()->with('message', toast('User guide was deleted successfully!', 'success'));
    }
}
