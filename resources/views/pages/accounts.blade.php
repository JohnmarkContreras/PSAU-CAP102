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

                                    {{-- Avatar column --}}
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
                                        <button class="delete-account text-red-600 hover:underline" data-id="{{ $user->id }}">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </x-card>
    </section>
</main>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#accountsTable').DataTable({
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
        columnDefs: [
            { orderable: false, targets: [1, 5] } // disable sorting for photo & actions
        ]
    });

    // CSRF for AJAX
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    // Delete handler (AJAX)
    $('#accountsTable').on('click', '.delete-account', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('Are you sure?')) return;

        $.ajax({
            url: '{{ url('') }}/superadmin/delete/account/' + id,
            method: 'POST',
            data: { _method: 'DELETE' },
            success: function () {
                const row = $('#accountsTable').find('tr[data-id="' + id + '"]');
                $('#accountsTable').DataTable().row(row).remove().draw(false);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Failed to delete account.');
            }
        });
    });
});
</script>
@endpush