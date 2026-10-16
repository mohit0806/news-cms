<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('roles.index', ['roles' => $roles]);
    }
}
