<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\StaticExportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(StaticExportObserver::class)]
class Shop extends Model
{
    protected $fillable = ['wp_post_id', 'slug', 'title', 'widget_title', 'widget_content'];

    public function url(): string
    {
        return '/shop/'.$this->slug.'/';
    }
}
