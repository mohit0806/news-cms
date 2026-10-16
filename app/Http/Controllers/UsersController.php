<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'active' => ['nullable', 'in:yes,no'],
            'sort' => ['nullable', 'in:name,email,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,15,25,50'],
        ]);

        if (isset($validated['per_page'])) {
            session(['users.per_page' => $validated['per_page']]);
        }

        $users = User::query()
            ->when(! empty($validated['search']), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('email', 'like', '%'.$validated['search'].'%')
            ))
            ->when(! empty($validated['role']), fn ($query) => $query->role($validated['role']))
            ->when(isset($validated['active']), fn ($query) => $query->where(
                'is_active', $validated['active'] === 'yes'
            ))
            ->with('roles')
            ->orderBy($validated['sort'] ?? 'created_at', $validated['direction'] ?? 'desc')
            ->paginate(session('users.per_page', 15))
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $validated,
            'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(10), 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('status', __('User created.'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
            'currentRole' => $user->getRoleNames()->first(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Unchecked switches submit nothing; boolean() maps absence to false.
        $request->merge(['is_active' => $request->boolean('is_active')]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'locale' => ['nullable', 'string', 'max:12'],
            'is_active' => ['required', 'boolean'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        if (! $validated['is_active']) {
            $this->ensureDeactivatable($user, $request->user());
        }

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'locale' => $validated['locale'],
            'is_active' => $validated['is_active'],
        ])->save();

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('status', __('User updated.'));
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->ensureDeactivatable($user, $request->user());

        $user->forceFill(['is_active' => false])->save();

        return back()->with('status', __('User deactivated.'));
    }

    public function activate(User $user): RedirectResponse
    {
        $user->forceFill(['is_active' => true])->save();

        return back()->with('status', __('User activated.'));
    }

    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $users = User::query()->whereKey($validated['ids'])->get();

        foreach ($users as $user) {
            $this->ensureDeactivatable($user, $request->user());
            $user->forceFill(['is_active' => false])->save();
        }

        return back()->with('status', __('Selected users deactivated.'));
    }

    /**
     * Nobody may deactivate themselves or the last active administrator:
     * both would lock the install out of admin access.
     */
    private function ensureDeactivatable(User $target, mixed $actor): void
    {
        abort_if($actor instanceof User && $target->is($actor), 422, 'You cannot deactivate your own account.');

        if (! $target->hasRole('Admin')) {
            return;
        }

        $otherActiveAdmins = User::query()
            ->where('is_active', true)
            ->whereKeyNot($target->getKey())
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->exists();

        abort_unless($otherActiveAdmins, 422, 'The last active administrator cannot be deactivated.');
    }
}
