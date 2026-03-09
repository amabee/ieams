@extends('layouts.app')
@section('title','Database Backups')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-bold">Database Backups</h4>
    @can('run-backup')
    <form method="POST" action="{{ route('backups.run') }}">
        @csrf
        <button type="submit" class="btn btn-primary" onclick="return confirm('Run database backup now?')">
            <i class="bi bi-hdd me-1"></i> Run Backup Now
        </button>
    </form>
    @endcan
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Automated Backups:</strong> Database backups run automatically every day at 2:00 AM via scheduled task.
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold">Backup History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Backup Date</th>
                        <th>File Name</th>
                        <th>File Size</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                    <tr>
                        <td>{{ $backup->backup_date->format('M d, Y') }}</td>
                        <td><code class="small">{{ $backup->file_name }}</code></td>
                        <td>{{ number_format($backup->file_size / 1024, 2) }} KB</td>
                        <td>
                            @if($backup->status === 'success')
                            <span class="badge bg-success">Success</span>
                            @else
                            <span class="badge bg-danger">Failed</span>
                            @endif
                        </td>
                        <td><small>{{ $backup->created_at->format('M d, Y h:i A') }}</small></td>
                        <td>
                            @if($backup->status === 'success' && Storage::exists('backups/'.$backup->file_name))
                            <a href="{{ route('backups.download', $backup) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download"></i> Download
                            </a>
                            @can('delete-backup')
                            <form method="POST" action="{{ route('backups.destroy', $backup) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this backup?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcan
                            @else
                            <span class="text-muted small">File not available</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No backups found. Run your first backup now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($backups->hasPages())
    <div class="card-footer bg-white">
        {{ $backups->links() }}
    </div>
    @endif
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Backup Settings</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Backup Schedule</dt>
                    <dd class="col-sm-7">Daily at 2:00 AM</dd>
                    
                    <dt class="col-sm-5">Backup Location</dt>
                    <dd class="col-sm-7"><code>storage/app/backups/</code></dd>
                    
                    <dt class="col-sm-5">Retention Policy</dt>
                    <dd class="col-sm-7">Keep last 30 backups</dd>
                    
                    <dt class="col-sm-5">Backup Type</dt>
                    <dd class="col-sm-7 mb-0">SQL dump (MySQL)</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold">Backup Guidelines</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 small">
                    <li>Backups are created automatically every day at 2:00 AM</li>
                    <li>Manual backups can be triggered via the "Run Backup Now" button</li>
                    <li>Download backups to external storage for additional safety</li>
                    <li>Old backups (beyond 30 days) are automatically deleted</li>
                    <li>Restore backups using database management tools (e.g., phpMyAdmin)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
