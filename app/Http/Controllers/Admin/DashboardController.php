<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetrics;
use App\Services\FeeBook;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics, FeeBook $book): View
    {
        // Coaches have no finance.view, so money never reaches the response —
        // it is not merely hidden with CSS.
        $admin = auth('admin')->user();
        $showFinance = (bool) $admin?->hasAbility('finance.view');

        // Birthday heads-up is owner/admin info only.
        $showBirthdays = (bool) $admin?->hasRole(\App\Models\Admin::ROLE_OWNER, \App\Models\Admin::ROLE_ADMIN);

        $charts = [
            'studentGrowth' => $metrics->studentGrowth(),
            'admissions' => $metrics->monthlyAdmissions(),
            'attendance' => $metrics->attendanceBreakdown(),
            'attendanceTrend' => $metrics->attendanceTrend(),
            'batches' => $metrics->batchDistribution(),
            'coaches' => $metrics->coachDistribution(),
            'studentStatus' => $metrics->activeVsInactive(),
        ];

        if ($showFinance) {
            $charts['revenue'] = $metrics->revenueVsPending();
        }

        return view('admin.dashboard', [
            'showFinance' => $showFinance,
            // Plain-language fee summary for the owner; absent for coaches.
            'feeOverview' => $showFinance ? $book->summary(now()->startOfMonth()) : null,
            'kpis' => $metrics->kpis($showFinance),
            'widgets' => $metrics->widgets(),
            'charts' => $charts,
            'upcomingEvents' => $metrics->upcomingEvents(),
            'birthdays' => $showBirthdays ? $metrics->birthdays() : collect(),
            'recentPayments' => $showFinance ? $metrics->recentPayments() : collect(),
            'topDefaulters' => $showFinance ? $metrics->topDefaulters() : collect(),
        ]);
    }
}
