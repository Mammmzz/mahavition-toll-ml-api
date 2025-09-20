<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        // Get database info
        $databaseInfo = [
            'connection' => config('database.default'),
            'database' => config('database.connections.' . config('database.default') . '.database'),
            'version' => DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown',
        ];
        
        // Get system info
        $systemInfo = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => php_uname(),
        ];
        
        // Get application info
        $appInfo = [
            'name' => config('app.name'),
            'url' => config('app.url'),
            'env' => config('app.env'),
            'debug' => config('app.debug') ? 'Enabled' : 'Disabled',
        ];
        
        // Get storage info
        $storageInfo = [
            'logs_size' => $this->getDirectorySize(storage_path('logs')),
            'cache_size' => $this->getDirectorySize(storage_path('framework/cache')),
            'sessions_size' => $this->getDirectorySize(storage_path('framework/sessions')),
            'views_size' => $this->getDirectorySize(storage_path('framework/views')),
        ];
        
        // Get admin users
        $adminUsers = User::where('is_admin', true)->get();
        
        return view('admin.settings.index', compact(
            'databaseInfo',
            'systemInfo',
            'appInfo',
            'storageInfo',
            'adminUsers'
        ));
    }
    
    /**
     * Update application settings
     */
    public function update(Request $request)
    {
        $action = $request->action;
        
        if ($action === 'clear_cache') {
            try {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
                
                return redirect()->back()->with('success', 'Cache berhasil dibersihkan');
            } catch (\Exception $e) {
                Log::error('Error clearing cache: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
            }
        } elseif ($action === 'clear_logs') {
            try {
                $logFiles = File::glob(storage_path('logs/*.log'));
                
                foreach ($logFiles as $logFile) {
                    if (File::isFile($logFile)) {
                        File::put($logFile, '');
                    }
                }
                
                return redirect()->back()->with('success', 'Log berhasil dibersihkan');
            } catch (\Exception $e) {
                Log::error('Error clearing logs: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal membersihkan log: ' . $e->getMessage());
            }
        } elseif ($action === 'backup_database') {
            try {
                // This is a simplified example. In production, you would want to use a proper backup package
                $connection = config('database.default');
                $database = config('database.connections.' . $connection . '.database');
                
                if ($connection === 'sqlite') {
                    $dbPath = config('database.connections.sqlite.database');
                    $backupPath = storage_path('app/backups');
                    
                    if (!File::exists($backupPath)) {
                        File::makeDirectory($backupPath, 0755, true);
                    }
                    
                    $backupFile = $backupPath . '/backup_' . date('Y-m-d_H-i-s') . '.sqlite';
                    File::copy($dbPath, $backupFile);
                    
                    return redirect()->back()->with('success', 'Database berhasil dibackup ke ' . $backupFile);
                } else {
                    return redirect()->back()->with('error', 'Backup database hanya didukung untuk SQLite');
                }
            } catch (\Exception $e) {
                Log::error('Error backing up database: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal melakukan backup database: ' . $e->getMessage());
            }
        } elseif ($action === 'toggle_maintenance') {
            try {
                if (app()->isDownForMaintenance()) {
                    Artisan::call('up');
                    return redirect()->back()->with('success', 'Aplikasi berhasil diaktifkan');
                } else {
                    Artisan::call('down', [
                        '--message' => 'Aplikasi sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.',
                    ]);
                    return redirect()->back()->with('success', 'Aplikasi berhasil dimasukkan ke mode pemeliharaan');
                }
            } catch (\Exception $e) {
                Log::error('Error toggling maintenance mode: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal mengubah mode pemeliharaan: ' . $e->getMessage());
            }
        }
        
        return redirect()->back()->with('error', 'Aksi tidak valid');
    }
    
    /**
     * Get the size of a directory in a human-readable format
     */
    private function getDirectorySize($directory)
    {
        if (!File::exists($directory)) {
            return '0 B';
        }
        
        $size = 0;
        
        foreach (File::allFiles($directory) as $file) {
            $size += $file->getSize();
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        
        return round($size / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}
