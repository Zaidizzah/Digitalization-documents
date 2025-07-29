<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function index(Request $request)
    {
        $resources = build_resource_array(
            // List of data for the page
            'Manage users',
            'Manage users',
            '<i class="bi bi-people"></i> ',
            'A page for managing users, displaying a list of users, and performing actions with CRUD(Create, Read, Update, Delete) operations.',
            [
                'Dashboard' => route('dashboard.index'),
                'Manage users' => route('users.index')
            ],
            [
                [
                    'href' => 'styles.css',
                    'base_path' => asset('resources/apps/user/css/')
                ]
            ],
            [
                [
                    'src' => 'scripts.js',
                    'base_path' => asset('resources/apps/user/js/')
                ]
            ],
        );

        // List of users
        $resources['users'] = User::where('role', 'User')
            ->when($request->search ?? false, function ($query, $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('created_at', 'like', "%$search%")
                    ->orWhere('updated_at', 'like', "%$search%")
                    ->orWhereRaw("DATE_FORMAT(created_at, '%d %F %Y %H:%i %A') LIKE ?", ["%$search%"])
                    ->orWhereRaw("DATE_FORMAT(updated_at, '%d %F %Y %H:%i %A') LIKE ?", ["%$search%"]);
            })
            ->paginate(25)->withQueryString();

        return view('apps.user.index', $resources);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|string|max:76|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->max(16)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'password_confirmation' => 'required|string|same:password'
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.index')->with('message', toast('Invalid creating user data. Please fill the form correctly.', 'error'))->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        $user = new User();
        $user->name = ucwords($validated['name']);
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->role = 'User';
        $user->save();

        return redirect()->route('users.index')->with('message', toast('User created successfully!', 'success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->only(['name', 'email']), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|string|max:76|unique:users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.index')->with('message', toast('Invalid updating user data. Please fill the form correctly.', 'error'))->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        $user->name = ucwords($validated['name']);
        $user->email = $validated['email'];
        $user->update();

        return redirect()->route('users.index')->with('message', toast('User updated successfully!', 'success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();

        return redirect()->route('users.index')->with('message', toast('User deleted successfully!', 'success'));
    }

    /**
     * Get user data by ID.
     *
     * GET /users/{id}
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get__user_data(Request $request, string $id)
    {
        // check if request is json request
        if ($request->wantsJson() === false) {
            return $this->error_response('Invalid request', null, Response::HTTP_BAD_REQUEST);
        }

        $user = User::select('id', 'name', 'email')->find($id);
        if ($user === null) {
            return $this->not_found_response('User data not found, maybe the user has been deleted or data is corrupted.');
        }

        return $this->success_response("User data has been found", ['user' => $user->toArray()]);
    }
}
