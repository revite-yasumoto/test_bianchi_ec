<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Front\Top\TopPageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopController extends Controller
{
    public function index(Request $request, TopPageService $service): Response
    {
        /** @var User|null $user */
        $user = $request->user('web');

        return Inertia::render('front/Top/Index', $service->build($user));
    }
}
