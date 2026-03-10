<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index()
    {
        return view('audit-logs.index');
    }

    public function data(Request $request)
    {
        $query = Activity::with('causer');

        // Column search filters passed as custom params
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhereHas('causer', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($event = $request->input('filter_event')) {
            $query->where('event', $event);
        }
        if ($causer = $request->input('filter_causer')) {
            $query->whereHas('causer', fn ($u) => $u->where('name', 'like', "%{$causer}%"));
        }
        if ($from = $request->input('filter_from')) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $request->input('filter_to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $total    = Activity::count();
        $filtered = $query->count();

        $query->latest();

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $logs   = $query->skip($start)->take($length)->get();

        $badgeClass = fn ($event) => match ($event) {
            'created' => 'bg-label-success',
            'updated' => 'bg-label-primary',
            'deleted' => 'bg-label-danger',
            default   => 'bg-label-secondary',
        };

        $data = $logs->map(function ($log) use ($badgeClass) {
            $timestamp = '<div>' . e($log->created_at->format('M d, Y')) . '</div>'
                . '<small class="text-muted">' . e($log->created_at->format('h:i A')) . '</small>';

            $event = '<span class="badge ' . $badgeClass($log->event) . '">' . ucfirst(e($log->event ?? 'N/A')) . '</span>';

            if ($log->causer) {
                $user = '<div class="fw-semibold">' . e($log->causer->name) . '</div>'
                    . '<small class="text-muted">' . e($log->causer->email) . '</small>';
            } else {
                $user = '<span class="text-muted">System</span>';
            }

            $subject = '<small class="text-muted">' . e(class_basename($log->subject_type ?? '')) . '</small>'
                . ($log->subject_id ? '<div>#' . $log->subject_id . '</div>' : '');

            $props = $log->properties ? json_encode($log->properties, JSON_PRETTY_PRINT) : null;
            $details = '<button type="button" class="btn btn-sm btn-icon btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#logModal"
                data-description="' . e($log->description) . '"
                data-event="' . e(ucfirst($log->event ?? '')) . '"
                data-user="' . e($log->causer->name ?? 'System') . '"
                data-timestamp="' . e($log->created_at->format('F d, Y h:i:s A')) . '"
                data-subject-type="' . e($log->subject_type ?? '—') . '"
                data-subject-id="' . e($log->subject_id ?? '—') . '"
                data-properties="' . e($props ?? '') . '"
                ><i class="bi bi-eye"></i></button>';

            return [
                'timestamp'   => $timestamp,
                'event'       => $event,
                'description' => e($log->description),
                'user'        => $user,
                'subject'     => $subject,
                'details'     => $details,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->values(),
        ]);
    }
}
