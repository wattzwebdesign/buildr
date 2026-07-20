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

    public function test_media_library_page_lists_searches_and_deletes(): void
    {
        Storage::fake('public');

        // seed two uploads via the editor flow
        $page = Page::create(['title' => 'T', 'slug' => 't']);
        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'image');
        $image = $page->nodes()->where('type', 'image')->first();
        $component->call('selectNode', $image->id)
            ->set('mediaTarget', 'settings.content.src')
            ->set('upload', UploadedFile::fake()->image('roof-hero.jpg'))
            ->set('mediaTarget', '')
            ->set('upload', UploadedFile::fake()->image('unused-photo.jpg'));

        $this->get('/buildr/media')->assertOk()->assertSee('roof-hero.jpg')->assertSee('unused-photo.jpg');

        // usage counts: first file is on the page, second is not
        $library = Livewire::test(\Buildr\Http\Livewire\MediaLibrary::class);
        $items = collect($library->viewData('items'))->keyBy('name');
        $this->assertSame(1, $items['roof-hero.jpg']['used']);
        $this->assertSame(0, $items['unused-photo.jpg']['used']);

        // search narrows the grid
        $library->set('search', 'unused')
            ->assertSee('unused-photo.jpg')->assertDontSee('roof-hero.jpg');

        // delete removes the record and the file
        $unused = Media::where('name', 'unused-photo.jpg')->first();
        $library->call('deleteMedia', $unused->id);
        $this->assertNull(Media::find($unused->id));
        Storage::disk('public')->assertMissing($unused->path);
    }

    public function test_editor_offers_the_media_library_picker(): void
    {
        Storage::fake('public');
        Media::create(['path' => 'buildr/pic.jpg', 'name' => 'pic.jpg', 'size' => 2048]);

        $page = Page::create(['title' => 'T', 'slug' => 't']);
        Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'image')
            ->assertSee('Library…')
            ->assertSee('pic.jpg');
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
