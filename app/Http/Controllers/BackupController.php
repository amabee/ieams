<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function index()
    {
        $backups = Backup::latest()->paginate(15);
        return view('backups.index', compact('backups'));
    }

    public function run()
    {
        Artisan::call('backup:run');
        return back()->with('success', 'Backup created successfully.');
    }

    public function download(Backup $backup)
    {
        $path = storage_path('app/backups/' . $backup->filename);
        if (!file_exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }
        return response()->download($path);
    }

    public function destroy(Backup $backup)
    {
        $path = storage_path('app/backups/' . $backup->filename);
        if (file_exists($path)) {
            @unlink($path);
        }
        $backup->delete();
        return back()->with('success', 'Backup deleted.');
    }
}
