<div {{ $attributes->merge(['class' => 'bg-white p-4 rounded shadow mb-4']) }}>
    <h2 class="text-xl font-bold mb-2">{{ $title }}</h2>
    <div>{{ $slot }}</div> <!-- This is where you place inner content -->
</div>
