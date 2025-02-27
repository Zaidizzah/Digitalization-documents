<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Renders the signin page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function signin_page()
    {
        return view('apps.auth.signin');
    }

    /**
     * Renders the signup page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function signup_page()
    {
        return view('apps.auth.signup');
    }

    /**
     * Signin user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signin(Request $request)
    {
        $validator = Validator::make($request->only(['email', 'password']), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('signin.page')->with('message', toast('Invalid signin. Please fill the form correctly.', 'error'))->withInput();
        }

        $validated = $validator->validated();

        if (Auth::attempt($validated)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard')->with('message', toast('Signin was successful', 'success'));
        }

        return redirect()->route('signin.page')->with('message', toast('Invalid signin. Please fill the form correctly.', 'error'))->withInput();
    }

    /**
     * Signs up a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->only(['name', 'email', 'password', 'password_confirmation']), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->max(16)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'password_confirmation' => 'required|string|same:password'
        ]);

        if ($validator->fails()) {
            return redirect()->route('signup.page')->with('message', toast('Invalid signup. Please fill the form correctly.', 'error'))->withInput();
        }

        $validated = $validator->validated();

        User::create([
            'name' => ucwords($validated['name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'User'
        ]);

        return redirect()->route('signin.page')->with('message', toast('Signup was successful', 'success'));
    }

    /**
     * Forgot password
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgot_password(Request $request) {}

    /**
     * Logs out the current user, invalidates the session, and regenerates the session token. 
     * Redirects the user to the signin page with a success message.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('signin.page')->with('message', toast('Signout was successful', 'success'));
    }
}
