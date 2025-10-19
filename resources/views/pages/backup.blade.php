@extends('layouts.app')
@section('title', 'Backup')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Backup Storage">
                <div class="text-sm text-black/90 space-y-4">
                    
                    <!-- Last Backup Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-gray-600 font-semibold mb-2">Last Backup</p>
                            @if($lastBackup)
                                <p class="text-green-600 font-semibold flex items-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $lastBackup['date']->format('F j, Y') }}
                                </p>
                                <p class="text-gray-500 text-xs mt-1">
                                    {{ $lastBackup['date']->format('g:i A') }}
                                </p>
                                <p class="text-gray-500 text-xs mt-2">
                                    Size: <span class="font-semibold">{{ $lastBackup['size'] }}</span>
                                </p>
                            @else
                                <p class="text-gray-400">No backups found</p>
                            @endif
                        </div>

                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <p class="text-gray-600 font-semibold mb-2">Backup Location</p>
                            <p class="text-gray-700 font-mono text-xs break-all">
                                backups/
                            <!-- </p> /mnt/external_hdd/ -->
                            <p class="text-gray-500 text-xs mt-2">
                                Scheduled: <span class="font-semibold">Daily 1:00 AM</span>
                            </p>
                        </div>
                    </div>

                    <!-- Alert -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                        <p class="text-blue-800 text-xs">
                            Backups run automatically daily at 1:00 AM. You can also trigger a manual backup below.
                        </p>
                    </div>
                        <div class="flex gap-3 pt-4">
                            <form id="backupForm" onsubmit="startBackup(event)" class="flex gap-3">
                                @csrf
                                <button 
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold" 
                                    id="backupBtn">
                                    <i class="fas fa-download"></i>
                                    <span>Manual Backup Now</span>
                                </button>

                                <!-- Choose a device button -->
                                <a href="{{ route('backup.device.index') }}" 
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2 text-sm font-semibold">
                                    <i class="fas fa-usb"></i>
                                    <span>Choose a Device</span>
                                </a>
                            </form>
                        </div>
                    <!-- Backup Status Message -->
                    <div id="backupStatus" style="display: none;" class="mt-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center gap-3">
                            <div class="animate-spin">
                                <i class="fas fa-spinner text-yellow-600"></i>
                            </div>
                            <p class="text-yellow-800 text-sm font-semibold" id="backupMessage">
                                Backing up... This may take a few minutes.
                            </p>
                        </div>
                    </div>

                    <!-- Success/Error Message -->
                    <div id="backupResult" style="display: none;" class="mt-4">
                        <div id="resultContent"></div>
                    </div>

                </div>
            </x-card>
        </section>
    </main>

    <script>
    function startBackup(e) {
        e.preventDefault();
        
        const btn = document.getElementById('backupBtn');
        const status = document.getElementById('backupStatus');
        const result = document.getElementById('backupResult');
        const message = document.getElementById('backupMessage');
        const form = document.getElementById('backupForm');

        btn.disabled = true;
        status.style.display = 'block';
        result.style.display = 'none';
        message.textContent = 'Backing up... This may take a few minutes.';

        const formData = new FormData(form);

        fetch('{{ route("backup.manual") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            status.style.display = 'none';
            result.style.display = 'block';

            if (data.success) {
                document.getElementById('resultContent').innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                        <p class="text-green-800 text-sm font-semibold">${data.message}</p>
                    </div>
                `;
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                document.getElementById('resultContent').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                        <p class="text-red-800 text-sm font-semibold">${data.message}</p>
                    </div>
                `;
                btn.disabled = false;
            }
        })
        .catch(error => {
            status.style.display = 'none';
            result.style.display = 'block';
            document.getElementById('resultContent').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                    <p class="text-red-800 text-sm font-semibold">Backup failed: ${error}</p>
                </div>
            `;
            btn.disabled = false;
        });
    }
    </script>
@endsection