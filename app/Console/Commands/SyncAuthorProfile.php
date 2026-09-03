<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Page;
use Illuminate\Console\Command;

/**
 * One-time sync for the author-profile page - real, hand-authored content
 * (not derived from the WordPress export), same pattern as SyncLegalPages.
 * Idempotent (upserts by slug), safe to re-run whenever the bio text changes.
 */
class SyncAuthorProfile extends Command
{
    protected $signature = 'content:sync-author-profile';

    protected $description = 'Upsert the "Über den Autor" page with Olaf Taubert\'s bio';

    public function handle(): int
    {
        Page::updateOrCreate(
            ['slug' => 'ueber-den-autor'],
            [
                'title' => 'Über den Autor',
                'meta_description' => 'Lernen Sie Olaf Taubert kennen: Rechtsanwalt und Notar in Wunstorf, und der Kopf hinter Ollis Weihnachtsgeschichten. Jetzt mehr erfahren.',
                'body_html' => <<<'HTML'
<p>Eine ruhige Hand bei Verträgen, ein feines Gespür für Paragraphen und ein Herz für die wohl magischste Zeit des Jahres. Wenn der Alltag als Notar in Wunstorf ruht, taucht Olaf Taubert in eine ganz andere Welt ein: die Welt der Weihnachtsgeschichten.</p>

<h2>Rechtsanwalt, Notar und Geschichtenmacher aus Wunstorf</h2>
<p>Im beruflichen Leben steht Olaf Taubert mit seiner Kanzlei in Wunstorf für Verlässlichkeit, Struktur und Klarsicht. Als etablierter Rechtsanwalt und Notar im Herzen von Wunstorf vertrauen ihm Menschen seit vielen Jahren in den unterschiedlichsten Lebenslagen. Wer beruflich Verträge prüft, Schicksale begleitet und präzise formuliert, entwickelt im Laufe der Jahre einen scharfen Blick für das, was Menschen bewegt: ihre Sorgen, ihre Hoffnungen und die feinen Nuancen des Zusammenlebens.</p>
<p>Genau diese Menschenkenntnis fließt in ein Herzensprojekt ein, das einen wohltuenden Gegenpol zum Paragraphendschungel bildet. Auf Ollis Weihnachtsgeschichten erweckt Olaf Taubert die festliche Zeit zum Leben.</p>

<h2>Warum Weihnachtsgeschichten?</h2>
<p>Weihnachten ist für Olaf Taubert weit mehr als nur ein Fest im Kalender. Es ist die Zeit, in der die Hektik des Alltagstrotts einen Augenblick innehält, in der Lichter die dunklen Winterabende erhellen und in der Werte wie Gemeinschaft, Nächstenliebe und Zuversicht wieder eine spürbare Bühne bekommen.</p>
<p>Sein Anliegen als Autor ist einfach und berührend zugleich:</p>
<ul>
    <li><strong>Echte Emotionen wecken:</strong> Seine Erzählungen nehmen Leserinnen und Leser mit auf eine Reise voller Wärme, Nachdenklichkeit und feinem Humor.</li>
    <li><strong>Bilder im Kopf entstehen lassen:</strong> Durch vertraute Kulissen, nostalgische Momente und lebendige Charaktere werden die Geschichten greifbar nah.</li>
    <li><strong>Innehalten schenken:</strong> Ein paar Minuten Auszeit mitten in der oft hektischen Vorweihnachtszeit bieten, um den wahren Zauber des Festes neu zu entdecken.</li>
</ul>
<p>Ob stimmungsvolle Kurzgeschichten, besinnliche Denkanstöße oder kleine Erzählungen für ruhige Abende bei Kerzenschein: Mit Worten schafft Olaf Taubert Räume, in denen man sich geborgen fühlt.</p>

<h2>Verwurzelt in der Region</h2>
<p>Als Wunstorfer kennt und schätzt Olaf Taubert die beschauliche Atmosphäre seiner Heimat. Der Blick auf das Steinhuder Meer, das winterliche Treiben in Steinhude und die eisigen Spaziergänge an der frischen Luft bieten die perfekte Kulisse, um neue Ideen für herzerwärmende Weihnachtsgeschichten zu sammeln.</p>
<blockquote>
    <p>„Im Notariat geht es um Vertrauen und Verlässlichkeit. In meinen Weihnachtsgeschichten geht es um die kleinen Wunder, die wir im Alltagsstress oft übersehen.“</p>
    <p>– Olaf Taubert</p>
</blockquote>

<h2>Herzliche Einladung zum Schmökern</h2>
<p>Lassen Sie sich verzaubern, lehnen Sie sich zurück und genießen Sie eine Tasse Tee oder Glühwein. Im <a href="/weihnachtsblog/">Weihnachtsblog</a> finden Sie regelmäßig neue Geschichten, die das Herz erwärmen und die Vorfreude auf das Weihnachtsfest steigern.</p>
<p>Ich freue mich über Ihren Besuch und wünsche Ihnen eine besinnliche Lesezeit!</p>
<p>Eine gemütliche Vorweihnachtszeit und eine lichterfüllte Lesezeit wünscht<br>
Ihr Olaf Taubert</p>
HTML,
            ]
        );

        $this->info('"Über den Autor" page synced.');

        return self::SUCCESS;
    }
}
