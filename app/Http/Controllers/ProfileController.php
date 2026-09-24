<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the profile.
     */
    public function edit()
    {
        return view('profile.edit');
    }

    /**
     * Update the profile (name and email only).
     */
    public function update(ProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return back()->withStatus(__('Perfil actualizado exitosamente.'));
    }

    /**
     * Change the password. It is hashed by the User model's "hashed" cast.
     */
    public function password(PasswordRequest $request)
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return back()->withPasswordStatus(__('Contraseña actualizada exitosamente.'));
    }
}
