@if (session('success'))
    <div class="toast-container toast-success text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">…</svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="toast-container toast-error text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">…</svg>
        <span class="font-medium">{{ session('error') }}</span>
    </div>
@endif

@if (session('warning'))
    <div class="toast-container toast-warning text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">…</svg>
        <span class="font-medium">{{ session('warning') }}</span>
    </div>
@endif
