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
    /** 完了画面を送信直後の一度だけ開けるようにするための印 */
    private const SESSION_SUBMITTED = 'contact.submitted_number';

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
        $contact = $submitContact($request->safe()->all(), $request->user('web')?->id);

        $request->session()->put(self::SESSION_SUBMITTED, $contact->contact_number);

        return redirect()->route('contact.complete');
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        $contactNumber = $request->session()->pull(self::SESSION_SUBMITTED);

        if ($contactNumber === null) {
            return redirect()->route('contact');
        }

        return Inertia::render('front/Contact/Complete', [
            'contactNumber' => $contactNumber,
        ]);
    }
}
