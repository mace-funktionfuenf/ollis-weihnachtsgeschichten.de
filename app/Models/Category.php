<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['parent_id', 'slug', 'name'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Mirrors the routing/export split: a root category sits at the flat
     * "/{slug}/" depth, one with a parent nests under it - see routes/web.php
     * and StaticSiteExporter for the same rule applied to exported paths.
     */
    public function url(): string
    {
        if ($this->parent) {
            return '/'.$this->parent->slug.'/'.$this->slug.'/';
        }

        return '/'.$this->slug.'/';
    }
}
