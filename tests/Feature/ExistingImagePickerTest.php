<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ExistingImagePickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_an_existing_image_does_not_crash_and_saves_a_plain_path(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::fake('public');
        Storage::disk('public')->put('posts/reused.jpg', 'fake-image-bytes');

        $post = Post::create(['slug' => 'p', 'title' => 'P', 'body_html' => '<p>x</p>', 'status' => 'publish']);

        // ->set() on a live field triggers the real afterStateUpdated lifecycle,
        // unlike fillForm() - this is what actually reproduces the bug, since
        // fillForm() writes form data directly without going through it.
        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->set('data.existing_featured_image', 'posts/reused.jpg')
            ->assertHasNoErrors()
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('posts/reused.jpg', $post->refresh()->featured_image);
    }
}
