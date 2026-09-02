<x-layouts.app :title="$shop->title">
    <article>
        <h1>{{ $shop->title }}</h1>
        @if ($shop->widget_content)
            <div class="content">{!! $shop->widget_content !!}</div>
        @endif
    </article>
</x-layouts.app>
