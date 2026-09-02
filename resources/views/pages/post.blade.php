@php $categories = $post->categories; $tags = $post->tags; @endphp
<x-layouts.app :title="$post->title" :description="$post->meta_description">
    <article>
        <h1>{{ $post->title }}</h1>
        @if ($post->published_at)
            <p><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('d. F Y') }}</time></p>
        @endif

        <div class="content">{!! $post->body_html !!}</div>

        @if ($categories->isNotEmpty())
            <p>
                Kategorien:
                @foreach ($categories as $category)
                    <a href="{{ '/'.$category->slug.'/' }}">{{ $category->name }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif

        @if ($tags->isNotEmpty())
            <p>
                Schlagworte: {{ $tags->pluck('name')->join(', ') }}
            </p>
        @endif
    </article>
</x-layouts.app>
