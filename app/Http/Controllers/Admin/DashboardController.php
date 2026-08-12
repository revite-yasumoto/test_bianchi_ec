<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardSummaryService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardSummaryService $summaryService) {}

    public function index(): Response
    {
        return Inertia::render('admin/Dashboard/Index', [
            'summary' => $this->summaryService->summary(),
            'chart' => $this->summaryService->salesChart(),
            'latestOrders' => $this->summaryService->latestOrders(),
        ]);
    }
}
