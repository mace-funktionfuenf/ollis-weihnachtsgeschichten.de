<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\StaticExportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(StaticExportObserver::class)]
class AdventDoor extends Model
{
    protected $fillable = ['day', 'title', 'story_html'];

    protected $casts = [
        'day' => 'integer',
    ];

    /**
     * Client-side gating owns the real "is this open yet" decision (see
     * resources/views/pages/advent-calendar.blade.php) - the static export
     * bakes every door's story into the page regardless of date, since
     * there's no per-request server logic once the page is cached. This is
     * only used server-side to label the door's unlock date in the markup.
     */
    public function unlocksOn(): string
    {
        return $this->day.'. Dezember';
    }
}
