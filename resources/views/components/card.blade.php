<div {{ $attributes->merge(['class' => 'bg-white p-4 rounded shadow mb-4']) }}>
    <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">{{ $title }}</h2>
    <div>{{ $slot }}</div> <!-- This is where you place inner content -->
</div>
