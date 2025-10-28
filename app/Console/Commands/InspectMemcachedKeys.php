<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InspectMemcachedKeys extends Command
{
    protected $signature = 'memcached:inspect {--sample=100 : Number of keys to sample}';
    protected $description = 'Inspect Memcached keys to identify what is consuming memory';

    public function handle()
    {
        $this->info('🔍 Inspecting Memcached Keys...');
        $this->line('');
        
        try {
            $memcached = new \Memcached();
            $memcached->addServer(
                config('cache.stores.memcached.servers.0.host', '127.0.0.1'),
                config('cache.stores.memcached.servers.0.port', 11211)
            );
            
            // Get server stats
            $stats = $memcached->getStats();
            $serverKey = config('cache.stores.memcached.servers.0.host') . ':' . config('cache.stores.memcached.servers.0.port');
            
            if (!isset($stats[$serverKey])) {
                $this->error('Cannot connect to Memcached');
                return 1;
            }
            
            $serverStats = $stats[$serverKey];
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Items', number_format($serverStats['curr_items'])],
                    ['Memory Used', $this->formatBytes($serverStats['bytes'])],
                    ['Evictions', number_format($serverStats['evictions'])],
                ]
            );
            
            $this->line('');
            $this->info('📊 Analyzing Key Patterns...');
            $this->line('');
            
            // Try to get all keys (may not work on all Memcached versions)
            $allKeys = $memcached->getAllKeys();
            
            if ($allKeys === false || empty($allKeys)) {
                $this->warn('⚠️  Cannot retrieve keys (getAllKeys not supported or no keys)');
                $this->line('');
                $this->info('💡 Recommended Actions:');
                $this->line('');
                $this->line('1. Check SESSION_DRIVER in .env:');
                $this->line('   php artisan tinker');
                $this->line('   >>> config(\'session.driver\')');
                $this->line('');
                $this->line('2. If it shows "memcached", change .env to:');
                $this->line('   SESSION_DRIVER=database');
                $this->line('   Then run: php artisan config:clear');
                $this->line('');
                $this->line('3. Check if other applications share this Memcached');
                $this->line('');
                $this->line('4. Flush Memcached:');
                $this->line('   php artisan memcached:clear-sessions --force');
                
                return 0;
            }
            
            $this->info('Found ' . count($allKeys) . ' keys. Analyzing patterns...');
            $this->line('');
            
            // Analyze key patterns
            $patterns = [];
            $sampleSize = min($this->option('sample'), count($allKeys));
            $sampledKeys = array_slice($allKeys, 0, $sampleSize);
            
            foreach ($sampledKeys as $key) {
                // Extract pattern
                if (preg_match('/^([a-z_]+):/', $key, $matches)) {
                    $prefix = $matches[1];
                } elseif (preg_match('/^([A-Za-z]+)/', $key, $matches)) {
                    $prefix = $matches[1];
                } else {
                    $prefix = 'other';
                }
                
                if (!isset($patterns[$prefix])) {
                    $patterns[$prefix] = [
                        'count' => 0,
                        'examples' => [],
                    ];
                }
                
                $patterns[$prefix]['count']++;
                
                if (count($patterns[$prefix]['examples']) < 3) {
                    $patterns[$prefix]['examples'][] = substr($key, 0, 80);
                }
            }
            
            // Sort by count
            uasort($patterns, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            $this->table(
                ['Prefix', 'Count (in sample)', 'Percentage', 'Examples'],
                array_map(function($prefix, $data) use ($sampleSize) {
                    $percent = round(($data['count'] / $sampleSize) * 100, 2);
                    $examples = implode("\n", array_slice($data['examples'], 0, 2));
                    return [
                        $prefix,
                        $data['count'],
                        $percent . '%',
                        $examples
                    ];
                }, array_keys($patterns), array_values($patterns))
            );
            
            $this->line('');
            $this->info('🎯 Key Takeaways:');
            $this->line('');
            
            // Identify issues
            $topPattern = array_key_first($patterns);
            $topCount = $patterns[$topPattern]['count'];
            $topPercent = round(($topCount / $sampleSize) * 100, 2);
            
            if ($topPercent > 50) {
                $this->warn("⚠️  '{$topPattern}' keys dominate ({$topPercent}% of sample)");
                $this->line('');
                
                if (stripos($topPattern, 'sess') !== false || stripos($topPattern, 'PHPSESS') !== false) {
                    $this->error('🚨 ISSUE: PHP Sessions are stored in Memcached!');
                    $this->line('');
                    $this->line('Solution:');
                    $this->line('  1. Change SESSION_DRIVER=database in .env');
                    $this->line('  2. Run: php artisan config:clear');
                    $this->line('  3. Run: php artisan memcached:clear-sessions --force');
                } else {
                    $this->warn('This might be from another application or cache system.');
                }
            }
            
            $this->line('');
            $this->info('📌 Sample size: ' . $sampleSize . ' out of ' . number_format($serverStats['curr_items']) . ' total keys');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

