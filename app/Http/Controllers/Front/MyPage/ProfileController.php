<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\MyPage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\MyPage\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        return Inertia::render('front/MyPage/Profile', [
            'profile' => [
                'name' => $user->name,
                'name_kana' => $user->name_kana,
                'email' => $user->email,
                'tel' => $user->tel,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $user->update($request->safe()->all());

        return back()->with('success', '会員情報を更新しました');
    }
}
