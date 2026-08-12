<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Front\Cart\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request, CartService $service): Response
    {
        /** @var User $user */
        $user = $request->user('web');

        return Inertia::render('front/Cart/Index', $service->build($user));
    }
}
