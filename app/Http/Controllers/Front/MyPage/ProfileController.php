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
                'birth_year' => $user->birthday?->format('Y') ?? '',
                'birth_month' => $user->birthday?->format('n') ?? '',
                'birth_day' => $user->birthday?->format('j') ?? '',
                'gender' => $user->gender?->value ?? '',
                'is_newsletter_subscribed' => $user->is_newsletter_subscribed,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user('web');

        $user->update([
            ...$request->safe()->except(['birth_year', 'birth_month', 'birth_day']),
            'birthday' => $request->birthday(),
            'is_newsletter_subscribed' => $request->boolean('is_newsletter_subscribed'),
        ]);

        return back()->with('success', '会員情報を更新しました');
    }
}
