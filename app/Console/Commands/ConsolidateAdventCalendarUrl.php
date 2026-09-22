<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * The interactive Advent calendar moved from "/adventskalendergeschichten/"
 * to "/adventskalender/" (see routes/web.php and StaticSiteExporter) - a
 * door-opening mechanism isn't itself a "Geschichte" (story), unlike the
 * three real year-story posts still linked from the nav dropdown, and the
 * existing "Adventskalender" Page's URL was the better, already-correct
 * name for it. Redirects the old URL to the new one, and repoints the
 * Instagram-post-removal redirect (content:remove-instagram-advent-post,
 * 2026-09-16) that used to send visitors to the old URL, so it goes
 * straight to the new one instead of chaining through a second redirect.
 */
class ConsolidateAdventCalendarUrl extends Command
{
    protected $signature = 'content:consolidate-advent-calendar-url';

    protected $description = 'Redirect the old /adventskalendergeschichten/ URL to /adventskalender/';

    public function handle(): int
    {
        Redirect::updateOrCreate(
            ['from_path' => '/adventskalendergeschichten'],
            ['to_path' => '/adventskalender/', 'status_code' => 301]
        );
        $this->info("Redirected '/adventskalendergeschichten' -> '/adventskalender/'.");

        $updated = Redirect::where('to_path', '/adventskalendergeschichten/')
            ->update(['to_path' => '/adventskalender/']);

        if ($updated > 0) {
            $this->info("Repointed {$updated} existing redirect(s) that targeted the old URL.");
        } else {
            $this->info('No existing redirects targeted the old URL, nothing to repoint.');
        }

        return self::SUCCESS;
    }
}
