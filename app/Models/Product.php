<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\StaticExportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(StaticExportObserver::class)]
class Product extends Model
{
    protected $fillable = [
        'wp_post_id', 'slug', 'title', 'body_html', 'asin', 'ean', 'article_number',
        'price', 'price_old', 'currency', 'portal', 'affiliate_link',
        'rating', 'rating_count', 'available', 'image_path', 'published_at',
        'meta_description',
    ];

    protected $casts = [
        'available' => 'boolean',
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'price_old' => 'decimal:2',
    ];

    public function audiences(): BelongsToMany
    {
        return $this->belongsToMany(ProductAudience::class);
    }

    public function giftCategories(): BelongsToMany
    {
        return $this->belongsToMany(GiftCategory::class);
    }

    public function mediaTypes(): BelongsToMany
    {
        return $this->belongsToMany(MediaType::class);
    }

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_product_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function url(): string
    {
        return '/produkt/'.$this->slug.'/';
    }
}
