@extends('admin.layouts.app')

@section('title', 'Laporan')

@section('styles')
<style>
    .report-card {
        transition: all 0.3s ease;
    }
    
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .icon-container {
        transition: all 0.3s ease;
    }
    
    .report-card:hover .icon-container {
        transform: scale(1.1);
    }
    
    .report-button {
        transition: all 0.3s ease;
    }
    
    .report-button:hover {
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
            <h1 class="text-3xl font-bold text-gray-800">Laporan dan Analitik</h1>
            <p class="text-gray-600 mt-1">Lihat statistik dan unduh laporan</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.reports.export') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-700 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:from-green-600 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Semua Data
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
        <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pengguna</p>
                    <p class="text-lg font-semibold text-gray-800">{{ \App\Models\User::count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Pendapatan</p>
                    <p class="text-lg font-semibold text-gray-800">Rp {{ number_format(\App\Models\Transaction::where('status', 'SUCCESS')->sum('saldo_pembayaran'), 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Transaksi</p>
                    <p class="text-lg font-semibold text-gray-800">{{ \App\Models\Transaction::count() }}</p>
                </div>
            </div>
        </div>
        
        <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Transaksi Bulan Ini</p>
                    <p class="text-lg font-semibold text-gray-800">{{ \App\Models\Transaction::whereMonth('created_at', now()->month)->count() }}</p>
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
                <h3 class="text-lg font-semibold text-white">Filter & Pencarian Laporan</h3>
            </div>
        </div>
        <div class="p-6">
            <form action="{{ route('admin.reports.export') }}" method="GET">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-2">
                        <label for="search" class="form-label">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cari Transaksi
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                placeholder="Cari berdasarkan nama atau plat nomor" 
                                class="form-input pl-10 pr-4 py-3 block w-full rounded-xl">
                        </div>
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
                        @if(request('search') || request('date_from') || request('date_to'))
                            <a href="{{ route('admin.reports.index') }}" class="btn-reset inline-flex items-center">
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
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Daily Report Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden report-card border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Laporan Harian</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 text-center mb-6">
                    Lihat laporan transaksi harian untuk bulan tertentu. Analisis tren harian dan bandingkan performa antar hari.
                </p>
                
                <a href="{{ route('admin.reports.daily') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-700 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:from-blue-600 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 w-full report-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Lihat Laporan Harian
                </a>
            </div>
        </div>
        
        <!-- Monthly Report Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden report-card border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Laporan Bulanan</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center text-green-600 icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 text-center mb-6">
                    Lihat laporan transaksi bulanan untuk tahun tertentu. Analisis tren bulanan dan bandingkan performa antar bulan.
                </p>
                
                <a href="{{ route('admin.reports.monthly') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-700 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:from-green-600 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 w-full report-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Lihat Laporan Bulanan
                </a>
            </div>
        </div>
        
        <!-- Vehicle Type Report Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden report-card border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Laporan Jenis Kendaraan</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-20 w-20 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 icon-container">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 text-center mb-6">
                    Lihat distribusi transaksi berdasarkan jenis kendaraan. Analisis jenis kendaraan yang paling sering melintas.
                </p>
                
                <a href="{{ route('admin.reports.vehicle-type') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-700 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:from-orange-600 hover:to-orange-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition-all duration-200 w-full report-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                    Lihat Laporan Jenis Kendaraan
                </a>
            </div>
        </div>
    </div>
@endsection