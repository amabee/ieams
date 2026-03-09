<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendanceExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Collection $records,
        private array $summary
    ) {}

    public function collection(): Collection
    {
        return $this->records->map(fn ($r) => [
            'Date'          => $r->date->format('Y-m-d'),
            'Employee No'   => $r->employee->employee_no ?? '—',
            'Employee Name' => $r->employee->full_name ?? '—',
            'Branch'        => $r->branch->name ?? '—',
            'Time In'       => $r->time_in ?? '—',
            'Time Out'      => $r->time_out ?? '—',
            'Hours Worked'  => $r->hours_worked ?? '—',
            'Status'        => ucfirst(str_replace('_', ' ', $r->status)),
            'Notes'         => $r->notes ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Date', 'Employee No', 'Employee Name', 'Branch', 'Time In', 'Time Out', 'Hours Worked', 'Status', 'Notes'];
    }

    public function title(): string
    {
        return 'Attendance Report';
    }
}
