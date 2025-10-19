@extends('layouts.app')
@section('title', 'Backup to Device')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Backup to External Device">
                <div class="text-sm text-black/90 space-y-4">
                    
                    <!-- Available Devices -->
                    <div>
                        <p class="font-semibold mb-3">Available Devices</p>
                        <div id="deviceList" class="space-y-2">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner animate-spin text-blue-600"></i>
                                <p class="text-gray-500 mt-2">Scanning for devices...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Device Info -->
                    <div id="selectedDeviceInfo" style="display: none;" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-sm font-semibold text-blue-900 mb-2">Selected Device</p>
                        <p id="selectedDeviceName" class="text-gray-700 font-semibold"></p>
                        <p id="selectedDevicePath" class="text-gray-600 text-xs font-mono"></p>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div class="bg-white rounded p-2">
                                <p class="text-xs text-gray-500">Total</p>
                                <p id="selectedDeviceTotal" class="font-semibold text-sm"></p>
                            </div>
                            <div class="bg-white rounded p-2">
                                <p class="text-xs text-gray-500">Used</p>
                                <p id="selectedDeviceUsed" class="font-semibold text-sm"></p>
                            </div>
                            <div class="bg-white rounded p-2">
                                <p class="text-xs text-gray-500">Free</p>
                                <p id="selectedDeviceFree" class="font-semibold text-sm text-green-600"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Name Input -->
                    <div id="backupNameSection" style="display: none;">
                        <label class="block text-sm font-semibold mb-2">Backup Name (Optional)</label>
                        <input 
                            type="text" 
                            id="backupName" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Leave empty for auto-generated name (backup_YYYY-MM-DD_HH-MM-SS)"
                        />
                    </div>

                    <!-- Backup Button -->
                    <div id="backupButtonSection" style="display: none;" class="flex gap-3 pt-4">
                        <button 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-semibold" 
                            id="backupBtn"
                            onclick="startBackupToDevice()">
                            <i class="fas fa-download"></i>
                            <span>Start Backup</span>
                        </button>
                        <button 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition flex items-center gap-2 text-sm font-semibold" 
                            onclick="clearDeviceSelection()">
                            <i class="fas fa-times"></i>
                            <span>Cancel</span>
                        </button>
                    </div>

                    <!-- Backup Status -->
                    <div id="backupStatus" style="display: none;" class="mt-4">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center gap-3">
                            <div class="animate-spin">
                                <i class="fas fa-spinner text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-yellow-800 text-sm font-semibold" id="backupMessage">
                                    Backing up... This may take a few minutes.
                                </p>
                                <div id="backupProgress" class="w-full bg-yellow-200 rounded-full h-2 mt-2">
                                    <div class="bg-yellow-600 h-2 rounded-full animate-pulse" style="width: 30%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Result Message -->
                    <div id="backupResult" style="display: none;" class="mt-4">
                        <div id="resultContent"></div>
                    </div>

                </div>
            </x-card>
        </section>
    </main>

    <script>
    let selectedDevice = null;

    // Load devices on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadDevices();
    });

    function loadDevices() {
        fetch('{{ route("backup.devices") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.devices.length > 0) {
                    displayDevices(data.devices);
                } else {
                    document.getElementById('deviceList').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                            <p class="text-red-800 text-sm">No external devices found. Please connect a USB drive or HDD.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('deviceList').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                        <p class="text-red-800 text-sm">Failed to scan devices: ${error}</p>
                    </div>
                `;
            });
    }

    function displayDevices(devices) {
        const deviceList = document.getElementById('deviceList');
        deviceList.innerHTML = '';

        devices.forEach((device, index) => {
            const spacePercent = device.space ? device.space.percent : 0;
            const spaceClass = spacePercent > 90 ? 'bg-red-100' : spacePercent > 70 ? 'bg-yellow-100' : 'bg-green-100';
            
            const html = `
                <button 
                    onclick="selectDevice(${index}, '${device.name}', '${device.path}', ${JSON.stringify(device.space)})"
                    class="w-full text-left p-3 border border-gray-300 rounded-lg hover:bg-blue-50 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-gray-800">${device.name}</p>
                            <p class="text-xs text-gray-500 font-mono mt-1">${device.path}</p>
                            <p class="text-xs text-gray-500 mt-1">Type: ${device.type}</p>
                        </div>
                        <div class="text-right">
                            ${device.space ? `
                                <p class="text-sm font-semibold">${device.space.free}</p>
                                <p class="text-xs text-gray-500">free</p>
                            ` : ''}
                        </div>
                    </div>
                    ${device.space ? `
                        <div class="mt-2 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${device.space.percent}%;"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">${device.space.used} used of ${device.space.total}</p>
                    ` : ''}
                </button>
            `;
            
            deviceList.innerHTML += html;
        });
    }

    function selectDevice(index, name, path, space) {
        selectedDevice = { name, path };
        document.getElementById('selectedDeviceName').textContent = name;
        document.getElementById('selectedDevicePath').textContent = path;
        document.getElementById('selectedDeviceTotal').textContent = space.total;
        document.getElementById('selectedDeviceUsed').textContent = space.used;
        document.getElementById('selectedDeviceFree').textContent = space.free;
        
        document.getElementById('selectedDeviceInfo').style.display = 'block';
        document.getElementById('backupNameSection').style.display = 'block';
        document.getElementById('backupButtonSection').style.display = 'block';
    }

    function clearDeviceSelection() {
        selectedDevice = null;
        document.getElementById('selectedDeviceInfo').style.display = 'none';
        document.getElementById('backupNameSection').style.display = 'none';
        document.getElementById('backupButtonSection').style.display = 'none';
        document.getElementById('backupStatus').style.display = 'none';
        document.getElementById('backupResult').style.display = 'none';
        document.getElementById('backupName').value = '';
    }

    function startBackupToDevice() {
        if (!selectedDevice) {
            alert('Please select a device');
            return;
        }

        const btn = document.getElementById('backupBtn');
        const status = document.getElementById('backupStatus');
        const result = document.getElementById('backupResult');
        const backupName = document.getElementById('backupName').value || '';

        btn.disabled = true;
        status.style.display = 'block';
        result.style.display = 'none';

        fetch('{{ route("backup.device") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                device_path: selectedDevice.path,
                backup_name: backupName
            })
        })
        .then(response => response.json())
        .then(data => {
            status.style.display = 'none';
            result.style.display = 'block';

            if (data.success) {
                document.getElementById('resultContent').innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-600 mt-0.5"></i>
                        <div>
                            <p class="text-green-800 text-sm font-semibold">${data.message}</p>
                            <p class="text-green-700 text-xs mt-1 font-mono">${data.backup_path}</p>
                        </div>
                    </div>
                `;
                setTimeout(() => {
                    location.reload();
                }, 3000);
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