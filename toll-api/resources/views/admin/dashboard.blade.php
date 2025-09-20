@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .chart-container {
        position: relative;
        height: 350px;
    }
    
    .gradient-blue {
        background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    }
    
    .gradient-green {
        background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    }
    
    .gradient-orange {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }
    
    .gradient-red {
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    }
    
    .card-shadow {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .card-shadow:hover {
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
    }
    
    .table-hover tr:hover {
        background-color: #f9fafb;
    }
    
    .status-badge {
        transition: all 0.2s ease;
    }
    
    .status-badge:hover {
        transform: scale(1.05);
    }
</style>
@endsection

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-600 mt-1">Selamat datang di panel admin EZToll</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl overflow-hidden card-shadow stat-card">
            <div class="gradient-blue px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-white bg-opacity-30 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-medium text-white text-opacity-80">Total Pengguna</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($totalUsers ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-blue-50">
                <div class="text-sm text-blue-600">
                    <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between hover:underline">
                        <span>Lihat Detail</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="rounded-xl overflow-hidden card-shadow stat-card">
            <div class="gradient-green px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-white bg-opacity-30 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-medium text-white text-opacity-80">Total Pendapatan</p>
                        <p class="text-2xl font-bold text-white">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-green-50">
                <div class="text-sm text-green-600">
                    <a href="{{ route('admin.transactions.index') }}?status=SUCCESS" class="flex items-center justify-between hover:underline">
                        <span>Lihat Detail</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="rounded-xl overflow-hidden card-shadow stat-card">
            <div class="gradient-orange px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-white bg-opacity-30 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-medium text-white text-opacity-80">Total Transaksi</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($totalTransactions ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-orange-50">
                <div class="text-sm text-orange-600">
                    <a href="{{ route('admin.transactions.index') }}" class="flex items-center justify-between hover:underline">
                        <span>Lihat Detail</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="rounded-xl overflow-hidden card-shadow stat-card">
            <div class="gradient-red px-4 py-5 sm:p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-white bg-opacity-30 mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="mb-1 text-sm font-medium text-white text-opacity-80">Transaksi Hari Ini</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($todayTransactions ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-red-50">
                <div class="text-sm text-red-600">
                    <a href="{{ route('admin.transactions.index') }}?date_from={{ date('Y-m-d') }}&date_to={{ date('Y-m-d') }}" class="flex items-center justify-between hover:underline">
                        <span>Lihat Detail</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mb-8">
        <!-- Chart: Transaksi per Hari -->
        <div class="p-6 bg-white rounded-xl shadow card-shadow">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">Transaksi per Hari</h2>
                <div class="text-sm text-gray-500">7 Hari Terakhir</div>
            </div>
            <div class="chart-container">
                <canvas id="transactionsChart"></canvas>
            </div>
        </div>
        
        <!-- Chart: Distribusi Jenis Kendaraan -->
        <div class="p-6 bg-white rounded-xl shadow card-shadow">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">Distribusi Kendaraan</h2>
                <div class="text-sm text-gray-500">Berdasarkan Jenis</div>
            </div>
            <div class="chart-container">
                <canvas id="vehicleTypeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow card-shadow mb-8">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">Transaksi Terbaru</h2>
            <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                Lihat Semua
            </a>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 table-hover">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Kendaraan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentTransactions ?? [] as $transaction)
                            <tr class="hover:bg-gray-50 transition-colors duration-150 ease-in-out">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#{{ $transaction->id }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="text-blue-600 font-medium">{{ substr($transaction->user->name ?? 'N/A', 0, 1) }}</span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $transaction->user->name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">{{ $transaction->plat_nomor }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $transaction->jenis_kendaraan }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rp {{ number_format($transaction->saldo_pembayaran, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="status-badge px-3 py-1 text-xs font-semibold rounded-full 
                                        {{ strtolower($transaction->status) == 'success' || strtolower($transaction->status) == 'sukses' ? 
                                        'text-green-800 bg-green-100' : 'text-red-800 bg-red-100' }}">
                                        {{ strtolower($transaction->status) == 'success' || strtolower($transaction->status) == 'sukses' ? 'Sukses' : 'Gagal' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $transaction->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="text-gray-500 text-lg font-medium">Tidak ada transaksi terbaru</p>
                                        <p class="text-gray-400 text-sm mt-1">Transaksi akan muncul di sini setelah pengguna melakukan pembayaran</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Konfigurasi chart
        Chart.defaults.font.family = "'Poppins', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#6b7280';
        
        // Transaksi per Hari Chart
        const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
        new Chart(transactionsCtx, {
            type: 'line',
            data: {
                labels: @json($dailyTransactionsLabels ?? []),
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: @json($dailyTransactionsData ?? []),
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(156, 163, 175, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
        
        // Distribusi Jenis Kendaraan Chart
        const vehicleTypeCtx = document.getElementById('vehicleTypeChart').getContext('2d');
        new Chart(vehicleTypeCtx, {
            type: 'doughnut',
            data: {
                labels: @json($vehicleTypeLabels ?? []),
                datasets: [{
                    data: @json($vehicleTypeData ?? []),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)', // Blue
                        'rgba(245, 158, 11, 0.8)', // Orange
                        'rgba(16, 185, 129, 0.8)', // Green
                        'rgba(239, 68, 68, 0.8)'   // Red
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 13
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
    });
</script>
@endsection