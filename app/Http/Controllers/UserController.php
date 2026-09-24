<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(15)]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return view('users.create', ['roles' => Role::options()]);
    }

    public function store(UserRequest $request)
    {
        $this->users->create($request->validated(), $request->user());

        return redirect()->route('user.index')->withStatus(__('Usuario creado correctamente.'));
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', ['user' => $user, 'roles' => Role::options()]);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->users->update($user, $request->validated(), $request->user());

        return redirect()->route('user.index')->withStatus(__('Usuario actualizado correctamente.'));
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $this->users->delete($user);

        return redirect()->route('user.index')->withStatus(__('Usuario eliminado correctamente.'));
    }

    public function downloadManualPdf()
    {
        return Response::download(public_path('download/manual_usuario.pdf'), 'manual_usuario.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
