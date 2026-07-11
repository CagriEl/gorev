<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dashboard(Request $request, ReportDashboardService $reports): JsonResponse
    {
        return response()->json($reports->build($request->user()));
    }
}
