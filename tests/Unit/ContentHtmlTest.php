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
}
