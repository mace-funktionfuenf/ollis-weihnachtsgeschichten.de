@props(['title' => null, 'description' => null])
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' – Ollis Weihnachtsgeschichten' : 'Ollis Weihnachtsgeschichten' }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <style>
        :root {
            --green: #2f5d3a;
            --green-dark: #1e3d26;
            --red: #a3312a;
            --red-dark: #7f2621;
            --gold: #c8973a;
            --gold-light: #e8c674;
            --cream: #fdfaf3;
            --ink: #2a2420;
            --ink-soft: #4a4238;
            --border: #e3dbc9;
            --radius: 0.7rem;
            --shadow: 0 2px 10px rgba(30, 61, 38, 0.09);
            --shadow-hover: 0 10px 24px rgba(30, 61, 38, 0.16);
            --garland: repeating-linear-gradient(
                90deg,
                var(--red) 0 14px,
                var(--gold) 14px 28px,
                var(--green) 28px 42px
            );
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--cream);
            color: var(--ink);
            line-height: 1.65;
        }
        a { color: var(--red); }
        a:focus-visible, button:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }
        .skip-link {
            position: absolute; left: -999px; top: 0; background: var(--green-dark); color: #fff;
            padding: 0.75rem 1rem; z-index: 100;
        }
        .skip-link:focus { left: 0; }
        .visually-hidden {
            position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
            overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
        }

        header.site {
            position: sticky; top: 0; z-index: 20;
            background: var(--green-dark);
            color: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
        }
        header.site::before {
            content: ''; display: block; height: 5px; background: var(--garland);
        }
        header.site .bar {
            max-width: 100rem; margin: 0 auto; padding: 0.9rem 1.25rem;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem 1rem;
        }
        header.site a { color: #fff; text-decoration: none; }
        .brand {
            display: inline-flex; align-items: center; gap: 0.5rem;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 1.35rem; font-weight: bold; letter-spacing: 0.01em;
        }
        .brand svg { flex-shrink: 0; color: var(--gold-light); }

        .nav-toggle-input { position: absolute; opacity: 0; pointer-events: none; }
        .nav-toggle-btn {
            display: none; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: 0.4rem; cursor: pointer; flex-shrink: 0;
        }
        .nav-toggle-btn .bars, .nav-toggle-btn .bars::before, .nav-toggle-btn .bars::after {
            display: block; width: 1.35rem; height: 2px; background: #fff; border-radius: 2px;
        }
        .nav-toggle-btn .bars { position: relative; }
        .nav-toggle-btn .bars::before, .nav-toggle-btn .bars::after { content: ''; position: absolute; left: 0; }
        .nav-toggle-btn .bars::before { top: -6px; }
        .nav-toggle-btn .bars::after { top: 6px; }
        .nav-toggle-btn:hover, .nav-toggle-input:focus-visible ~ .nav-toggle-btn {
            background: rgba(255, 255, 255, 0.14); outline: none;
        }

        nav.main > ul {
            list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 0.25rem;
        }
        nav.main a {
            display: inline-block; padding: 0.4rem 0.75rem; border-radius: 999px;
            font-size: 0.95rem; transition: background-color 0.15s ease;
        }
        nav.main a:hover, nav.main a:focus-visible { background: rgba(255, 255, 255, 0.14); }

        nav.main li.has-dropdown { position: relative; display: flex; align-items: center; }
        nav.main .dropdown-toggle {
            appearance: none; background: none; border: 0; margin: 0; padding: 0 0.5rem 0 0.1rem;
            display: inline-flex; align-items: center; color: inherit;
        }
        nav.main .dropdown-toggle .caret {
            display: inline-block; width: 0.5em; height: 0.5em;
            border-right: 2px solid rgba(255, 255, 255, 0.75); border-bottom: 2px solid rgba(255, 255, 255, 0.75);
            transform: rotate(45deg); transition: transform 0.15s ease;
        }
        nav.main .dropdown-toggle[aria-expanded="true"] .caret { transform: rotate(-135deg); }
        nav.main .dropdown {
            list-style: none; margin: 0; padding: 0.25rem 0 0.5rem; display: none; flex-direction: column; gap: 0.1rem;
        }
        nav.main .dropdown a {
            display: block; border-radius: 0.4rem; padding: 0.5rem 0.75rem 0.5rem 1.75rem;
            color: rgba(255, 255, 255, 0.85);
        }
        nav.main .dropdown a:hover, nav.main .dropdown a:focus-visible { background: rgba(255, 255, 255, 0.12); }

        /* Mobile: the whole nav collapses behind the hamburger toggle, and
           each section is a tap-to-open accordion, driven by the tiny
           dropdown-toggle script at the bottom of the page (no hover to
           rely on for touch). The toggle button is a real tap target here. */
        @media (max-width: 60.99rem) {
            .nav-toggle-btn { display: inline-flex; }
            nav.main { display: none; flex-basis: 100%; order: 3; }
            .nav-toggle-input:checked ~ nav.main { display: block; }
            nav.main > ul { flex-direction: column; padding: 0.5rem 0 0.75rem; gap: 0; }
            nav.main li.has-dropdown { flex-wrap: wrap; }
            nav.main li.has-dropdown > a { flex: 1; }
            nav.main .dropdown-toggle { cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 999px; }
            nav.main .dropdown-toggle:hover { background: rgba(255, 255, 255, 0.14); }
            nav.main li.has-dropdown.is-open > .dropdown { display: flex; flex-basis: 100%; }
        }

        /* Desktop: hovering anywhere on the parent item opens its panel -
           the chevron is purely a visual "has a submenu" indicator here
           (pointer-events: none), not a separate target to aim for, and
           the panel sits flush against the item (no gap) so the pointer
           never crosses a dead zone on the way down into it. Only one
           panel can ever be open since only one item can be hovered (or
           focused) at a time. */
        @media (min-width: 61rem) {
            nav.main .dropdown-toggle { pointer-events: none; }
            nav.main .dropdown {
                position: absolute; top: 100%; left: 0; z-index: 30; padding: 0.5rem;
                background: #fff; border-radius: var(--radius); box-shadow: var(--shadow-hover);
                min-width: 16rem; max-height: 70vh; overflow-y: auto;
            }
            nav.main .dropdown a { color: var(--ink); white-space: nowrap; padding-left: 0.75rem; }
            nav.main .dropdown a:hover, nav.main .dropdown a:focus-visible { background: var(--cream); }
            nav.main li.has-dropdown:hover > .dropdown,
            nav.main li.has-dropdown:focus-within > .dropdown { display: flex; }
            nav.main li.has-dropdown:hover > .dropdown-toggle .caret,
            nav.main li.has-dropdown:focus-within > .dropdown-toggle .caret { transform: rotate(-135deg); }
        }

        @media (max-width: 26rem) {
            .brand { font-size: 1.1rem; }
        }

        main {
            max-width: 44rem; margin: 0 auto; padding: 2.5rem 1.25rem 3.5rem;
        }
        main:has(.card-grid) { max-width: 66rem; }
        main img { max-width: 100%; height: auto; }
        h1, h2, h3 {
            color: var(--green-dark); font-family: Georgia, 'Times New Roman', serif; line-height: 1.25;
        }
        h1 { font-size: 2.1rem; }
        h2 {
            font-size: 1.5rem; margin: 2.5rem 0 1rem;
        }
        h2::after { content: ''; display: block; width: 2.5rem; height: 3px; background: var(--gold); border-radius: 2px; margin-top: 0.5rem; }
        .lede { font-size: 1.15rem; color: var(--ink-soft); }

        .hero {
            background: linear-gradient(180deg, #ffffff, var(--cream) 70%);
            border: 1px solid var(--border); border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2.25rem 2rem; margin-bottom: 2rem;
        }
        .hero h1 { margin-top: 0; }
        .hero .lede { margin-bottom: 0; }

        .card-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); gap: 1.5rem;
            list-style: none; padding: 0; margin: 1.5rem 0;
        }
        .card {
            display: flex; flex-direction: column;
            border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; background: #fff;
            box-shadow: var(--shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .card:hover, .card:focus-within { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
        .card .thumb {
            display: flex; align-items: center; justify-content: center;
            aspect-ratio: 4 / 3; background: var(--cream); border-radius: 0.4rem; overflow: hidden; margin-bottom: 0.75rem;
        }
        .card .thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card .thumb.photo img { width: 100%; height: 100%; object-fit: cover; }
        .card img { border-radius: 0.25rem; }
        .card h3 { font-size: 1.05rem; margin: 0.25rem 0 0.5rem; }
        .card p { margin: 0.25rem 0; color: var(--ink-soft); }
        .card .card-date {
            margin: 0; font-size: 0.78rem; font-weight: 600; color: var(--gold);
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .card .actions { margin-top: auto; padding-top: 0.75rem; }

        .price { font-weight: bold; color: var(--green-dark); }
        .price del { color: #8a8175; font-weight: normal; margin-right: 0.4rem; }

        .btn {
            display: inline-block; margin-top: 0.5rem; padding: 0.55rem 1.1rem;
            background: var(--red); color: #fff; text-decoration: none; border-radius: 999px; font-size: 0.95rem;
            box-shadow: 0 2px 6px rgba(163, 49, 42, 0.3);
            transition: background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn:hover, .btn:focus-visible { background: var(--red-dark); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(163, 49, 42, 0.35); }
        .btn.secondary {
            background: transparent; color: var(--red); border: 1px solid var(--red); box-shadow: none;
        }
        .btn.secondary:hover, .btn.secondary:focus-visible { background: rgba(163, 49, 42, 0.08); color: var(--red-dark); }

        .unavailable {
            display: inline-block; color: var(--red-dark); background: #f7e6e4;
            font-size: 0.85rem; padding: 0.2rem 0.65rem; border-radius: 999px;
        }
        .flag {
            background: #fff6e0; border: 1px solid var(--gold); border-radius: var(--radius);
            padding: 0.85rem 1.1rem; margin: 1.25rem 0; font-size: 0.95rem;
        }
        .meta-list { list-style: none; padding: 0; margin: 1rem 0; display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .meta-list a, .meta-list span {
            display: inline-block; text-decoration: none; background: #fff; border: 1px solid var(--border);
            border-radius: 999px; padding: 0.3rem 0.85rem; font-size: 0.9rem; color: var(--ink-soft);
            transition: border-color 0.15s ease, color 0.15s ease;
        }
        .meta-list a:hover, .meta-list a:focus-visible { border-color: var(--gold); color: var(--red); }

        .article-header { margin-bottom: 1.5rem; }
        .article-header h1 { margin-bottom: 0.5rem; }
        .article-meta {
            display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; align-items: center;
            color: var(--ink-soft); font-size: 0.95rem;
        }
        .hero-image {
            display: block; width: 100%; height: 22rem; object-fit: cover;
            border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 1.75rem;
        }

        .content { font-size: 1.05rem; display: flow-root; }
        .content > * + * { margin-top: 1rem; }
        .content h2, .content h3 { margin-top: 2rem; }
        .content ul, .content ol { padding-left: 1.5rem; }
        .content li + li { margin-top: 0.35rem; }
        .content blockquote {
            margin: 1.25rem 0; padding: 0.25rem 1.25rem; border-left: 3px solid var(--gold);
            color: var(--ink-soft); font-style: italic;
        }
        .content figure { margin: 1.5rem 0; }
        .content figure img { border-radius: var(--radius); }
        .content figcaption { margin-top: 0.5rem; font-size: 0.9rem; color: var(--ink-soft); text-align: center; }

        /* WordPress's classic-editor image alignment classes, imported
           verbatim in post/page bodies - unstyled, they left images
           jammed flush against the surrounding text with zero breathing
           room, which is what made older posts look unfinished. */
        .content img { border-radius: 0.4rem; }
        .content .alignleft { float: left; margin: 0.35rem 1.5rem 1rem 0; }
        .content .alignright { float: right; margin: 0.35rem 0 1rem 1.5rem; }
        .content .aligncenter { display: block; float: none; margin: 1.5rem auto; }

        footer.site {
            background: var(--green-dark); color: #f0ece0; margin-top: 3.5rem;
        }
        footer.site::before {
            content: ''; display: block; height: 4px; background: var(--garland);
        }
        footer.site .bar {
            max-width: 64rem; margin: 0 auto; padding: 1.5rem 1.25rem; display: flex; flex-wrap: wrap;
            justify-content: space-between; align-items: center; gap: 1rem; font-size: 0.9rem;
        }
        footer.site a { color: #f0ece0; text-decoration: none; }
        footer.site a:hover, footer.site a:focus-visible { color: var(--gold-light); }
        footer.site .meta-list a {
            background: transparent; border-color: rgba(255, 255, 255, 0.25); color: #f0ece0;
        }
        footer.site .meta-list a:hover, footer.site .meta-list a:focus-visible {
            border-color: var(--gold-light); color: var(--gold-light);
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .card, .btn, nav.main .dropdown-toggle .caret { transition: none; }
            .card:hover { transform: none; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main">Zum Inhalt springen</a>
    <header class="site">
        <div class="bar">
            <a class="brand" href="/">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M12 2v20M4.5 6l15 12M19.5 6l-15 12M6 4l6 4 6-4M6 20l6-4 6 4" stroke-linecap="round"/>
                </svg>
                Ollis Weihnachtsgeschichten
            </a>
            <input type="checkbox" id="nav-toggle" class="nav-toggle-input">
            <label for="nav-toggle" class="nav-toggle-btn">
                <span class="visually-hidden">Menü öffnen</span>
                <span class="bars" aria-hidden="true"></span>
            </label>
            <nav class="main" aria-label="Hauptnavigation">
                <ul>
                    <li class="has-dropdown">
                        <a href="/weihnachtsgeschichten/">Ollis Weihnachtsgeschichten</a>
                        <button type="button" class="dropdown-toggle" aria-expanded="false" aria-label="Untermenü „Ollis Weihnachtsgeschichten“ öffnen">
                            <span class="caret" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown">
                            <li><a href="/weihnachtsgeschichten/">Alle Weihnachtsgeschichten</a></li>
                            @foreach ($navStories as $story)
                                <li><a href="{{ $story->url() }}">Weihnachtsgeschichte {{ $story->published_at->year }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="has-dropdown">
                        <a href="/adventskalendergeschichten/">Adventskalendergeschichten</a>
                        <button type="button" class="dropdown-toggle" aria-expanded="false" aria-label="Untermenü „Adventskalendergeschichten“ öffnen">
                            <span class="caret" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown">
                            <li><a href="/adventskalender/">Adventskalender</a></li>
                            <li><a href="/adventskalendergeschichte-2014/">Adventskalendergeschichte – Durchstarter</a></li>
                            <li><a href="/adventskalendergeschichte-2007-eine-kreuzfahrt-die-karibik/">Adventskalendergeschichte – Kreuzfahrt</a></li>
                            <li><a href="/adventskalendergeschichte-2006-pleiten-pech-und-pannen-im-weihnachtsdorf/">Adventskalendergeschichte – Pleiten, Pech, Pannen</a></li>
                        </ul>
                    </li>
                    <li class="has-dropdown">
                        <a href="/die-schoensten-weihnachtsgeschichten/">Weihnachtsgeschichten</a>
                        <button type="button" class="dropdown-toggle" aria-expanded="false" aria-label="Untermenü „Weihnachtsgeschichten“ öffnen">
                            <span class="caret" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown">
                            <li><a href="/die-schoensten-weihnachtsgeschichten/weihnachtsgeschichten-zum-vorlesen/">zum Lesen &amp; Vorlesen</a></li>
                            <li><a href="/die-schoensten-weihnachtsgeschichten/weihnachtsgeschichten-auf-dvd/">auf DVD &amp; BlueRay</a></li>
                            <li><a href="/die-schoensten-weihnachtsgeschichten/weihnachtsgeschichten-als-hoerbuch/">als Hörspiel und Hörbuch</a></li>
                            <li><a href="/weihnachtsgeschichten-mit-lokalem-bezug/">Weihnachtsgeschichten mit lokalem Bezug</a></li>
                        </ul>
                    </li>
                    <li class="has-dropdown">
                        <a href="/weihnachtsblog/">Weihnachtsblog</a>
                        <button type="button" class="dropdown-toggle" aria-expanded="false" aria-label="Untermenü „Weihnachtsblog“ öffnen">
                            <span class="caret" aria-hidden="true"></span>
                        </button>
                        <ul class="dropdown">
                            <li><a href="/weihnachtsblog/">Weihnachtsblog</a></li>
                            <li><a href="/weihnachtsgeschenke/">Weihnachtsgeschenke</a></li>
                            <li><a href="/rezepte_zu_weihnachten/">Rezepte zu Weihnachten</a></li>
                            <li><a href="/weihnachtscartoon/">Weihnachtscartoon</a></li>
                        </ul>
                    </li>
                    <li><a href="/geschenkideen/">Geschenkideen</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="site">
        <div class="bar">
            <p>&copy; {{ date('Y') }} Ollis Weihnachtsgeschichten – Olaf Taubert</p>
            <ul class="meta-list">
                <li><a href="/impressum/">Impressum</a></li>
                <li><a href="/datenschutz/">Datenschutz</a></li>
            </ul>
        </div>
    </footer>

    <script>
        // Progressive enhancement only: every link above already works
        // without this. It just lets the mobile accordion sections (and,
        // as a bonus, a click on desktop) open/close, closing any other
        // open section first so only one panel is ever visible.
        document.querySelectorAll('.dropdown-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var li = btn.closest('li');
                var willOpen = btn.getAttribute('aria-expanded') !== 'true';

                document.querySelectorAll('.has-dropdown.is-open').forEach(function (openLi) {
                    if (openLi !== li) {
                        openLi.classList.remove('is-open');
                        openLi.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
                    }
                });

                btn.setAttribute('aria-expanded', String(willOpen));
                li.classList.toggle('is-open', willOpen);
            });
        });
    </script>
</body>
</html>
