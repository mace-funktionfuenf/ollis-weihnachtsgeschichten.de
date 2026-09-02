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
            --gold: #c8973a;
            --cream: #fdfaf3;
            --ink: #2a2420;
            --border: #e3dbc9;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, 'Times New Roman', serif;
            background: var(--cream);
            color: var(--ink);
            line-height: 1.6;
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
            background: var(--green-dark);
            color: #fff;
        }
        header.site .bar {
            max-width: 60rem; margin: 0 auto; padding: 1rem 1.25rem;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
        }
        header.site a { color: #fff; text-decoration: none; }
        header.site .brand { font-size: 1.4rem; font-weight: bold; }
        nav.main ul {
            list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 1.25rem;
        }
        nav.main a { padding: 0.25rem 0; border-bottom: 2px solid transparent; }
        nav.main a:hover, nav.main a:focus-visible { border-bottom-color: var(--gold); }
        main {
            max-width: 42rem; margin: 0 auto; padding: 2rem 1.25rem 3rem;
        }
        main img { max-width: 100%; height: auto; }
        h1, h2, h3 { color: var(--green-dark); font-family: Georgia, serif; }
        .lede { font-size: 1.1rem; color: #4a4238; }
        .card-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: 1.25rem;
            list-style: none; padding: 0; margin: 1.5rem 0;
        }
        .card {
            border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #fff;
        }
        .card img { border-radius: 0.25rem; }
        .card h3 { font-size: 1.05rem; margin: 0.5rem 0; }
        .price { font-weight: bold; color: var(--green-dark); }
        .price del { color: #8a8175; font-weight: normal; margin-right: 0.4rem; }
        .btn {
            display: inline-block; margin-top: 0.5rem; padding: 0.5rem 0.9rem;
            background: var(--red); color: #fff; text-decoration: none; border-radius: 0.3rem; font-size: 0.95rem;
        }
        .btn:hover, .btn:focus-visible { background: var(--green); }
        .btn.secondary { background: transparent; color: var(--red); border: 1px solid var(--red); }
        .unavailable { color: #8a8175; font-size: 0.9rem; }
        .flag {
            background: #fff6e0; border: 1px solid var(--gold); border-radius: 0.4rem;
            padding: 0.75rem 1rem; margin: 1rem 0; font-size: 0.95rem;
        }
        .meta-list { list-style: none; padding: 0; margin: 1rem 0; }
        .meta-list a { text-decoration: none; }
        footer.site {
            background: var(--green-dark); color: #f0ece0; margin-top: 3rem;
        }
        footer.site .bar {
            max-width: 60rem; margin: 0 auto; padding: 1.5rem 1.25rem; display: flex; flex-wrap: wrap;
            justify-content: space-between; gap: 1rem; font-size: 0.9rem;
        }
        footer.site a { color: #fff; }
    </style>
</head>
<body>
    <a class="skip-link" href="#main">Zum Inhalt springen</a>
    <header class="site">
        <div class="bar">
            <a class="brand" href="/">Ollis Weihnachtsgeschichten</a>
            <nav class="main" aria-label="Hauptnavigation">
                <ul>
                    <li><a href="/weihnachtsgeschichten/">Weihnachtsgeschichten</a></li>
                    <li><a href="/weihnachtsblog/">Weihnachtsblog</a></li>
                    <li><a href="/geschenkideen/">Geschenkideen</a></li>
                    <li><a href="/weihnachtsgeschenke/">Geschenke</a></li>
                    <li><a href="/adventskalender/">Adventskalender</a></li>
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
            <ul class="meta-list" style="display:flex; gap:1rem;">
                <li><a href="/impressum/">Impressum &amp; Datenschutz</a></li>
                <li><a href="https://www.facebook.com/OllisWeihnachtsgeschichten" rel="noopener">Facebook</a></li>
                <li><a href="https://www.twitter.com/weihnachten2412" rel="noopener">Twitter</a></li>
            </ul>
        </div>
    </footer>
</body>
</html>
