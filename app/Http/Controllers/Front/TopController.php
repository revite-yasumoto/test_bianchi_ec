<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TopController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('front/Top/Index');
    }
}
