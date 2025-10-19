@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Accounts">
            <div class="text-sm text-black/90 space-y-0.5">
                <div class="mb-4">
                    <a href="{{ route('create.account') }}" class="bg-green-800 hover:bg-green-700 text-white py-2 px-4 rounded">
                        + Create New Account
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table id="accountsTable" class="min-w-full text-sm text-left text-gray-600 stripe hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr data-id="{{ $user->id }}">
                                    <td class="px-4 py-2">{{ $user->id }}</td>
                                    <td class="px-4 py-2">
                                        @if($user->profile_picture)
                                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                alt="Profile Picture"
                                                class="w-12 h-12 rounded-full object-cover border border-gray-300">
                                        @else
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff"
                                                alt="Default Avatar"
                                                class="w-12 h-12 rounded-full object-cover border border-gray-300">
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">{{ $user->name }}</td>
                                    <td class="px-4 py-2">{{ $user->email }}</td>
                                    <td class="px-4 py-2 capitalize">{{ $user->getRoleNames()->implode(', ') }}</td>
                                    <td class="px-4 py-2">
                                        <button 
                                            class="delete-account text-red-600 hover:underline" 
                                            data-id="{{ $user->id }}" 
                                            data-name="{{ $user->name }}">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>
    </section>

    {{-- Delete Confirmation Modal --}}
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg p-6 w-96 text-center">
            <h2 class="text-xl font-semibold text-gray-800 mb-2">Confirm Deletion</h2>
            <p class="text-gray-600 mb-4">Are you sure you want to delete <span id="deleteUserName" class="font-semibold text-red-700"></span>?</p>
            <div class="flex justify-center gap-3">
                <button id="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded">Yes, Delete</button>
                <button id="cancelDelete" class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded">Cancel</button>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteModal');
    const deleteUserName = document.getElementById('deleteUserName');
    let deleteId = null;

    // Initialize DataTable
    const table = $('#accountsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search accounts...",
            lengthMenu: "Show _MENU_ entries",
            paginate: { previous: "← Prev", next: "Next →" },
            info: "Showing _START_ to _END_ of _TOTAL_ accounts",
            infoEmpty: "No accounts available",
        },
        columnDefs: [{ orderable: false, targets: [1, 5] }]
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    // Open modal
    $('#accountsTable').on('click', '.delete-account', function (e) {
        e.preventDefault();
        deleteId = $(this).data('id');
        const name = $(this).data('name');
        deleteUserName.textContent = name;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    // Cancel delete
    document.getElementById('cancelDelete').addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        deleteId = null;
    });

    // Confirm delete
    document.getElementById('confirmDelete').addEventListener('click', function () {
        if (!deleteId) return;
        $.ajax({
            url: '{{ url('') }}/accounts/' + deleteId,
            method: 'POST',
            data: { _method: 'DELETE' },
            success: function () {
                const row = $('#accountsTable').find('tr[data-id="' + deleteId + '"]');
                table.row(row).remove().draw(false);
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Failed to delete account.');
            }
        });
    });
});
</script>
@endpush
