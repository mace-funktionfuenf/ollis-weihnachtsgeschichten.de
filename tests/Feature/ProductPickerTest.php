<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_relationship_saves(): void
    {
        $this->actingAs(User::factory()->create());

        $post = Post::create(['slug' => 'p', 'title' => 'P', 'body_html' => '<p>x</p>', 'status' => 'publish']);
        $product = Product::create(['slug' => 'prod', 'title' => 'Prod']);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->fillForm(['products' => [$product->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();
        $this->assertEquals(['Prod'], $post->products()->pluck('title')->all());
    }
}
