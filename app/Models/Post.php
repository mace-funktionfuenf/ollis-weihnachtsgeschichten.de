<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\StaticExportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(StaticExportObserver::class)]
class Post extends Model
{
    protected $fillable = [
        'wp_post_id', 'slug', 'title', 'excerpt', 'body_html',
        'author_name', 'published_at', 'status', 'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function url(): string
    {
        return '/'.$this->slug.'/';
    }
}
