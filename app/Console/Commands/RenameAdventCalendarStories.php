<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * The three legacy "Adventskalendergeschichte" posts (2006/2007/2014 - see
 * CLAUDE.md, distinct from the new /adventskalendergeschichten/ door
 * calendar) carried their publication year in both slug and title/body,
 * making them look dated and one-off rather than evergreen. Per the
 * 2026-09-16 client review: drop the year everywhere, and leave a 301 from
 * every old URL so nothing that already links or ranks for the old slug
 * 404s.
 */
class RenameAdventCalendarStories extends Command
{
    protected $signature = 'content:rename-advent-stories';

    protected $description = 'Strip the year from the three legacy Adventskalendergeschichte posts (slug, title, body) and redirect the old URLs';

    /** @var list<array{old: string, new: string, title: string}> */
    private const RENAMES = [
        [
            'old' => 'adventskalendergeschichte-2007-eine-kreuzfahrt-die-karibik',
            'new' => 'adventskalendergeschichte-eine-kreuzfahrt-die-karibik',
            'title' => 'Adventskalendergeschichte: Eine Kreuzfahrt in die Karibik',
        ],
        [
            'old' => 'adventskalendergeschichte-2006-pleiten-pech-und-pannen-im-weihnachtsdorf',
            'new' => 'adventskalendergeschichte-pleiten-pech-und-pannen-im-weihnachtsdorf',
            'title' => 'Adventskalendergeschichte: Pleiten, Pech und Pannen im Weihnachtsdorf',
        ],
        [
            'old' => 'adventskalendergeschichte-2014',
            'new' => 'adventskalendergeschichte-durchstarter',
            'title' => 'Adventskalendergeschichte: Durchstarter',
        ],
    ];

    public function handle(): int
    {
        foreach (self::RENAMES as $rename) {
            $post = Post::where('slug', $rename['old'])->first();

            if (! $post) {
                $post = Post::where('slug', $rename['new'])->first();
                if ($post) {
                    $this->info("{$rename['new']} already renamed, skipping.");
                }
                continue;
            }

            $post->body_html = $this->stripYear($post->body_html);
            $post->title = $rename['title'];
            $post->slug = $rename['new'];
            $post->save();

            Redirect::updateOrCreate(
                ['from_path' => '/'.$rename['old']],
                ['to_path' => '/'.$rename['new'].'/']
            );

            $this->info("Renamed {$rename['old']} to {$rename['new']} and added a redirect.");
        }

        return self::SUCCESS;
    }

    private function stripYear(string $html): string
    {
        $html = str_replace(
            [
                'Adventskalendergeschichte 2007',
                'Adventskalendergeschichte 2006 - ',
                'Weihnachten 2006 ein',
                'Adventskalendergeschichte 2014',
            ],
            [
                'Adventskalendergeschichte',
                'Adventskalendergeschichte: ',
                'Weihnachten ein',
                'Adventskalendergeschichte',
            ],
            $html
        );

        return $html;
    }
}
