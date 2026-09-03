<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImageUploadOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_an_oversized_image_through_filament_gets_capped_to_max_width(): void
    {
        $this->actingAs(User::factory()->create());

        $post = Post::create(['slug' => 'p', 'title' => 'P', 'body_html' => '<p>x</p>', 'status' => 'publish']);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('data.featured_image', TemporaryUploadedFile::fake()->image('huge.jpg', 2400, 1800))
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $post->refresh()->featured_image;

        $this->assertNotNull($path);

        [$width] = getimagesize(Storage::disk('public')->path($path));

        $this->assertLessThanOrEqual(1200, $width);
    }
}
