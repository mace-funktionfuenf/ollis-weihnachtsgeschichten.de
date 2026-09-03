<x-layouts.app description="Seit über 20 Jahren schreibt Olaf Taubert jedes Jahr eine neue, lustige Weihnachtsgeschichte. Dazu Geschenkideen, Rezepte und mehr rund um Weihnachten.">
    <div class="hero">
        <h1>Ollis Weihnachtsgeschichten</h1>

        @if ($intro)
            <div class="lede">{!! \App\Support\ContentHtml::externalLinksInNewTab($intro->body_html) !!}</div>
        @endif

        @if ($latestPost)
            <p>
                <a class="btn" href="{{ $latestPost->url() }}">Neueste Geschichte: {{ $latestPost->title }}</a>
            </p>
        @endif
    </div>

    @if ($recentPosts->isNotEmpty())
        <h2>Weitere Geschichten</h2>
        <ul class="card-grid">
            @foreach ($recentPosts as $post)
                <x-post-card :post="$post" />
            @endforeach
        </ul>
    @endif

    <p><a href="/weihnachtsgeschichten/">Alle Weihnachtsgeschichten ansehen &rarr;</a></p>
</x-layouts.app>
