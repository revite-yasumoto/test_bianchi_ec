<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Auth;

use App\Actions\Front\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /** 完了画面を登録直後の一度だけ開けるようにするための印 */
    private const SESSION_REGISTERED = 'auth.registered';

    public function create(): Response
    {
        return Inertia::render('front/Auth/Register');
    }

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $registerUser([
            'name' => $request->string('name')->toString(),
            'name_kana' => $request->input('name_kana'),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $request->session()->put(self::SESSION_REGISTERED, true);

        // intended() を呼ばないことで、購入手続きから来た場合の遷移先を後続のログインまで残す
        return redirect()->route('register.complete');
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->pull(self::SESSION_REGISTERED, false)) {
            return redirect()->route('login');
        }

        return Inertia::render('front/Auth/RegisterComplete');
    }
}
