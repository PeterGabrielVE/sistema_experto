<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users
     */
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(15)]);
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('users.create', ['roles' => Role::options()]);
    }

    /**
     * Store a newly created user in storage.
     * The password is hashed by the User model's "hashed" cast.
     */
    public function store(UserRequest $request)
    {
        User::create($request->validated());

        return redirect()->route('user.index')->withStatus(__('Usuario creado correctamente.'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::options()]);
    }

    /**
     * Update the specified user in storage. An empty password keeps the current one.
     */
    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('user.index')->withStatus(__('Usuario actualizado correctamente.'));
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()->route('user.index')->withStatus(__('Usuario eliminado correctamente.'));
    }

    public function chart()
    {
        $months = [];
        for($i = 1;$i <= 12; $i++){
            $result = User::orderBy('created_at', 'ASC')
                ->whereMonth('created_at','=',$i)
                ->count();

            $month = [ $i => $result ];
            $monts = array_push($months,$month);
        }

        return response()->json($months);
    }

    public function downloadManualPdf()
    {
        $file= public_path(). "/download/manual_usuario.pdf";

        $headers = array(
                'Content-Type: application/pdf',
                );

        return Response::download($file, 'manual_usuario.pdf', $headers);
    }
}
