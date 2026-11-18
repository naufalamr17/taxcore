<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccessController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access.manage');
        $validated = $request->validate(['edit' => ['nullable', 'integer']]);

        return view('access.index', ['users' => User::with('companies')->orderBy('name')->paginate(30), 'allCompanies' => Company::orderBy('name')->get(), 'editing' => isset($validated['edit']) ? User::with('companies')->findOrFail($validated['edit']) : null, 'overrides' => RolePermission::all()->groupBy('role'), 'events' => AuditEvent::with('user')->whereNull('company_id')->latest('id')->limit(30)->get()]);
    }

    public function store(UserRequest $request, UserService $users): RedirectResponse
    {
        $users->save($request->user(), $request->validated());

        return back()->with('status', 'Pengguna ditambahkan.');
    }

    public function update(UserRequest $request, User $user, UserService $users): RedirectResponse
    {
        $users->save($request->user(), $request->validated(), $user);

        return redirect()->route('access.index')->with('status', 'Pengguna diperbarui.');
    }

    public function permissions(Request $request, UserService $users): RedirectResponse
    {
        Gate::authorize('access.manage');
        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['array'], 'permissions.*.*' => ['boolean']]);
        $users->permissions($request->user(), $data['permissions'] ?? []);

        return back()->with('status', 'Hak akses peran diperbarui.');
    }
}
