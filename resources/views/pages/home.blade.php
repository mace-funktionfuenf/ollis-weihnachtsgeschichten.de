<x-layouts.app description="Seit über 20 Jahren schreibt Olaf Taubert jedes Jahr eine neue, lustige Weihnachtsgeschichte. Dazu Geschenkideen, Rezepte und mehr rund um Weihnachten.">
    <h1>Ollis Weihnachtsgeschichten</h1>

    @if ($intro)
        <div class="lede">{!! $intro->body_html !!}</div>
    @endif

    @if ($latestPost)
        <h2>Die neueste Geschichte</h2>
        <p>
            <a class="btn" href="{{ $latestPost->url() }}">{{ $latestPost->title }}</a>
        </p>
    @endif

    @if ($recentPosts->isNotEmpty())
        <h2>Weitere Geschichten</h2>
        <ul class="card-grid">
            @foreach ($recentPosts as $post)
                <li class="card">
                    <h3><a href="{{ $post->url() }}">{{ $post->title }}</a></h3>
                    @if ($post->excerpt)
                        <p>{{ str(strip_tags($post->excerpt))->limit(140) }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p><a href="/weihnachtsgeschichten/">Alle Weihnachtsgeschichten ansehen &rarr;</a></p>
</x-layouts.app>
