@extends('admin.layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Sistem</h1>
        <p class="text-gray-600">Kelola pengaturan aplikasi EZToll</p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Application Info -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Informasi Aplikasi</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nama Aplikasi</p>
                        <p class="mt-1 text-base text-gray-900">{{ $appInfo['name'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">URL</p>
                        <p class="mt-1 text-base text-gray-900">{{ $appInfo['url'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Environment</p>
                        <p class="mt-1 text-base text-gray-900">{{ $appInfo['env'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Debug Mode</p>
                        <p class="mt-1 text-base text-gray-900">{{ $appInfo['debug'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Maintenance Mode</p>
                        <p class="mt-1 text-base text-gray-900">{{ app()->isDownForMaintenance() ? 'Aktif' : 'Tidak Aktif' }}</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="toggle_maintenance">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                            {{ app()->isDownForMaintenance() ? 'Nonaktifkan Mode Pemeliharaan' : 'Aktifkan Mode Pemeliharaan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Informasi Sistem</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Versi PHP</p>
                        <p class="mt-1 text-base text-gray-900">{{ $systemInfo['php_version'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Versi Laravel</p>
                        <p class="mt-1 text-base text-gray-900">{{ $systemInfo['laravel_version'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Server</p>
                        <p class="mt-1 text-base text-gray-900">{{ $systemInfo['server'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Sistem Operasi</p>
                        <p class="mt-1 text-base text-gray-900">{{ $systemInfo['os'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Database Info -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Informasi Database</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Koneksi</p>
                        <p class="mt-1 text-base text-gray-900">{{ $databaseInfo['connection'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Database</p>
                        <p class="mt-1 text-base text-gray-900">{{ $databaseInfo['database'] }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm font-medium text-gray-500">Versi</p>
                        <p class="mt-1 text-base text-gray-900">{{ $databaseInfo['version'] }}</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="backup_database">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                            Backup Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Storage Info -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Informasi Penyimpanan</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Log</p>
                        <p class="text-base text-gray-900">{{ $storageInfo['logs_size'] }}</p>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Cache</p>
                        <p class="text-base text-gray-900">{{ $storageInfo['cache_size'] }}</p>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Sessions</p>
                        <p class="text-base text-gray-900">{{ $storageInfo['sessions_size'] }}</p>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Views</p>
                        <p class="text-base text-gray-900">{{ $storageInfo['views_size'] }}</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 space-y-4">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                            Bersihkan Cache
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center">
                            Bersihkan Log
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Admin Users -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Admin Users</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Login</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($adminUsers as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Tidak ada admin user
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
