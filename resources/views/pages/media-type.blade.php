<x-layouts.app :title="$mediaType->name">
    <h1>Weihnachtsgeschichten: {{ $mediaType->name }}</h1>

    @if ($mediaType->products->isEmpty())
        <p>Für diese Kategorie sind noch keine Produkte hinterlegt.</p>
    @else
        <ul class="card-grid">
            @foreach ($mediaType->products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </ul>
    @endif
</x-layouts.app>
