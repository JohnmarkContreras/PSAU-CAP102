@extends('layouts.app')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@section('content')
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Pending tags">
            <div class="text-sm text-black/90 space-y-0.5">
                @if($pending->isEmpty())
                    <p>No pending geotags.</p>
                @else
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
                            </tr>
                        </thead>
                            <tbody>
                                @forelse($pending as $geo)
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
                                            <td class="px-4 py-2 text-center">
                                                <form action="{{ route('pending-geotags.approve', $geo->id) }}" method="POST" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm p-2 w-20 rounded-xs mb-2 text-white cursor-pointer  bg-green-800 ">Approve</button>
                                                    <i class="fa-solid fa-check text-2xl"></i>
                                                </form>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <form action="{{ route('pending-geotags.reject', $geo->id) }}" method="POST" style="display:inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-sm p-2 w-20 rounded-xs mb-2 text-white cursor-pointer bg-red-700">Reject</button>
                                                    <i class="fa-solid fa-xmark text-2xl"></i>
                                                </form>
                                            </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                    </table>
                @endif
            </div>
        </x-card>
    </section>
@endsection
