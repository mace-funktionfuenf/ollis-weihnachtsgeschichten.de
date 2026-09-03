<x-layouts.app :title="$page->title" :description="$page->meta_description">
    <article>
        <h1>{{ $page->title }}</h1>
        <div class="content">{!! \App\Support\ContentHtml::externalLinksInNewTab($page->body_html) !!}</div>
    </article>
</x-layouts.app>
