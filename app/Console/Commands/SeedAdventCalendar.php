<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdventDoor;
use Illuminate\Console\Command;

/**
 * Scaffolds the 24 Advent calendar doors with placeholder text so the
 * date-gating mechanism (see resources/views/pages/advent-calendar.blade.php)
 * can be seen and tested before the real stories are written. Only ever
 * creates a door that doesn't exist yet - real, edited text is never
 * overwritten by a re-run.
 */
class SeedAdventCalendar extends Command
{
    protected $signature = 'content:seed-advent-calendar';

    protected $description = 'Create any missing Advent calendar door (1-24) with placeholder text';

    public function handle(): int
    {
        $created = 0;

        foreach (range(1, 24) as $day) {
            if (AdventDoor::where('day', $day)->exists()) {
                continue;
            }

            AdventDoor::create([
                'day' => $day,
                'title' => "Türchen {$day} (Platzhalter)",
                'story_html' => "<p>Hier steht bald die Geschichte für Türchen {$day}. Text bitte in Filament unter „Adventskalender“ eintragen.</p>",
            ]);
            $created++;
        }

        $this->info("{$created} Platzhalter-Türchen angelegt.");

        return self::SUCCESS;
    }
}
