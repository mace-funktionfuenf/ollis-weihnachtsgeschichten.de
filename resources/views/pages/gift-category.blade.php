<x-layouts.app :title="$giftCategory->name">
    <h1>Weihnachtsgeschenke: {{ $giftCategory->name }}</h1>

    @if ($giftCategory->products->isEmpty())
        <p>Für diese Kategorie sind noch keine Produkte hinterlegt.</p>
    @else
        <ul class="card-grid">
            @foreach ($giftCategory->products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </ul>
    @endif
</x-layouts.app>
