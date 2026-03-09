<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends Model
{
    protected $fillable = [
        'branch_id', 'forecast_date', 'predicted_absenteeism_rate',
        'predicted_absent_count', 'model_used', 'confidence_level', 'generated_at',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'generated_at'  => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
