<x-layouts.app
    title="Adventskalender"
    description="Unser Adventskalender mit 24 neuen Weihnachtsgeschichten – jeden Tag öffnet sich ein neues Türchen."
    canonical="/adventskalender/"
>
    <h1>Adventskalender</h1>
    <p class="lede">
        24 neue Geschichten, ein Türchen pro Tag: Jedes Türchen öffnet sich von selbst, sobald der
        jeweilige Dezembertag erreicht ist – schauen Sie jeden Tag im Advent wieder vorbei.
    </p>

    <ol class="advent-grid">
        @foreach ($doors as $door)
            <li class="door" data-day="{{ $door->day }}">
                <h2 class="door-number">Türchen {{ $door->day }}</h2>
                <button
                    type="button"
                    class="door-toggle"
                    aria-expanded="false"
                    aria-controls="door-story-{{ $door->day }}"
                    disabled
                >
                    <span class="door-status">Öffnet am {{ $door->unlocksOn() }}</span>
                </button>
                <div class="door-story" id="door-story-{{ $door->day }}" hidden>
                    <h3>{{ $door->title }}</h3>
                    {!! \App\Support\ContentHtml::externalLinksInNewTab($door->story_html) !!}
                </div>
            </li>
        @endforeach
    </ol>

    <noscript>
        <style>
            .door-toggle { display: none; }
            .door-story { display: block !important; }
        </style>
        <p>Ihr Browser führt kein JavaScript aus, daher werden hier alle Geschichten direkt angezeigt.</p>
    </noscript>

    @if ($page)
        <div class="content">{!! \App\Support\ContentHtml::externalLinksInNewTab($page->body_html) !!}</div>
    @endif

    <style>
        .advent-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); gap: 1rem;
            list-style: none; padding: 0; margin: 2rem 0;
        }
        .door {
            border: 1px solid var(--border); border-radius: var(--radius); background: #fff;
            box-shadow: var(--shadow); padding: 0.9rem; text-align: center;
        }
        .door-number {
            margin: 0 0 0.6rem; font-size: 1rem; color: var(--green-dark);
        }
        .door-number::after { content: none; }
        .door-toggle {
            width: 100%; border: 1px solid var(--border); border-radius: 999px; background: var(--cream);
            color: var(--ink-soft); padding: 0.5rem 0.6rem; font-size: 0.78rem; cursor: not-allowed;
        }
        .door.unlocked .door-toggle {
            background: var(--gold); color: var(--ink); border-color: var(--gold); cursor: pointer;
            font-weight: 600;
        }
        .door.unlocked .door-toggle:hover, .door.unlocked .door-toggle:focus-visible {
            background: var(--gold-light);
        }
        .door-story {
            margin-top: 0.9rem; text-align: left; border-top: 1px solid var(--border); padding-top: 0.75rem;
        }
        .door-story h3 { margin: 0 0 0.5rem; font-size: 1.05rem; }
    </style>

    <script>
        (function () {
            // ?preview in the URL unlocks every door regardless of today's
            // date - lets you or the client see the real, rendered,
            // interactive calendar (not just the raw text in Filament)
            // without waiting for December. Not linked anywhere on the
            // page on purpose; nothing sensitive is behind it either way,
            // since every story is already in the static HTML - see the
            // note on this page in CLAUDE.md.
            var preview = new URLSearchParams(location.search).has('preview');
            var today = new Date();
            var isDecember = today.getMonth() === 11;
            var currentDay = today.getDate();

            document.querySelectorAll('.door').forEach(function (door) {
                var day = parseInt(door.dataset.day, 10);
                if (!preview && (!isDecember || currentDay < day)) {
                    return;
                }

                var button = door.querySelector('.door-toggle');
                var status = door.querySelector('.door-status');
                var story = document.getElementById(button.getAttribute('aria-controls'));

                door.classList.add('unlocked');
                button.disabled = false;
                status.textContent = 'Türchen öffnen';

                button.addEventListener('click', function () {
                    var isOpen = button.getAttribute('aria-expanded') === 'true';
                    button.setAttribute('aria-expanded', String(!isOpen));
                    story.hidden = isOpen;
                    status.textContent = isOpen ? 'Türchen öffnen' : 'Türchen schließen';
                });
            });
        })();
    </script>
</x-layouts.app>
