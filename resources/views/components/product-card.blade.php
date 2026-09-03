@props(['product'])
<li class="card">
    @if ($product->image_path)
        <div class="thumb">
            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->title }}" loading="lazy" width="160">
        </div>
    @endif
    <h3><a href="{{ $product->url() }}">{{ $product->title }}</a></h3>
    @if ($product->price)
        <p class="price">
            @if ($product->price_old && $product->price_old > $product->price)
                <del>{{ number_format((float) $product->price_old, 2, ',', '.') }} €</del>
            @endif
            {{ number_format((float) $product->price, 2, ',', '.') }} €
        </p>
    @endif
    @if (! $product->available)
        <p><span class="unavailable">Derzeit nicht verfügbar</span></p>
    @endif
    <div class="actions">
        <a class="btn secondary" href="{{ $product->url() }}">Details</a>
        @if ($product->affiliate_link)
            <a class="btn" href="{{ $product->affiliate_link }}" rel="nofollow sponsored noopener" target="_blank">
                Ansehen<span class="visually-hidden"> (öffnet bei Amazon in neuem Tab)</span>
            </a>
        @endif
    </div>
</li>
