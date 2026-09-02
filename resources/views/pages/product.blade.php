<x-layouts.app :title="$product->title" :description="$product->meta_description">
    <article>
        <h1>{{ $product->title }}</h1>

        @if ($product->image_path)
            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->title }}" width="240">
        @endif

        @if ($product->price)
            <p class="price">
                @if ($product->price_old && $product->price_old > $product->price)
                    <del>{{ number_format((float) $product->price_old, 2, ',', '.') }} €</del>
                @endif
                {{ number_format((float) $product->price, 2, ',', '.') }} €
            </p>
        @endif

        @if (! $product->available)
            <p class="unavailable">Derzeit nicht verfügbar</p>
        @endif

        @if ($product->affiliate_link)
            <p>
                <a class="btn" href="{{ $product->affiliate_link }}" rel="nofollow sponsored noopener" target="_blank">
                    Bei Amazon ansehen<span class="visually-hidden"> (öffnet in neuem Tab)</span>
                </a>
            </p>
        @endif

        @if ($product->body_html)
            <div class="content">{!! $product->body_html !!}</div>
        @endif

        @if ($product->audiences->isNotEmpty() || $product->giftCategories->isNotEmpty() || $product->mediaTypes->isNotEmpty())
            <ul class="meta-list">
                @foreach ($product->mediaTypes as $t)
                    <li><a href="{{ $t->url() }}">{{ $t->name }}</a></li>
                @endforeach
                @foreach ($product->audiences as $a)
                    <li><a href="{{ $a->url() }}">Für {{ $a->name }}</a></li>
                @endforeach
                @foreach ($product->giftCategories as $g)
                    <li><a href="{{ $g->url() }}">{{ $g->name }}</a></li>
                @endforeach
            </ul>
        @endif

        @if ($product->related->isNotEmpty())
            <h2>Das könnte Ihnen auch gefallen</h2>
            <ul class="card-grid">
                @foreach ($product->related as $related)
                    <x-product-card :product="$related" />
                @endforeach
            </ul>
        @endif
    </article>
</x-layouts.app>
