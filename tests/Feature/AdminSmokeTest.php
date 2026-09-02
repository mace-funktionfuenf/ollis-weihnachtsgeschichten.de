<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\GiftCategoryResource;
use App\Filament\Resources\MediaTypeResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PostResource;
use App\Filament\Resources\ProductAudienceResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\RedirectResource;
use App\Filament\Resources\ShopResource;
use App\Filament\Resources\TagResource;
use App\Models\Category;
use App\Models\GiftCategory;
use App\Models\MediaType;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductAudience;
use App\Models\Redirect;
use App\Models\Shop;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<array{class-string}> */
    public static function resources(): array
    {
        return [
            [PostResource::class],
            [PageResource::class],
            [ProductResource::class],
            [ShopResource::class],
            [CategoryResource::class],
            [TagResource::class],
            [ProductAudienceResource::class],
            [GiftCategoryResource::class],
            [MediaTypeResource::class],
            [RedirectResource::class],
        ];
    }

    #[DataProvider('resources')]
    public function test_resource_list_and_create_pages_render(string $resource): void
    {
        $this->actingAs(User::factory()->create());

        $category = Category::create(['slug' => 'test-kategorie', 'name' => 'Test']);
        $tag = Tag::create(['slug' => 'test-tag', 'name' => 'Test']);
        $audience = ProductAudience::create(['slug' => 'test-fuer', 'name' => 'Test']);
        $giftCategory = GiftCategory::create(['slug' => 'test-geschenk', 'name' => 'Test']);
        $mediaType = MediaType::create(['slug' => 'test-medium', 'name' => 'Test']);

        $post = Post::create([
            'slug' => 'test-post', 'title' => 'Test Post', 'body_html' => '<p>Hi</p>', 'status' => 'publish',
        ]);
        $post->categories()->attach($category);
        $post->tags()->attach($tag);

        Page::create(['slug' => 'test-seite', 'title' => 'Test Seite', 'body_html' => '<p>Hi</p>']);

        $product = Product::create([
            'slug' => 'test-produkt', 'title' => 'Test Produkt', 'body_html' => '<p>Hi</p>',
        ]);
        $product->audiences()->attach($audience);
        $product->giftCategories()->attach($giftCategory);
        $product->mediaTypes()->attach($mediaType);

        Shop::create(['slug' => 'test-shop', 'title' => 'Test Shop']);
        Redirect::create(['from_path' => '/alt', 'to_path' => '/neu']);

        $listPageClass = $resource::getUrl('index');
        $this->get($listPageClass)->assertSuccessful();

        $this->get($resource::getUrl('create'))->assertSuccessful();
    }
}
