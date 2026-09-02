<x-layouts.app :title="$category->name">
    <h1>{{ $category->name }}</h1>

    @if ($category->children->isNotEmpty())
        <ul class="meta-list">
            @foreach ($category->children as $child)
                <li><a href="{{ '/'.$category->slug.'/'.$child->slug.'/' }}">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    @if ($category->posts->isEmpty())
        <p>Für diese Kategorie sind noch keine Geschichten hinterlegt.</p>
    @else
        <ul class="card-grid">
            @foreach ($category->posts as $post)
                <li class="card">
                    <h3><a href="{{ $post->url() }}">{{ $post->title }}</a></h3>
                    @if ($post->excerpt)
                        <p>{{ str(strip_tags($post->excerpt))->limit(140) }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
