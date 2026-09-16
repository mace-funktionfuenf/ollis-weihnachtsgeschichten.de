<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Console\Command;

/**
 * Per the 2026-09-16 client review: the "Adventskalender auf Instagram:
 * AdventsABCFee" post is old, its outbound links are dead, and it should
 * come down. Redirects the URL to /adventskalendergeschichten/ (the new
 * interactive door calendar) rather than leaving a bare 404, since that's
 * the closest thing this site now has to what a reader following that link
 * was looking for.
 */
class RemoveInstagramAdventPost extends Command
{
    protected $signature = 'content:remove-instagram-advent-post';

    protected $description = 'Delete the outdated Instagram Advent calendar post and redirect its URL';

    public function handle(): int
    {
        $post = Post::where('slug', 'adventskalender-auf-instagram-adventsabcfee')->first();

        if (! $post) {
            $this->info('Instagram Advent post already removed, skipping.');

            return self::SUCCESS;
        }

        $post->delete();

        Redirect::updateOrCreate(
            ['from_path' => '/adventskalender-auf-instagram-adventsabcfee'],
            ['to_path' => '/adventskalendergeschichten/']
        );

        $this->info('Deleted the Instagram Advent post and redirected its URL.');

        return self::SUCCESS;
    }
}
