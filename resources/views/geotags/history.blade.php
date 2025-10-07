@extends('layouts.app')
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}

@section('content')
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Approved & Rejected Geotags">
            <div class="text-sm text-black/90 space-y-0.5">
                @if($geotags->isEmpty())
                    <p>No pending geotags.</p>
                    
                @else
                <div class="mb-4 text-right">
                    <a href="{{ route('geotags.pending') }}" class="text-blue-600 hover:underline font-medium">
                        Back to Pending Geotags
                    </a>
                </div>
                <div class="overflow-x-auto min-h-[600px]">
                    <table class="w-full text-sm text-left border border-gray-200 rounded-lg mt-2">
                        <thead class="bg-gray-100">
                            <tr class="text-center">
                                <th class="p-2">User</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Age</th>
                                <th>Height</th>
                                <th>Stem Diameter</th>
                                <th>Canopy Diameter</th>
                                <th colspan="2">Actions</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                            <tbody>
                                @forelse($geotags as $geo)
                                    <tr class="hover:bg-gray-50 text-center">
                                        <td class="px-4 py-2">{{ $geo->user->name }}</td>
                                        <td class="px-4 py-2">{{ $geo->code }}</td>
                                        <td class="px-4 py-2">{{ $geo->type }}</td>
                                        <td class="px-4 py-2">{{ $geo->latitude }}</td>
                                        <td class="px-4 py-2">{{ $geo->longitude }}</td>
                                        <td class="px-4 py-2">{{ $geo->age }}</td>
                                        <td class="px-4 py-2">{{ $geo->height }}</td>
                                        <td class="px-4 py-2">{{ $geo->stem_diameter }}</td>
                                        <td class="px-4 py-2">{{ $geo->canopy_diameter }}</td>

                                        {{-- Status Badge --}}
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded text-xs font-bold
                                                @if($geo->status === 'pending') bg-yellow-200 text-yellow-800
                                                @elseif($geo->status === 'approved') bg-green-200 text-green-800
                                                @else bg-red-200 text-red-800 @endif">
                                                {{ ucfirst($geo->status) }}
                                            </span>
                                        </td>

                                        {{-- Approve Button --}}
                                        <td class="px-4 py-2 text-center">
                                            @if($geo->status === 'pending')
                                                <form action="{{ route('pending-geotags.approve', $geo->id) }}" method="POST" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm p-2 w-20 rounded-xs mb-2 text-white cursor-pointer bg-green-800">Approve</button>
                                                    <i class="fa-solid fa-check text-2xl"></i>
                                                </form>
                                            @else
                                                <span class="text-gray-400 italic">—</span>
                                            @endif
                                        </td>

                                        {{-- Reject Button or Reason --}}
                                        <td class="px-4 py-2 text-center">
                                            @if($geo->status === 'pending')
                                                <form action="{{ route('pending-geotags.reject', $geo->id) }}" method="POST" style="display:inline">
                                                    @csrf
                                                    @include('partials.reject', ['geotag' => $geo])
                                                    <i class="fa-solid fa-xmark text-2xl"></i>
                                                </form>
                                            @elseif($geo->status === 'rejected')
                                                <span class="text-sm text-red-600">{{ $geo->rejection_reason ?? '—' }}</span>
                                            @else
                                                <span class="text-gray-400 italic">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center text-gray-500 py-4">No geotags found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                    </table>
                </div>
                <div class="pagination mt-4">
                    {{ $geotags->links('pagination::tailwind') }}
                </div>
                @endif
            </div>
        </x-card>
    </section>
@endsection
