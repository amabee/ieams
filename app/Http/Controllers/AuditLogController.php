<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = Activity::with('causer')
            ->when($request->search, fn ($q) => $q->where('description', 'like', "%{$request->search}%"))
            ->when($request->date_from, fn ($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn ($q) => $q->where('created_at', '<=', $request->date_to . ' 23:59:59'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}