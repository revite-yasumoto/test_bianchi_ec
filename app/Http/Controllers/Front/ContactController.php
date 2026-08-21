<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Actions\Front\Contact\ResolveContactProduct;
use App\Actions\Front\Contact\SubmitContact;
use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Contact\StoreContactRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(Request $request, ResolveContactProduct $resolveProduct): Response
    {
        /** @var User|null $user */
        $user = $request->user('web');

        $product = $resolveProduct($request->query('product_id'));

        return Inertia::render('front/Contact/Create', [
            'defaults' => [
                'name' => $user?->name ?? '',
                'email' => $user?->email ?? '',
                'product_name' => '',
            ],
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);
    }

    public function store(StoreContactRequest $request, SubmitContact $submitContact): RedirectResponse
    {
        $submitContact($request->safe()->all(), $request->user('web')?->id);

        return back()->with('success', '送信しました。3営業日以内にご返信いたします。');
    }
}
