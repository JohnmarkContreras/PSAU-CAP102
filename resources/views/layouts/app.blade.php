<!DOCTYPE html>
<html lang="en">
<head>
    @php
        // Safely resolve role name if authenticated (Spatie roles or simple column)
        $roleName = Auth::check()
            ? (Auth::user()->roles->pluck('name')->first() ?? Auth::user()->role ?? 'User')
            : 'Guest';
    @endphp
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'PSAU Tamarind RDE')</title>
    <link rel="icon" href="/PSAU_Logo.png">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/app.js') }}" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" />
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
        /* Toast Container */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 90vw;
            pointer-events: none;
        }

        @media (max-width: 640px) {
            .toast-container {
                top: 70px;
                right: 10px;
                left: 10px;
                max-width: calc(100vw - 20px);
            }
        }

        /* Toast Styles */
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease forwards;
            pointer-events: auto;
            max-width: 400px;
            min-width: 280px;
            background: white;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 640px) {
            .toast {
                max-width: calc(100vw - 20px);
                min-width: auto;
                width: 100%;
            }
        }

        .toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .toast.success {
            border-left: 4px solid #10b981;
        }

        .toast.success::before {
            background: #10b981;
        }

        .toast.error {
            border-left: 4px solid #ef4444;
        }

        .toast.error::before {
            background: #ef4444;
        }

        .toast.info {
            border-left: 4px solid #3b82f6;
        }

        .toast.info::before {
            background: #3b82f6;
        }

        .toast.warning {
            border-left: 4px solid #f59e0b;
        }

        .toast.warning::before {
            background: #f59e0b;
        }

        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .toast.success .toast-icon {
            color: #10b981;
        }

        .toast.error .toast-icon {
            color: #ef4444;
        }

        .toast.info .toast-icon {
            color: #3b82f6;
        }

        .toast.warning .toast-icon {
            color: #f59e0b;
        }

        .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .toast-message {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .toast-close {
            background: none;
            border: none;
            color: #d1d5db;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            flex-shrink: 0;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }

        .toast-close:hover {
            color: #9ca3af;
        }

        /* Progress bar for auto-dismiss */
        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            opacity: 0.3;
        }

        .toast.success .toast-progress {
            background: #10b981;
        }

        .toast.error .toast-progress {
            background: #ef4444;
        }

        .toast.info .toast-progress {
            background: #3b82f6;
        }

        .toast.warning .toast-progress {
            background: #f59e0b;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(400px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            to {
                opacity: 0;
                transform: translateX(400px);
            }
        }

        @keyframes progress {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }

        .toast.removing {
            animation: slideOut 0.3s ease forwards;
        }

        @media (max-width: 640px) {
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slideOut {
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
        }
    </style>
@stack('scripts')
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen flex flex-col md:flex-row w-full overflow-x-hidden">
    <!-- Sidebar (Desktop) -->
    <aside class="hidden md:flex fixed top-0 left-0 bg-[#003300] w-60 h-screen flex-col items-center py-6 text-white z-50">
        @include('components.navbar')
    </aside>

    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed top-0 left-0 right-0 bg-[#003300] text-white flex justify-between items-center px-4 py-3 z-40 shadow">
        <span class="font-bold text-lg">PSAU Tamarind R&DE</span>
        <button id="mobileMenuBtn" class="text-2xl p-2 rounded focus:outline-none focus:ring-2 focus:ring-white" aria-controls="mobileSidebar" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- Mobile Sidebar (Slide-over) -->
    <div id="mobileSidebar" class="fixed inset-0 z-50 hidden md:hidden" aria-hidden="true">
        <!-- Backdrop -->
        <div id="backdrop" class="absolute inset-0 bg-black transition-opacity duration-300 opacity-0"></div>

        <!-- Panel -->
        <aside id="sidebarPanel"
            class="relative bg-[#003300] w-3/4 max-w-xs h-full p-6 text-white transform -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto rounded-r-2xl shadow-lg">
            <!-- Close button -->
            <button id="closeSidebar" class="text-white text-2xl mb-6 p-2 rounded focus:outline-none focus:ring-2 focus:ring-white" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <!-- Navbar links -->
            <nav class="flex flex-col items-center space-y-6 text-2xl md:text-lg font-bold">
                @include('components.navbar')
            </nav>
        </aside>
    </div>

    <!-- Page Wrapper -->
    <div class="flex flex-col flex-1 w-full md:ml-60">
        <!-- Spacer for fixed mobile header -->
        <div class="md:hidden h-[56px]"></div>

        <!-- Top Right User Info -->
<div class="p-4 flex justify-end items-center gap-3 z-50">
    @auth
        <div class="relative">
            <button id="dropdownBtn" class="focus:outline-none focus:ring-2 focus:ring-green-500 rounded-full" aria-haspopup="true" aria-expanded="false">
                <div class="flex items-center gap-2">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('storage/' . Auth::user()->profile_picture) }}" 
                             alt="{{ Auth::user()->name }}" 
                             class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover border-2 border-green-600 hover:border-green-700 transition-all">
                    @else
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-green-600 flex items-center justify-center text-white font-semibold border-2 border-green-700 hover:bg-green-700 transition-all">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="hidden md:inline-block px-3 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                        {{ ucfirst($roleName) }}
                    </span>
                </div>
            </button>
            <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg overflow-hidden z-50 border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
                <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                    <i class="fa-solid fa-user mr-2"></i>Profile
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-gray-200">
                    <i class="fa-solid fa-right-from-bracket mr-2"></i>Logout
                </a>
            </div>
        </div>
    @endauth

    @guest
        <span class="text-sm md:text-base font-medium text-gray-600">
            Guest
        </span>
    @endguest
</div>

        <!-- Page Content -->
        <main class="flex-grow px-3 py-4 md:p-6 z-0">
            @yield('content')
        </main>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <!-- Backdrop -->
        <div id="confirmBackdrop" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity duration-300"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6 animate-in">
            <h3 id="confirmTitle" class="text-lg font-semibold text-gray-900 mb-2">Confirm Action</h3>
            <p id="confirmMessage" class="text-gray-600 text-sm mb-6">Are you sure you want to proceed?</p>
            
            <div class="flex gap-3 justify-end">
                <button id="confirmCancel" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button id="confirmOk" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Toast notification system
        function showToast(type, title, message, duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;

            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };

            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress" style="animation: progress ${duration}ms linear forwards;"></div>
            `;

            container.appendChild(toast);

            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => removeToast(toast));

            setTimeout(() => removeToast(toast), duration);

            return toast;
        }

        function removeToast(toast) {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }

        // Confirmation Modal System
        let confirmCallback = null;

        function showConfirmModal(title, message, onConfirm) {
            const modal = document.getElementById('confirmModal');
            const titleEl = document.getElementById('confirmTitle');
            const messageEl = document.getElementById('confirmMessage');
            const okBtn = document.getElementById('confirmOk');
            const cancelBtn = document.getElementById('confirmCancel');
            const backdrop = document.getElementById('confirmBackdrop');

            titleEl.textContent = title;
            messageEl.textContent = message;
            confirmCallback = onConfirm;

            modal.classList.remove('hidden');

            const handleCancel = () => {
                modal.classList.add('hidden');
                confirmCallback = null;
                okBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                backdrop.removeEventListener('click', handleCancel);
            };

            const handleConfirm = () => {
                modal.classList.add('hidden');
                if (confirmCallback) {
                    confirmCallback();
                }
                confirmCallback = null;
                okBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                backdrop.removeEventListener('click', handleCancel);
            };

            okBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
            backdrop.addEventListener('click', handleCancel);
        }

        // Delete with confirmation helper
        function deleteWithConfirmation(url, itemName = 'item') {
            showConfirmModal(
                'Delete ' + itemName + '?',
                'Are you sure you want to delete this ' + itemName + '? This action cannot be undone.',
                () => {
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Success!', data.message || itemName + ' deleted successfully.');
                            if (data.redirect) {
                                setTimeout(() => window.location.href = data.redirect, 1500);
                            }
                        } else {
                            showToast('error', 'Error!', data.message || 'Failed to delete ' + itemName + '.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('error', 'Error!', 'An error occurred while deleting the ' + itemName + '.');
                    });
                }
            );
        }

        // Dropdown toggle
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('dropdownBtn');
            const menu = document.getElementById('dropdownMenu');
            if (!btn || !menu) return;
            if (btn.contains(e.target)) {
                menu.classList.toggle('hidden');
            } else if (!menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        // Mobile sidebar toggle
        const mobileSidebar = document.getElementById('mobileSidebar');
        const sidebarPanel = document.getElementById('sidebarPanel');
        const backdrop = document.getElementById('backdrop');
        const openBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('closeSidebar');

        function openSidebar() {
            mobileSidebar.classList.remove('hidden');
            requestAnimationFrame(() => {
                sidebarPanel.classList.remove('-translate-x-full');
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                openBtn?.setAttribute('aria-expanded', 'true');
                mobileSidebar.setAttribute('aria-hidden', 'false');
            });
        }

        function closeSidebar() {
            sidebarPanel.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => {
                mobileSidebar.classList.add('hidden');
                openBtn?.setAttribute('aria-expanded', 'false');
                mobileSidebar.setAttribute('aria-hidden', 'true');
            }, 300);
        }

        openBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        backdrop?.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

        // Reset on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                mobileSidebar.classList.add('hidden');
                sidebarPanel.classList.add('-translate-x-full');
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
            }
        });
    </script>
</body>
</html>