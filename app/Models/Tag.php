<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['slug', 'name'];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
