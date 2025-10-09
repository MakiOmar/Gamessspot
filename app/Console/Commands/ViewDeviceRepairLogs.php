<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewDeviceRepairLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:device-repair {--lines=50 : Number of lines to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View device repair notification logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            $this->error('Log file not found: ' . $logFile);
            return 1;
        }
        
        $lines = $this->option('lines');
        
        $this->info("Displaying last {$lines} lines of device repair logs...");
        $this->line('');
        
        // Read the log file
        $command = "tail -n {$lines} " . escapeshellarg($logFile) . " | grep -E 'ADMIN FORM|PUBLIC FORM|STATUS UPDATE|DeviceServiceNotification|Device Service'";
        
        $output = [];
        exec($command, $output, $returnVar);
        
        if (empty($output)) {
            $this->warn('No device repair logs found in the last ' . $lines . ' lines.');
            $this->info('Try submitting a device repair form to generate logs.');
        } else {
            foreach ($output as $line) {
                // Colorize different log levels
                if (strpos($line, 'ERROR') !== false) {
                    $this->error($line);
                } elseif (strpos($line, 'WARNING') !== false) {
                    $this->warn($line);
                } else {
                    $this->line($line);
                }
            }
        }
        
        $this->line('');
        $this->info('Log file location: ' . $logFile);
        
        return 0;
    }
}

