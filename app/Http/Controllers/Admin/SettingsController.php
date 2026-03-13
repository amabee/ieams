<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $allowed = [
            'organization_name', 'contact_email', 'address',
            'late_threshold_minutes', 'grace_period_minutes', 'auto_absent_hours',
            'late_alert_threshold', 'absent_alert_threshold', 'alert_emails',
            'forecast_alpha', 'forecast_beta', 'forecast_gamma', 'forecast_horizon',
            'annual_sick_leave', 'annual_vacation_leave', 'annual_emergency_leave', 'annual_other_leave',
        ];

        foreach ($allowed as $key) {
            SystemSetting::set($key, $request->input($key, ''));
        }

        // Checkbox — unchecked means it's absent from the request, treat as 0
        SystemSetting::set('require_time_out', $request->has('require_time_out') ? '1' : '0');

        return back()->with('success', 'Settings saved.');
    }
}
