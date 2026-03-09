<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $branches = Branch::where('is_active', true)->get();
        return view('reports.index', compact('branches'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:daily,weekly,monthly,annual',
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
            'branch_id'   => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'format'      => 'required|in:pdf,excel',
        ]);

        $records = AttendanceRecord::with('employee', 'branch')
            ->whereBetween('date', [$validated['date_from'], $validated['date_to']])
            ->when($validated['branch_id'] ?? null, fn ($q) => $q->where('branch_id', $validated['branch_id']))
            ->when($validated['employee_id'] ?? null, fn ($q) => $q->where('employee_id', $validated['employee_id']))
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $summary = [
            'total'    => $records->count(),
            'present'  => $records->where('status', 'present')->count(),
            'late'     => $records->where('status', 'late')->count(),
            'absent'   => $records->where('status', 'absent')->count(),
            'on_leave' => $records->where('status', 'on_leave')->count(),
            'date_from' => $validated['date_from'],
            'date_to'   => $validated['date_to'],
        ];

        if ($validated['format'] === 'pdf') {
            $pdf = Pdf::loadView('reports.attendance-pdf', compact('records', 'summary', 'validated'))
                ->setPaper('a4', 'landscape');
            return $pdf->download('attendance-report-' . now()->format('Y-m-d') . '.pdf');
        }

        // Excel via Maatwebsite
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($records, $summary),
            'attendance-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function download(string $type)
    {
        return redirect()->route('reports.index');
    }
}