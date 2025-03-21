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

    public function profile()
    {
        $resources = build_resource_array(
            "Profile",
            "Profile",
            "<i class=\"bi bi-person-fill-gear\"></i> ",
            "Display user information or profile, on the '<mark>" . auth()->user()->name . "</mark>' user",
            [
                'Dashboard' => route('dashboard.index'),
                'Profile' => route('dashboard.profile')
            ]
        );

        return view('apps.dashboard.profile', $resources);
    }

    public function change_name(Request $req)
    {
        $req->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = User::find(auth()->id());
        $user->name = $req->name;
        $user->save();

        return redirect()->route('dashboard.profile')->with('message', toast('Name changed successfully!', 'success'));
    }

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

        return redirect()->route('dashboard.profile')->with('message', toast('Password changed successfully!', 'success'));
    }
}
