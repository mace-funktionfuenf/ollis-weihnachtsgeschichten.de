<x-layouts.app :title="'Für '.$audience->name">
    <h1>Geschenkideen für {{ $audience->name }}</h1>

    @if ($audience->products->isEmpty())
        <p>Für diese Auswahl sind noch keine Produkte hinterlegt.</p>
    @else
        <ul class="card-grid">
            @foreach ($audience->products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </ul>
    @endif
</x-layouts.app>
