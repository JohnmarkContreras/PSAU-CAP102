<form method="POST" action="{{ route('trees.update', $tree->code) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Tree parameters -->
    <input type="text" name="code" value="{{ old('type', $tree->type) }}">
    <input type="text" name="height" value="{{ old('height', $tree->height) }}">
    <input type="text" name="stem_diameter" value="{{ old('stem_diameter', $tree->stem_diamter) }}">
    <input type="text" name="canopy_diameter" value="{{ old('canopy_diameter', $tree->canopy_diameter) }}">

    <!-- Status -->
    <select name="status">
        <option value="alive" {{ $tree->status === 'alive' ? 'selected' : '' }}>Alive</option>
        <option value="dead" {{ $tree->status === 'dead' ? 'selected' : '' }}>Dead</option>
    </select>

    <!-- Death metadata -->
    {{-- <textarea name="reason" placeholder="Reason if dead">{{ old('reason', optional($tree->deathReport)->reason) }}</textarea> --}}
    <input type="file" name="image">

    <button type="submit">Update Tree</button>
</form>
