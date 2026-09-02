<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\StaticExportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(StaticExportObserver::class)]
class Page extends Model
{
    protected $fillable = ['wp_post_id', 'slug', 'title', 'body_html', 'meta_description'];

    public function url(): string
    {
        return $this->slug === 'startseite' ? '/' : '/'.$this->slug.'/';
    }
}
