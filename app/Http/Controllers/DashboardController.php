<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CanteenTransaction;
use App\Models\RfidCard;
use App\Models\SecurityAuditLog;
use App\Models\SppBill;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // 1. Core KPIs
        $totalStudents = Student::count();
        $activeCardsCount = RfidCard::where('status', 'ACTIVE')->count();
        $totalCirculatingBalance = RfidCard::sum('balance');

        $gateTodayCount = Attendance::whereDate('date', $today)->count();
        $gateLateCount = Attendance::whereDate('date', $today)->where('is_late', true)->count();

        $canteenTodaySum = CanteenTransaction::whereDate('created_at', $today)
            ->where('status', 'SUCCESS')
            ->sum('amount');
        $canteenTodayCount = CanteenTransaction::whereDate('created_at', $today)
            ->where('status', 'SUCCESS')
            ->count();

        $unpaidSppSum = SppBill::where('status', 'UNPAID')->sum('amount');
        $unpaidSppCount = SppBill::where('status', 'UNPAID')->count();

        $securityAlertsCount = SecurityAuditLog::whereIn('severity', ['WARNING', 'CRITICAL'])
            ->whereDate('created_at', $today)
            ->count();

        // 2. Recent Feeds
        $recentCanteen = CanteenTransaction::with('student', 'card')
            ->latest()
            ->take(6)
            ->get();

        $recentGate = Attendance::with('student')
            ->whereDate('date', $today)
            ->latest('tap_in_time')
            ->take(6)
            ->get();

        $recentSecurityLogs = SecurityAuditLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.index', compact(
            'totalStudents',
            'activeCardsCount',
            'totalCirculatingBalance',
            'gateTodayCount',
            'gateLateCount',
            'canteenTodaySum',
            'canteenTodayCount',
            'unpaidSppSum',
            'unpaidSppCount',
            'securityAlertsCount',
            'recentCanteen',
            'recentGate',
            'recentSecurityLogs'
        ));
    }
}
