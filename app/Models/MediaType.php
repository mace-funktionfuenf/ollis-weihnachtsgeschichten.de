<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaType extends Model
{
    protected $fillable = ['slug', 'name'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function url(): string
    {
        return '/weihnachtsgeschichten/'.$this->slug.'/';
    }
}
