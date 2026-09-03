@php $categories = $post->categories; $tags = $post->tags; $availableProducts = $post->products->where('available', true); @endphp
<x-layouts.app :title="$post->title" :description="$post->meta_description">
    <article>
        <header class="article-header">
            <h1>{{ $post->title }}</h1>
            @if ($post->author_name)
                <div class="article-meta">
                    <span>von <a href="/ueber-den-autor/">{{ $post->author_name }}</a></span>
                </div>
            @endif
        </header>

        @if ($post->featured_image)
            <img class="hero-image" src="{{ asset('storage/'.$post->featured_image) }}" alt="{{ $post->title }}">
        @endif

        <div class="content">{!! \App\Support\ContentHtml::externalLinksInNewTab($post->body_html) !!}</div>

        @if ($categories->isNotEmpty() || $tags->isNotEmpty())
            <ul class="meta-list">
                @foreach ($categories as $category)
                    <li><a href="{{ '/'.$category->slug.'/' }}">{{ $category->name }}</a></li>
                @endforeach
                @foreach ($tags as $tag)
                    <li><span>{{ $tag->name }}</span></li>
                @endforeach
            </ul>
        @endif

        @if ($availableProducts->isNotEmpty())
            <h2>Das könnte Ihnen auch gefallen</h2>
            <ul class="card-grid">
                @foreach ($availableProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </ul>
        @endif
    </article>
</x-layouts.app>
