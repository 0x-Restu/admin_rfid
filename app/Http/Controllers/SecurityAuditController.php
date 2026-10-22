<?php

namespace App\Http\Controllers;

use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;

class SecurityAuditController extends Controller
{
    /**
     * Display live security & audit trail
     */
    public function index(Request $request)
    {
        $query = SecurityAuditLog::with('user')->latest();

        if ($request->filled('severity') && $request->severity !== 'ALL') {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('target_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => SecurityAuditLog::count(),
            'critical' => SecurityAuditLog::where('severity', 'CRITICAL')->count(),
            'warning' => SecurityAuditLog::where('severity', 'WARNING')->count(),
            'blocked' => SecurityAuditLog::where('status', 'BLOCKED')->count(),
        ];

        return view('admin.security.index', compact('logs', 'stats'));
    }
}
