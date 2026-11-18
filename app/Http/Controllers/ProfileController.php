<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request, AccessService $access): RedirectResponse
    {
        DB::transaction(function () use ($request, $access): void {
            $user = $request->user();
            $user->fill($request->validated());
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();
            $access->audit($user, null, 'user.profile.updated', 'user:'.$user->id);
        });

        return redirect()->route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    public function password(PasswordUpdateRequest $request, AccessService $access): RedirectResponse
    {
        DB::transaction(function () use ($request, $access): void {
            $user = $request->user();
            $user->password = $request->validated('password');
            $user->remember_token = Str::random(60);
            $user->save();
            $access->audit($user, null, 'user.password.updated', 'user:'.$user->id);
        });
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('status', 'Password berhasil diperbarui.');
    }
}
