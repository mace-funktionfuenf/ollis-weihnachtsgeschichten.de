@php $categories = $post->categories; $tags = $post->tags; @endphp
<x-layouts.app :title="$post->title" :description="$post->meta_description">
    <article>
        <header class="article-header">
            <h1>{{ $post->title }}</h1>
            <div class="article-meta">
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('d. F Y') }}</time>
                @endif
                @if ($post->author_name)
                    <span>von {{ $post->author_name }}</span>
                @endif
            </div>
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

        @if ($post->products->isNotEmpty())
            <h2>Das könnte Ihnen auch gefallen</h2>
            <ul class="card-grid">
                @foreach ($post->products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </ul>
        @endif
    </article>
</x-layouts.app>
