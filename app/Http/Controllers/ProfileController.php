<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'mustVerifyEmail' => $user instanceof MustVerifyEmail,
                'hasVerifiedEmail' => method_exists($user, 'hasVerifiedEmail') ? $user->hasVerifiedEmail() : true,
            ],
            'urls' => [
                'dashboard' => route('dashboard'),
                'profile' => route('profile.edit'),
                'updateProfile' => route('profile.update'),
                'updatePassword' => route('password.update'),
                'deleteProfile' => route('profile.destroy'),
                'sendVerification' => route('verification.send'),
                'whatsapp' => route('whatsapp.index'),
                'imports' => route('imports.index'),
                'ai' => route('ai.index'),
            ],
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(DeleteUserRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
