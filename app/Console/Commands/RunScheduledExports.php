<?php
// app/Console/Commands/RunScheduledExports.php

namespace App\Console\Commands;

use App\Models\ExportSchedule;
use App\Jobs\ExportProductsJob;
use App\Models\ExportLog;
use Illuminate\Console\Command;

class RunScheduledExports extends Command
{
    protected $signature = 'exports:run-scheduled';
    protected $description = 'Run scheduled exports';
    
    public function handle()
    {
        $schedules = ExportSchedule::where('is_active', true)
            ->where('next_run', '<=', now())
            ->get();
        
        foreach ($schedules as $schedule) {
            $exportLog = ExportLog::create([
                'export_type' => 'scheduled',
                'format' => $schedule->format,
                'filters' => $schedule->filters,
                'status' => 'pending',
                'user_email' => $schedule->email,
                'filename' => ''
            ]);
            
            ExportProductsJob::dispatch(
                $schedule->filters ?? [], 
                $schedule->format, 
                $exportLog->id, 
                $schedule->email
            );
            
            $schedule->update([
                'last_run' => now(),
                'next_run' => $this->calculateNextRun($schedule->frequency)
            ]);
        }
    }
    
    private function calculateNextRun($frequency)
    {
        return match($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => now()->addDay()
        };
    }
}