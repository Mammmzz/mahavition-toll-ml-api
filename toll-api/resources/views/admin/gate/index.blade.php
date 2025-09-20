@extends('admin.layouts.app')

@section('title', 'Pengaturan Gerbang')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pengaturan Gerbang</h1>
            <p class="text-gray-600">Kelola status gerbang tol dan lihat aktivitas</p>
        </div>
    </div>
    
    <!-- Gate Status Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-700">Status Gerbang</h2>
        </div>
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center mb-4 md:mb-0">
                    <div class="p-4 rounded-full {{ $gate->gate_status == 'on' ? 'bg-green-100' : 'bg-red-100' }} mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 {{ $gate->gate_status == 'on' ? 'text-green-600' : 'text-red-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status Saat Ini</p>
                        <p class="text-xl font-bold {{ $gate->gate_status == 'on' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $gate->gate_status == 'on' ? 'Terbuka' : 'Tertutup' }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">
                            Terakhir diperbarui: {{ $gate->updated_at->format('d M Y H:i:s') }}
                        </p>
                    </div>
                </div>
                
                <div>
                    <form action="{{ route('admin.gate.toggle') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 {{ $gate->gate_status == 'on' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-green-600 hover:bg-green-700 focus:ring-green-500' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150">
                            @if($gate->gate_status == 'on')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Tutup Gerbang
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                Buka Gerbang
                            @endif
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mt-6 border-t border-gray-200 pt-6">
                <div class="text-sm text-gray-500">
                    <p class="mb-2"><strong>Catatan:</strong></p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Gerbang akan terbuka otomatis setelah transaksi berhasil.</li>
                        <li>Gerbang akan tertutup otomatis setelah 5 detik.</li>
                        <li>Gunakan tombol di atas untuk membuka atau menutup gerbang secara manual jika diperlukan.</li>
                        <li>Status gerbang akan diperbarui secara real-time.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Daily Gate Usage Chart -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Penggunaan Gerbang Harian (7 Hari Terakhir)</h2>
            </div>
            <div class="p-6">
                <div style="height: 300px;">
                    <canvas id="dailyGateUsageChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Hourly Gate Usage Chart -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">Distribusi Jam Penggunaan (Hari Ini)</h2>
            </div>
            <div class="p-6">
                <div style="height: 300px;">
                    <canvas id="hourlyGateUsageChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Gate Activities -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-700">Aktivitas Gerbang Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Transaksi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Kendaraan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentActivities as $activity)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->plat_nomor }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->jenis_kendaraan }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($activity->saldo_pembayaran, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 text-xs font-semibold leading-5 {{ $activity->status == 'success' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100' }} rounded-full">
                                    {{ $activity->status == 'success' ? 'Sukses' : 'Gagal' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $activity->created_at->format('d M Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Tidak ada aktivitas gerbang terbaru
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            <a href="{{ route('admin.transactions.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                Lihat Semua Aktivitas
            </a>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Daily Gate Usage Chart
        const dailyCtx = document.getElementById('dailyGateUsageChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: @json($dailyLabels),
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: @json($dailyData),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Hourly Gate Usage Chart
        const hourlyCtx = document.getElementById('hourlyGateUsageChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: @json($hourlyLabels),
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: @json($hourlyData),
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(16, 185, 129, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
