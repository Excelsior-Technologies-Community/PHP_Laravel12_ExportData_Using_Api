<?php
// app/Models/ExportSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportSchedule extends Model
{
    protected $fillable = [
        'format', 'frequency', 'filters', 'email', 'last_run', 'next_run', 'is_active'
    ];
    
    protected $casts = [
        'filters' => 'array',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'is_active' => 'boolean'
    ];
}