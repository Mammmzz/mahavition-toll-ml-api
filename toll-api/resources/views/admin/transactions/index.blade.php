@extends('admin.layouts.app')

@section('title', 'Manajemen Transaksi')

@section('styles')
<style>
    .transaction-card {
        transition: all 0.3s ease;
    }
    
    .transaction-card:hover {
        transform: translateY(-2px);
    }
    
    /* Enhanced Filter Styles */
    .filter-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }
    
    .filter-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='7' cy='7' r='2'/%3E%3Ccircle cx='23' cy='7' r='2'/%3E%3Ccircle cx='39' cy='7' r='2'/%3E%3Ccircle cx='55' cy='7' r='2'/%3E%3Ccircle cx='7' cy='23' r='2'/%3E%3Ccircle cx='23' cy='23' r='2'/%3E%3Ccircle cx='39' cy='23' r='2'/%3E%3Ccircle cx='55' cy='23' r='2'/%3E%3Ccircle cx='7' cy='39' r='2'/%3E%3Ccircle cx='23' cy='39' r='2'/%3E%3Ccircle cx='39' cy='39' r='2'/%3E%3Ccircle cx='55' cy='39' r='2'/%3E%3Ccircle cx='7' cy='55' r='2'/%3E%3Ccircle cx='23' cy='55' r='2'/%3E%3Ccircle cx='39' cy='55' r='2'/%3E%3Ccircle cx='55' cy='55' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .filter-icon {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .form-input {
        background: linear-gradient(145deg, #ffffff, #f8fafc);
        border: 2px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    
    .form-input:hover {
        border-color: #cbd5e0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }
    
    .form-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .form-label {
        color: #374151;
        font-weight: 600;
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .form-label svg {
        margin-right: 6px;
        animation: bounce 1s infinite;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    
    .filter-actions {
        background: linear-gradient(145deg, #f1f5f9, #e2e8f0);
        border-radius: 16px;
        padding: 16px;
        margin-top: 32px;
    }
    
    .btn-filter {
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        color: white;
        font-weight: 600;
        padding: 12px 32px;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.4);
    }
    
    .btn-filter:active {
        transform: translateY(0);
    }
    
    .btn-reset {
        background: white;
        color: #6b7280;
        font-weight: 500;
        padding: 12px 24px;
        border-radius: 16px;
        border: 2px solid #d1d5db;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .btn-reset:hover {
        border-color: #9ca3af;
        transform: translateY(-1px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .table-row {
        transition: all 0.2s ease;
    }
    
    .table-row:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    .action-button {
        transition: all 0.2s ease;
    }
    
    .action-button:hover {
        transform: scale(1.15);
    }
    
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-blue {
        color: #1e40af;
        background-color: #dbeafe;
    }
    
    .badge-green {
        color: #065f46;
        background-color: #d1fae5;
    }
    
    .badge-orange {
        color: #9a3412;
        background-color: #ffedd5;
    }
    
    .badge-red {
        color: #991b1b;
        background-color: #fee2e2;
    }
    
    /* Animated background */
    .filter-container {
        background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
</style>
@endsection

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Transaksi</h1>
            <p class="text-gray-600 mt-1">Kelola data transaksi EZToll</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.transactions.export', request()->query()) }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-700 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:from-green-600 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-4 bg-white rounded-xl shadow-sm transaction-card border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Transaksi</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $transactions->total() }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm transaction-card border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pendapatan</p>
                    <p class="text-lg font-semibold text-gray-800">Rp {{ number_format($transactions->where('status', 'SUCCESS')->sum('saldo_pembayaran'), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm transaction-card border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Transaksi Hari Ini</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $transactions->where('created_at', '>=', \Carbon\Carbon::today())->count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm transaction-card border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Rata-rata Transaksi</p>
                    <p class="text-lg font-semibold text-gray-800">Rp {{ number_format($transactions->avg('saldo_pembayaran'), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="bg-gradient-to-br from-white to-blue-50 rounded-2xl shadow-lg mb-8 border border-blue-100 overflow-hidden">
        <div class="filter-header px-6 py-4">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 rounded-full p-2 mr-3 filter-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white">Filter & Pencarian</h3>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.transactions.index') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6">
                    <div class="lg:col-span-2">
                        <label for="plat_nomor" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Cari Plat Nomor
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="plat_nomor" id="plat_nomor" value="{{ request('plat_nomor') }}" 
                                placeholder="Cari plat nomor" 
                                class="form-input pl-10 pr-4 py-3 block w-full rounded-xl">
                        </div>
                    </div>
                    
                    <div>
                        <label for="user_id" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Pengguna
                        </label>
                        <select name="user_id" id="user_id" class="form-input block w-full py-3 px-4 rounded-xl">
                            <option value="">Semua Pengguna</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->plat_nomor }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="jenis_kendaraan" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Jenis Kendaraan
                        </label>
                        <select name="jenis_kendaraan" id="jenis_kendaraan" class="form-input block w-full py-3 px-4 rounded-xl">
                            <option value="">Semua Jenis</option>
                            @foreach($vehicleTypes as $type)
                                <option value="{{ $type }}" {{ request('jenis_kendaraan') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Status
                        </label>
                        <select name="status" id="status" class="form-input block w-full py-3 px-4 rounded-xl">
                            <option value="">Semua Status</option>
                            <option value="SUCCESS" {{ request('status') == 'SUCCESS' ? 'selected' : '' }}>Sukses</option>
                            <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Gagal</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Dari Tanggal
                        </label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                            class="form-input block w-full py-3 px-4 rounded-xl">
                    </div>
                    
                    <div>
                        <label for="date_to" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                            class="form-input block w-full py-3 px-4 rounded-xl">
                    </div>
                </div>
                
                <div class="filter-actions flex items-center justify-between">
                    <div>
                        @if(request('plat_nomor') || request('user_id') || request('jenis_kendaraan') || request('status') || request('date_from') || request('date_to'))
                            <a href="{{ route('admin.transactions.index') }}" class="btn-reset inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Reset Filter
                            </a>
                        @endif
                    </div>
                    <button type="submit" class="btn-filter inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-800">Daftar Transaksi</h3>
            <p class="text-sm text-gray-500">Total: {{ $transactions->total() }} transaksi</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
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
                    @forelse($transactions as $transaction)
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $transaction->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="font-medium text-blue-600">{{ substr($transaction->user->name ?? 'N/A', 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->user->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge badge-blue">{{ $transaction->plat_nomor }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge {{ $transaction->jenis_kendaraan == 'Mobil' ? 'badge-green' : ($transaction->jenis_kendaraan == 'Bus' ? 'badge-orange' : 'badge-blue') }}">
                                    {{ $transaction->jenis_kendaraan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($transaction->saldo_pembayaran, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge {{ strtolower($transaction->status) == 'success' || strtolower($transaction->status) == 'sukses' ? 'badge-green' : 'badge-red' }}">
                                    {{ strtolower($transaction->status) == 'success' || strtolower($transaction->status) == 'sukses' ? 'Sukses' : 'Gagal' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $transaction->created_at->format('d M Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $transaction->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-3">
                                    <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="text-blue-600 hover:text-blue-900 action-button" title="Lihat Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.transactions.edit', $transaction->id) }}" class="text-indigo-600 hover:text-indigo-900 action-button" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.transactions.destroy', $transaction->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete(this)" class="text-red-600 hover:text-red-900 action-button" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium">Tidak ada data transaksi</p>
                                    <p class="text-gray-400 text-sm mt-1">Tidak ada transaksi yang ditemukan dengan filter yang diterapkan</p>
                                    
                                    @if(request('plat_nomor') || request('user_id') || request('jenis_kendaraan') || request('status') || request('date_from') || request('date_to'))
                                        <a href="{{ route('admin.transactions.index') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Reset Filter
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $transactions->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
    function confirmDelete(button) {
        if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
            button.closest('form').submit();
        }
    }
</script>
@endsection