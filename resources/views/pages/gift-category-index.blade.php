<x-layouts.app title="Weihnachtsgeschenke" description="Geschenkideen zu Weihnachten, sortiert nach Zielgruppe.">
    <h1>Weihnachtsgeschenke</h1>
    <p class="lede">Geschenkideen zu Weihnachten – sortiert nach Zielgruppe.</p>

    <ul class="card-grid">
        @foreach ($giftCategories as $giftCategory)
            <li class="card">
                <h3><a href="{{ $giftCategory->url() }}">{{ $giftCategory->name }}</a></h3>
                <p>{{ $giftCategory->products_count }} {{ $giftCategory->products_count === 1 ? 'Geschenkidee' : 'Geschenkideen' }}</p>
            </li>
        @endforeach
    </ul>
</x-layouts.app>
