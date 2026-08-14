<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Contact\StoreContactRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user('web');

        // 商品詳細の「この商品について問い合わせる」から渡る商品名。
        // 未認証で到達でき、配列や任意長の値を渡されうるため、文字列のみを入力欄と同じ上限で受ける
        $productName = $request->query('product_name');

        return Inertia::render('front/Contact/Create', [
            'defaults' => [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'product_name' => is_string($productName) ? Str::substr($productName, 0, 255) : '',
            ],
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        Contact::query()->create([
            ...$request->safe()->all(),
            'user_id' => $request->user('web')?->id,
        ]);

        return back()->with('success', '送信しました。3営業日以内にご返信いたします。');
    }
}
