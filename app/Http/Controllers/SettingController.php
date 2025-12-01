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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Cache;

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
            $request->only(['file', 'documents']),
            [
                'file' => FileRule::types(['avif', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'])->max(20 * 1024 * 1024),
                'documents' => 'nullable|string|exists:files,en'
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

        $DOCUMENT_TYPE = null;
        if (empty($name) !== true) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = Cache::remember("list:userguides", 60, function () use ($DOCUMENT_TYPE) {
            $USER_GUIDES = UserGuides::without(['document_type', 'parent', 'children']);

            if (isset($DOCUMENT_TYPE)) $USER_GUIDES = $USER_GUIDES->where('document_type_id', $DOCUMENT_TYPE->id);
            else $USER_GUIDES = $USER_GUIDES->whereNull('document_type_id');
            return $USER_GUIDES->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->toArray();
        });

        $P__PER_PAGE = 10;
        $P__PAGE = $request->get('page', 1);
        $P__OFFSET = ($P__PAGE - 1) * $P__PER_PAGE;

        $DATA = new LengthAwarePaginator(
            UserGuides::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }, 'children' => function ($query) {
                $query->orderBy('title', 'desc');
            }])->when(isset($DOCUMENT_TYPE), function ($query) use ($DOCUMENT_TYPE) {
                $query->where('document_type_id', $DOCUMENT_TYPE->id);
            }, function ($query) {
                $query->whereNull('document_type_id');
            })->whereNull('parent_id')
                ->orderBy('created_at', 'desc')->whereIn('id', array_slice($DATA, $P__OFFSET, $P__PER_PAGE))->get(),
            count($DATA),
            $P__PER_PAGE,
            $P__PAGE,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        // Check if user guide not found or NULL
        if ($DATA->isEmpty()) {
            return $this->not_found_response("List of user guide data's not found.");
        }

        return $this->success_response("List of user guide data's has successfully found.", ['html' => view('partials.userguide-create-edit-tree-item', ['USER_GUIDES' => $DATA, 'DOCUMENT_TYPE' => $DOCUMENT_TYPE ?? NULL])->render()]);
    }

    public function __get_user_guide_content(Request $request, int $id)
    {
        // Check if content type of request is want json
        if ($request->wantsJson() === FALSE) {
            return $this->error_response("Invalid request", null, Response::HTTP_BAD_REQUEST);
        }

        $USER_GUIDE = UserGuides::with(['document_type' => function ($query) {
            $query->select('id', 'name', 'long_name');
        }, 'children' => function ($query) {
            $query->orderBy('title', 'desc');
        }])->where('id', $id)->first();

        // Check if user guide not found or NULL
        if (empty($USER_GUIDE)) {
            return $this->not_found_response("User guide data with ID {$id} is not found.");
        }

        return $this->success_response("User guide data has successfully loaded.", ['content' => $USER_GUIDE->content]);
    }

    public function user_guide__index(Request $request, ?string $name = null)
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

        $DOCUMENT_TYPE = null;
        if (empty($name) !== true) {
            $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();

            $resources['breadcrumb'] = array_merge(
                array_slice($resources['breadcrumb'], 0, 1, true),
                [
                    'Documents' => route('documents.index'),
                    "Document type {$DOCUMENT_TYPE->name}"
                ],
                array_slice($resources['breadcrumb'], 1, 1, true)
            );

            // Change correct route for 'User Guides' element in breadcrumb
            $resources['breadcrumb']['User Guide'] = route('userguides.index.named', $name);
        }
        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = Cache::remember("list:userguides", 60, function () use ($DOCUMENT_TYPE) {
            $USER_GUIDES = UserGuides::without(['document_type', 'parent', 'children']);

            if (isset($DOCUMENT_TYPE)) $USER_GUIDES = $USER_GUIDES->where('document_type_id', $DOCUMENT_TYPE->id);
            else $USER_GUIDES = $USER_GUIDES->whereNull('document_type_id');
            return $USER_GUIDES->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->toArray();
        });

        $P__PER_PAGE = 10;
        $P__PAGE = $request->get('page', 1);
        $P__OFFSET = ($P__PAGE - 1) * $P__PER_PAGE;

        $DATA = new LengthAwarePaginator(
            UserGuides::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }, 'children' => function ($query) {
                $query->orderBy('title', 'desc');
            }])->when(isset($DOCUMENT_TYPE), function ($query) use ($DOCUMENT_TYPE) {
                $query->where('document_type_id', $DOCUMENT_TYPE->id);
            }, function ($query) {
                $query->whereNull('document_type_id');
            })->whereNull('parent_id')
                ->orderBy('created_at', 'desc')->whereIn('id', array_slice($DATA, $P__OFFSET, $P__PER_PAGE))->get(),
            count($DATA),
            $P__PER_PAGE,
            $P__PAGE,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        $resources['user_guides'] = $DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.index', $resources);
    }

    public function user_guide__activate(mixed ...$params)
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
                    $query->where('name', $name)->where('is_active', 0);
                });
            });
        })->where('is_active', 0)->findOrFail($id);

        $USER_GUIDE->is_active = 1; // Change visibility status active
        $USER_GUIDE->save();

        return redirect()->back()->with('message', toast("User guide status has succesfully changed to 'Active'", 'success'));
    }

    public function user_guide__deactivate(mixed ...$params)
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
        })->where('is_active', 1)->findOrFail($id);

        $USER_GUIDE->is_active = 0; // Change visibility status inactive
        $USER_GUIDE->save();

        return redirect()->back()->with('message', toast("User guide status has succesfully changed to 'Inactive'", 'success'));
    }

    public function user_guide__create(Request $request, ?string $name = null)
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

        $DOCUMENT_TYPE = NULL;
        if (empty($name) !== true) {
            $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();

            $resources['breadcrumb'] = array_merge(
                array_slice($resources['breadcrumb'], 0, 1, true),
                [
                    'Documents' => route('documents.index'),
                    "Document type {$DOCUMENT_TYPE->name}"
                ],
                array_slice($resources['breadcrumb'], 1, 2, true)
            );

            // Change correct route for 'User Guides' and 'Create' element in breadcrumb
            $resources['breadcrumb']['User Guide'] = route('userguides.index.named', $name);
            $resources['breadcrumb']['Create'] = route('userguides.create.named', $name);
        }

        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = Cache::remember("list:userguides", 60, function () use ($DOCUMENT_TYPE) {
            $USER_GUIDES = UserGuides::without(['document_type', 'parent', 'children']);

            if (isset($DOCUMENT_TYPE)) $USER_GUIDES = $USER_GUIDES->where('document_type_id', $DOCUMENT_TYPE->id);
            else $USER_GUIDES = $USER_GUIDES->whereNull('document_type_id');
            return $USER_GUIDES->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->toArray();
        });

        $P__PER_PAGE = 10;
        $P__PAGE = $request->get('page', 1);
        $P__OFFSET = ($P__PAGE - 1) * $P__PER_PAGE;

        $DATA = new LengthAwarePaginator(
            UserGuides::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }, 'children' => function ($query) {
                $query->orderBy('title', 'desc');
            }])->when(isset($DOCUMENT_TYPE), function ($query) use ($DOCUMENT_TYPE) {
                $query->where('document_type_id', $DOCUMENT_TYPE->id);
            }, function ($query) {
                $query->whereNull('document_type_id');
            })->whereNull('parent_id')
                ->orderBy('created_at', 'desc')->whereIn('id', array_slice($DATA, $P__OFFSET, $P__PER_PAGE))->get(),
            count($DATA),
            $P__PER_PAGE,
            $P__PAGE,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        $resources['user_guides'] = $DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.create-edit', $resources);
    }

    public function user_guide__store(Request $request, ?string $name = null)
    {
        if ($name !== null) $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();

        // Get document_type_id from parent list 
        if ($request->input('parent_id') !== null) {
            $PARENT_GUIDE = UserGuides::when($name !== NULL, function ($query) use ($name) {
                $query->where(function ($query) use ($name) {
                    $query->whereHas('document_type', function ($query) use ($name) {
                        $query->where('name', $name)->where('is_active', 1);
                    });
                });
            })->find((int) $request->input('parent_id'));

            if ($PARENT_GUIDE === NULL) {
                return redirect()->back()->with('message', toast('Parent for new user guide is not found. Please choose another valid parent user guide.', 'error'))->withInput();
            }
        }

        $request->validate([
            'title' => 'required|string|max:150',
            'parent_id' => 'nullable|integer|exists:user_guides,id',
            'content' => 'required|string|max:4294967295' // Max length for LONGTEXT type is 4294967295 characters
        ]);

        $PATH = Str::slug($request->input('title'));
        if (isset($PARENT_GUIDE)) {
            $PATH = "{$PARENT_GUIDE->path}/$PATH";
            // Check if path already exist
            if (UserGuides::where('parent_id', $PARENT_GUIDE->id)->where('is_active', 1)->where('path', $PATH)->exists()) {
                return redirect()->back()->with('message', toast('New user guide already exist or duplicated. Please try again or change with another title.', 'error'))->withInput();
            }
        }

        $DATA = new UserGuides();

        if (isset($PARENT_GUIDE)) {
            if ($PARENT_GUIDE !== NULL && $PARENT_GUIDE->document_type_id !== NULL) $DATA->document_type_id = $PARENT_GUIDE->document_type_id;
        }

        $DATA->title = $request->input('title');
        $DATA->path = $PATH;
        $DATA->parent_id = $request->input('parent_id');
        $DATA->document_type_id = $DOCUMENT_TYPE->id ?? NULL;
        $DATA->content = $request->input('content');
        $DATA->save();

        return redirect()->route(($name !== null ? 'userguides.index.named' : 'userguides.index'), $name ?? [])->with('message', toast('User guide was created successfully!', 'success'));
    }

    public function user_guide__edit(Request $request, mixed ...$params)
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
        $CURRENT_DATA = UserGuides::select('id', 'parent_id', 'title', 'path', 'content')->findOrFail($id);
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
                ]
            ]
        );

        $DOCUMENT_TYPE = null;
        if (empty($name) !== true) {
            $DOCUMENT_TYPE = DocumentType::where('is_active', 1)->where('name', $name)->firstOrFail();

            $resources['breadcrumb'] = array_merge(
                array_slice($resources['breadcrumb'], 0, 1, true),
                [
                    'Documents' => route('documents.index'),
                    "Document type {$DOCUMENT_TYPE->name}"
                ],
                array_slice($resources['breadcrumb'], 1, 2, true)
            );

            // Change correct route for 'User Guides' and 'Create' element in breadcrumb
            $resources['breadcrumb']['User Guide'] = route('userguides.index.named', $name);
            $resources['breadcrumb']['Edit'] = route('userguides.edit.named', [$name, $id]);
        }

        // Get user guides data just ID, PARENT_ID, and TITLE fields
        $DATA = Cache::remember("list:userguides", 60, function () use ($DOCUMENT_TYPE) {
            $USER_GUIDES = UserGuides::without(['document_type', 'parent', 'children']);

            if (isset($DOCUMENT_TYPE)) $USER_GUIDES = $USER_GUIDES->where('document_type_id', $DOCUMENT_TYPE->id);
            else $USER_GUIDES = $USER_GUIDES->whereNull('document_type_id');
            return $USER_GUIDES->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->pluck('id')
                ->toArray();
        });

        $P__PER_PAGE = 10;
        $P__PAGE = $request->get('page', 1);
        $P__OFFSET = ($P__PAGE - 1) * $P__PER_PAGE;

        $DATA = new LengthAwarePaginator(
            UserGuides::with(['document_type' => function ($query) {
                $query->select('id', 'name', 'long_name');
            }, 'children' => function ($query) {
                $query->orderBy('title', 'desc');
            }])->when(isset($DOCUMENT_TYPE), function ($query) use ($DOCUMENT_TYPE) {
                $query->where('document_type_id', $DOCUMENT_TYPE->id);
            }, function ($query) {
                $query->whereNull('document_type_id');
            })->whereNull('parent_id')
                ->orderBy('created_at', 'desc')->whereIn('id', array_slice($DATA, $P__OFFSET, $P__PER_PAGE))->get(),
            count($DATA),
            $P__PER_PAGE,
            $P__PAGE,
            [
                'path' => $request->url(),
                'query' => $request->query()
            ]
        );

        $resources['user_guides'] = $DATA;
        $resources['CURRENT_DATA'] = $CURRENT_DATA;
        $resources['document_type'] = $DOCUMENT_TYPE ?? NULL;

        return view('apps.userguides.create-edit', $resources);
    }

    public function user_guide__update(Request $request, mixed ...$params)
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

        // Get document_type_id from parent list 
        if ($request->input('parent_id') !== null) {
            $PARENT_GUIDE = UserGuides::when($name !== NULL, function ($query) use ($name) {
                $query->where(function ($query) use ($name) {
                    $query->whereHas('document_type', function ($query) use ($name) {
                        $query->where('name', $name)->where('is_active', 1);
                    });
                });
            })->find((int) $request->input('parent_id'));
            dd($request->input('parent_id'), $PARENT_GUIDE);

            if ($PARENT_GUIDE === NULL) {
                return redirect()->back()->with('message', toast('Parent for current user guide is not found. Please choose another valid parent user guide.', 'error'))->withInput();
            }
        }

        // Check if path already exist
        $PATH = Str::slug($request->input('title'));
        if (isset($PARENT_GUIDE)) {
            $PATH = "{$PARENT_GUIDE->path}/$PATH";
            dd($PATH, $PARENT_GUIDE);
            // Check if path already exist
            if (UserGuides::where('parent_id', $PARENT_GUIDE->id)->where('is_active', 1)->where('id', '!=', $USER_GUIDE->id)->where('path', $PATH)->exists()) {
                return redirect()->back()->with('message', toast('New user guide already exist or duplicated. Please try again or change with another title.', 'error'))->withInput();
            }
        }

        $USER_GUIDE->title = $request->input('title');
        $USER_GUIDE->path = $PATH;
        if ($USER_GUIDE->parent_id !== (int) $request->input('parent_id') && $USER_GUIDE->id !== (int) $request->input('parent_id')) $USER_GUIDE->parent_id = $request->input('parent_id'); // Prevent updating parent_id if it's not changed and it's not same with the ID
        $USER_GUIDE->content = $request->input('content');
        $USER_GUIDE->is_active = 1;
        $USER_GUIDE->save();

        // Checking route type for redirecting user to correct route
        return redirect()->route($name ? "userguides.update.named" : 'userguides.edit', $name ? [$name, $USER_GUIDE->id] : $USER_GUIDE->id)->with('message', toast('User guide was updated successfully!', 'success'));
    }

    public function user_guide__destroy(mixed ...$params)
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

    public function user_guide__show(?string $path = null)
    {
        $breadcrumbs = [
            'Dashboard' => route('dashboard.index'),
            'User Guide' => route('userguides.index'),
            'Index' => route('userguides.show.index')
        ];

        if ($path !== null) {
            // Unset the last element from the array breadcrumbs
            unset($breadcrumbs[array_key_last($breadcrumbs)]);

            $USER_GUIDE = UserGuides::with('document_type')->where('path', $path)->firstOrFail();

            // Change correct route for 'User Guides' element in breadcrumb
            if ($USER_GUIDE->document_type instanceof \App\Models\DocumentType) {
                $breadcrumbs = array_merge(
                    array_slice($breadcrumbs, 0, 1, true),
                    [
                        'Documents' => route('documents.index'),
                        "Document type {$USER_GUIDE->document_type->name}"
                    ],
                    array_slice($breadcrumbs, 1, 1, true)
                );

                $breadcrumbs['User Guide'] = route('userguides.index.named', $USER_GUIDE->document_type->name);
            }

            $segments = explode('/', $USER_GUIDE->path);
            $pathBuild = "";

            foreach ($segments as $i => $segment) {
                $pathBuild .= ($i > 0 ? '/' : '') . $segment;

                if ($i === array_key_last($segments)) {
                    $breadcrumbs[ucwords(str_replace('-', ' ', $segment))] = route('userguides.show.dynamic', [$pathBuild]);
                } else {
                    $breadcrumbs[ucwords(str_replace('-', ' ', $segment))] = route('userguides.show.dynamic', [$pathBuild]);
                }
            }
        }

        // Cache lists menu of user guide
        Cache::forget('menu:userguides');
        $USER_GUIDE_LISTS_MENU = Cache::rememberForever('menu:userguides', function () {
            return UserGuides::with(['document_type' => function ($query) {
                $query->select('id', 'name')->where('is_active', 1);
            }, 'children' => function ($query) {
                $query->select('id', 'parent_id', 'title', 'path')->where('is_active', 1)->with(['children' => function ($query) {
                    $query->select('id', 'parent_id', 'title', 'path')->where('is_active', 1);
                }]);
            }])->whereNull('parent_id')->select('id', 'title', 'path')->where('is_active', 1)->get()->toArray();
        });

        $resources = build_resource_array(
            "User Guide - " . (isset($USER_GUIDE) ? $USER_GUIDE->title : "Index"),
            "User Guide - " . (isset($USER_GUIDE) ? $USER_GUIDE->title : "Index"),
            '<i class="bi bi-code-square"></i> ',
            "A page for displaying a list of content for the user guide: " . (isset($USER_GUIDE) ? $USER_GUIDE->title : "Index") . ".",
            $breadcrumbs,
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
                    'href' => 'show.css',
                    'base_path' => asset('resources/apps/userguides/css/')
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
                    'src' => 'show.js',
                    'base_path' => asset('resources/apps/userguides/js/')
                ]
            ]
        );
        if (isset($USER_GUIDE)) {
            $resources['user_guide'] = $USER_GUIDE;
        }
        $resources['USER_GUIDE_LISTS_MENU'] = $USER_GUIDE_LISTS_MENU;
        // dd($resources['USER_GUIDE_LISTS_MENU']);

        return view('apps/userguides.show', $resources);
    }
}
