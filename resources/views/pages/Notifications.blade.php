<!-- resources/views/pages/Notifications.blade.php -->
@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Notifications">
                <div class="text-sm text-black/90 space-y-0.5">
                    <!-- Filter Buttons -->
                    <div class="mb-4">
                        <div class="flex space-x-6 mb-4 border-b border-gray-300">
                            <button type="button" data-filter="all"
                                class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 border-green-600 text-green-800 cursor-pointer transition">
                                All
                            </button>
                            <button type="button" data-filter="new"
                                class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 border-transparent text-gray-600 hover:text-blue-600 cursor-pointer transition">
                                New
                            </button>
                            <button type="button" data-filter="unread"
                                class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 border-transparent text-gray-600 hover:text-blue-600 cursor-pointer transition">
                                Unread
                            </button>
                        </div>

                        <!-- Mark All Button -->
                        <button type="button" onclick="markAllRead()"
                            class="text-sm bg-[#e9eee9] rounded-sm w-32 h-10 text-green-800 hover:underline font-medium cursor-pointer hover:bg-green-50 transition">
                            Mark all as read
                        </button>
                    </div>

                    <!-- Notifications Container -->
                    <div id="notificationBody" class="space-y-3 mt-6">
                        <div class="text-center py-8">
                            <span class="text-gray-500 text-sm animate-pulse">Loading notifications...</span>
                        </div>
                    </div>

                    <!-- Pagination Info -->
                    <div class="mt-6 flex items-center justify-between">
                        <span id="paginationInfo" class="text-xs text-gray-600"></span>
                        <div id="paginationControls" class="flex gap-2"></div>
                    </div>
                </div>
            </x-card>
        </section>
    </main>
@endsection

@push('scripts')
<script>
let currentFilter = 'all';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', function () {
    loadNotifications();
    setupFilterButtons();
});

function loadNotifications() {
    const container = document.getElementById('notificationBody');
    container.innerHTML = '<div class="text-center py-8"><span class="text-gray-500 text-sm animate-pulse">Loading...</span></div>';

    fetch(`{{ route('pages.Notifications') }}?filter=${currentFilter}&page=${currentPage}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderNotifications(data.notifications);
            renderPagination(data.pagination);
            attachDeleteListeners();
        } else {
            container.innerHTML = '<div class="text-center py-8 text-red-600">Error loading notifications</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = '<div class="text-center py-8 text-red-600">Error loading notifications</div>';
    });
}

function renderNotifications(notifications) {
    const container = document.getElementById('notificationBody');

    if (notifications.length === 0) {
        container.innerHTML = '<div class="bg-gray-100 rounded-lg p-8 text-center"><i class="fas fa-inbox text-gray-400 text-4xl mb-3"></i><p class="text-gray-600">No notifications</p></div>';
        return;
    }

    container.innerHTML = notifications.map(notif => {
        const isUnread = !notif.read_at;
        const bgClass = isUnread ? 'bg-green-50' : 'bg-white';
        const borderClass = isUnread ? 'border-green-600' : 'border-gray-300';
        const message = notif.data?.message || 'No message';
        const timestamp = formatTime(notif.created_at);
        const actionUrl = notif.data?.action_url || null;
        const actionText = notif.data?.action_text || 'View';

        let actionLink = '';
        if (actionUrl) {
            actionLink = `<a href="${actionUrl}" class="text-xs text-blue-600 hover:text-blue-800 font-medium mt-2 inline-block"><i class="fas fa-arrow-right"></i> ${actionText}</a>`;
        }

        return `
            <div class="rounded-lg p-4 border-l-4 ${bgClass} ${borderClass} hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-700 mb-2">${message}</p>
                        ${actionLink}
                        <span class="text-xs text-gray-500 block mt-1">${timestamp}</span>
                    </div>
                    <div class="flex gap-2 ml-4">
                        ${isUnread ? `<button onclick="markAsRead('${notif.id}')" class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition" title="Mark as read"><i class="fas fa-check"></i></button>` : ''}
                        <button data-delete-id="${notif.id}" class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    info.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total}`;

    let html = '';
    if (pagination.current_page > 1) {
        html += `<button onclick="goToPage(${pagination.current_page - 1})" class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 rounded"><i class="fas fa-chevron-left"></i></button>`;
    }

    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            html += `<button class="px-3 py-1 text-sm bg-green-600 text-white rounded">${i}</button>`;
        } else if (i === 1 || i === pagination.last_page || (i > pagination.current_page - 2 && i < pagination.current_page + 2)) {
            html += `<button onclick="goToPage(${i})" class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 rounded">${i}</button>`;
        } else if (i === pagination.current_page - 2 || i === pagination.current_page + 2) {
            html += `<span class="px-2 text-gray-600">...</span>`;
        }
    }

    if (pagination.current_page < pagination.last_page) {
        html += `<button onclick="goToPage(${pagination.current_page + 1})" class="px-3 py-1 text-sm bg-gray-300 hover:bg-gray-400 rounded"><i class="fas fa-chevron-right"></i></button>`;
    }

    controls.innerHTML = html;
}

function setupFilterButtons() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentFilter = this.dataset.filter;
            currentPage = 1;

            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('border-green-600', 'text-green-800');
                b.classList.add('border-transparent', 'text-gray-600');
            });

            this.classList.remove('border-transparent', 'text-gray-600');
            this.classList.add('border-green-600', 'text-green-800');

            loadNotifications();
        });
    });
}

function attachDeleteListeners() {
    document.querySelectorAll('[data-delete-id]').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.deleteId;
            showConfirmModal(
                'Delete Notification?',
                'Are you sure you want to delete this notification?',
                () => deleteNotification(id)
            );
        });
    });
}

function deleteNotification(id) {
    fetch(`{{ url('/notifications') }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Success!', data.message);
            loadNotifications();
        } else {
            showToast('error', 'Error!', data.message);
        }
    })
    .catch(() => showToast('error', 'Error!', 'Failed to delete'));
}

function markAsRead(id) {
    fetch(`{{ url('/notifications') }}/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Success!', data.message);
            loadNotifications();
        } else {
            showToast('error', 'Error!', data.message);
        }
    })
    .catch(() => showToast('error', 'Error!', 'Failed to mark as read'));
}

function markAllRead() {
    fetch(`{{ route('notifications.markAllRead') }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showToast('success', 'Success!', data.message);
            currentPage = 1;
            setTimeout(() => loadNotifications(), 500);
        } else {
            showToast('error', 'Error!', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Error!', 'Failed to mark all as read');
    });
}

function goToPage(page) {
    currentPage = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    loadNotifications();
}

function formatTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}
</script>
@endpush