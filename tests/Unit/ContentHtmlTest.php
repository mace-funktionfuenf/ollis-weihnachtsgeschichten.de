<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ContentHtml;
use Tests\TestCase;

class ContentHtmlTest extends TestCase
{
    public function test_external_links_open_in_new_tab(): void
    {
        $html = '<p>Siehe <a href="https://example.com/foo">extern</a>.</p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('rel="noopener noreferrer"', $result);
        $this->assertStringContainsString('href="https://example.com/foo"', $result);
    }

    public function test_internal_links_are_left_alone(): void
    {
        $html = '<p>Siehe <a href="/weihnachtsgeschichten/">intern</a> und '
            .'<a href="https://www.ollis-weihnachtsgeschichten.de/impressum/">Impressum</a>.</p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringNotContainsString('target="_blank"', $result);
    }

    public function test_anchor_mailto_and_tel_links_are_untouched(): void
    {
        $html = '<p><a href="#top">Nach oben</a> <a href="mailto:info@example.com">Mail</a> '
            .'<a href="tel:+491234">Anruf</a></p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringNotContainsString('target="_blank"', $result);
    }

    public function test_empty_and_null_input_is_handled(): void
    {
        $this->assertSame('', ContentHtml::externalLinksInNewTab(null));
        $this->assertSame('', ContentHtml::externalLinksInNewTab(''));
    }

    public function test_does_not_duplicate_an_existing_rel_noopener(): void
    {
        $html = '<a href="https://amazon.de/foo" rel="noopener noreferrer">Angebot</a>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertSame(1, substr_count($result, 'noopener'));
        $this->assertSame(1, substr_count($result, 'noreferrer'));
    }

    public function test_preserves_umlauts(): void
    {
        $html = '<p>Frohe Weihnachten! <a href="https://example.com">Grüße</a></p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringContainsString('Grüße', $result);
    }

    public function test_heading_that_skips_straight_to_h3_is_shifted_up_to_h2(): void
    {
        $html = '<p>Text</p><h3>Viel Spaß und lustige Weihnachten!</h3><p>mehr Text</p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringContainsString('<h2>Viel Spaß und lustige Weihnachten!</h2>', $result);
        $this->assertStringNotContainsString('<h3>', $result);
    }

    public function test_relative_heading_nesting_is_preserved_when_shifting(): void
    {
        $html = '<h3>Abschnitt</h3><h4>Unterabschnitt</h4>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringContainsString('<h2>Abschnitt</h2>', $result);
        $this->assertStringContainsString('<h3>Unterabschnitt</h3>', $result);
    }

    public function test_content_already_starting_at_h2_is_left_alone(): void
    {
        $html = '<h2>Schon richtig</h2><p>Text</p>';

        $result = ContentHtml::externalLinksInNewTab($html);

        $this->assertStringContainsString('<h2>Schon richtig</h2>', $result);
    }
}
