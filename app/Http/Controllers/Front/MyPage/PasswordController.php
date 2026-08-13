<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\MyPage\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('front/MyPage/Password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $user->update(['password' => $request->string('password')->value()]);

        return back()->with('success', 'パスワードを変更しました');
    }
}
