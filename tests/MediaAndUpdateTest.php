<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Media;
use Buildr\Models\Page;
use Buildr\Support\UpdateCheck;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class MediaAndUpdateTest extends TestCase
{
    public function test_upload_stores_media_and_fills_the_target_field(): void
    {
        Storage::fake('public');

        $page = Page::create(['title' => 'T', 'slug' => 't']);
        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'image');

        $image = $page->nodes()->where('type', 'image')->first();

        $component->call('selectNode', $image->id)
            ->set('mediaTarget', 'settings.content.src')
            ->set('upload', UploadedFile::fake()->image('roof.jpg', 800, 600));

        $media = Media::first();
        $this->assertNotNull($media);
        Storage::disk('public')->assertExists($media->path);

        $this->assertStringContainsString('/storage/buildr/', $image->fresh()->setting('content', 'src'));
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $page = Page::create(['title' => 'T', 'slug' => 't']);

        Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'image')
            ->set('mediaTarget', 'settings.content.src')
            ->set('upload', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
            ->assertHasErrors('upload');

        $this->assertNull(Media::first());
    }

    public function test_update_check_compares_refs(): void
    {
        Http::fake(['api.github.com/*' => Http::response('abcdef1234567890', 200)]);

        cache()->forget('buildr.latest_ref');
        $this->assertTrue(UpdateCheck::available('1111111111'));

        cache()->forget('buildr.latest_ref');
        $this->assertFalse(UpdateCheck::available('abcdef1234567890'));
    }
}
