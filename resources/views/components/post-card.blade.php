@props(['post'])
<li class="card">
    @if ($post->featured_image)
        <div class="thumb photo">
            <img src="{{ asset('storage/'.$post->featured_image) }}" alt="{{ $post->title }}" loading="lazy">
        </div>
    @endif
    <h3><a href="{{ $post->url() }}">{{ $post->title }}</a></h3>
    <p>{{ $post->summary() }}</p>
    <div class="actions">
        <a class="btn secondary" href="{{ $post->url() }}">Weiterlesen</a>
    </div>
</li>
