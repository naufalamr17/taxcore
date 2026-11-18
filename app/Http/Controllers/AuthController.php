<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccessService;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(): View|RedirectResponse
    {
        if (! User::exists()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request, AccessService $access): RedirectResponse
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:50'], 'password' => ['required', 'string', 'max:128']]);
        if (! Auth::attempt($data + ['is_active' => true])) {
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }
        $request->session()->regenerate();
        $access->audit($request->user(), null, 'auth.login', 'user:'.$request->user()->id);

        return redirect()->intended(route('dashboard'));
    }

    public function setup(): View
    {
        abort_if(User::exists(), 404);

        return view('auth.setup');
    }

    public function initialize(Request $request, UserService $users): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'username' => ['required', 'alpha_dash', 'max:50'], 'email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed']]);
        Auth::login($users->initialize($data));
        $request->session()->regenerate();

        return redirect()->route('companies.index')->with('status', 'Admin dibuat. Tambahkan perusahaan untuk mulai bekerja.');
    }

    public function logout(Request $request, AccessService $access): RedirectResponse
    {
        $access->audit($request->user(), null, 'auth.logout', 'user:'.$request->user()->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
