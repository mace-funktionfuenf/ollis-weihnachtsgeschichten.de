@props(['post'])
<li class="card">
    @if ($post->featured_image)
        <div class="thumb photo">
            <img src="{{ asset('storage/'.$post->featured_image) }}" alt="{{ $post->title }}" loading="lazy">
        </div>
    @endif
    @if ($post->published_at)
        <p class="card-date">{{ $post->published_at->translatedFormat('d. F Y') }}</p>
    @endif
    <h3><a href="{{ $post->url() }}">{{ $post->title }}</a></h3>
    <p>{{ $post->summary() }}</p>
    <div class="actions">
        <a class="btn secondary" href="{{ $post->url() }}">Weiterlesen</a>
    </div>
</li>
