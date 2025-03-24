<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $resources = build_resource_array(
            "Dashboard",
            "Dashboard",
            "<i class=\"bi bi-speedometer\"></i> ",
            "A page for displaying a statistic of the system or application.",
            [
                'Dashboard' => route('dashboard.index')
            ]
        );

        return view('apps.dashboard.index', $resources);
    }

    /**
     * Display the authenticated user's profile information.
     *
     * This method retrieves the currently authenticated user and constructs
     * a resource array containing profile information to be displayed on
     * the profile view page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function profile()
    {
        $user = auth()->user();
        $resources = build_resource_array(
            "Profile",
            "Profile",
            "<i class=\"bi bi-person-fill-gear\"></i> ",
            "Display user information or profile, on the '<mark>" . $user->name . "</mark>' user",
            [
                'Dashboard' => route('dashboard.index'),
                'Profile' => route('dashboard.profile')
            ]
        );

        return view('apps.dashboard.profile', $resources);
    }

    /**
     * Handle the incoming request for changing name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function change_name(Request $req)
    {
        $req->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = User::find(auth()->id());
        $user->name = $req->name;
        $user->save();

        return redirect()->route('dashboard.profile')->with('message', toast('Your name has changed successfully!'));
    }

    /**
     * Handle the incoming request for changing password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function change_password(Request $req)
    {
        $req->validate([
            'password_new' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->max(16)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'password_confirmation' => 'required|string|same:password'
        ]);

        $user = User::find(auth()->id());
        $user->password = Hash::make($req->password_new);
        $user->save();

        return redirect()->route('dashboard.profile')->with('message', toast('Your password has changed successfully!'));
    }
}
