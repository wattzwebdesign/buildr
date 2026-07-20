<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Editor;
use Buildr\Models\Page;
use Buildr\Render\PageRenderer;
use Buildr\Support\Publisher;
use Livewire\Livewire;

class RevisionsTest extends TestCase
{
    private function publishedPage(): array
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home']);

        $component = Livewire::test(Editor::class, ['page' => $page])
            ->call('addContainer', 1)
            ->call('addElement', 'heading')
            ->set('settings.content.text', 'Version one');

        Publisher::publish($page->fresh());

        return [$page->fresh(), $component];
    }

    public function test_draft_edits_do_not_touch_the_live_page_until_publish(): void
    {
        [$page, $component] = $this->publishedPage();

        $heading = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $component->call('selectNode', $heading->id)
            ->set('settings.content.text', 'Version two');

        // public still renders the published tree
        $public = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringContainsString('Version one', $public);
        $this->assertStringNotContainsString('Version two', $public);

        // editor canvas shows the draft
        $editor = app(PageRenderer::class)->renderEditor($page->fresh());
        $this->assertStringContainsString('Version two', collect($editor['roots'])->pluck('html')->implode(''));

        // publish pushes the draft live
        Publisher::publish($page->fresh());
        $public = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringContainsString('Version two', $public);
    }

    public function test_each_publish_creates_a_revision_snapshot(): void
    {
        [$page] = $this->publishedPage();

        $this->assertSame(1, $page->revisions()->count());

        Publisher::publish($page->fresh());
        $this->assertSame(2, $page->fresh()->revisions()->count());

        $snapshot = $page->revisions()->latest('id')->first()->snapshot;
        $this->assertSame('container', $snapshot[0]['type']);
        $this->assertSame('heading', $snapshot[0]['children'][0]['type']);
    }

    public function test_discard_reverts_draft_to_published_state(): void
    {
        [$page, $component] = $this->publishedPage();

        $heading = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $component->call('selectNode', $heading->id)
            ->set('settings.content.text', 'Abandoned edit')
            ->call('discardDraft');

        $draftHeading = $page->fresh()->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $this->assertSame('Version one', $draftHeading->setting('content', 'text'));

        // clean after discard: updated_at aligned to published_at
        $fresh = $page->fresh();
        $this->assertFalse($fresh->updated_at->gt($fresh->published_at));
    }

    public function test_restore_revision_loads_snapshot_into_draft(): void
    {
        [$page, $component] = $this->publishedPage();
        $firstRevision = $page->revisions()->first();

        // change + publish a second version
        $heading = $page->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $component->call('selectNode', $heading->id)->set('settings.content.text', 'Version two');
        Publisher::publish($page->fresh());

        // restore v1 into the draft — live stays on v2
        $component->call('restoreRevision', $firstRevision->id);

        $draftHeading = $page->fresh()->nodes()->where('is_draft', true)->where('type', 'heading')->first();
        $this->assertSame('Version one', $draftHeading->setting('content', 'text'));

        $public = app(PageRenderer::class)->render($page->fresh())['html'];
        $this->assertStringContainsString('Version two', $public);
    }

    public function test_backgrounds_compile_for_page_and_container(): void
    {
        [$page, $component] = $this->publishedPage();

        // page-level background via Page Settings
        $component->call('openPage')
            ->set('pageForm.background.color', '#f7f9fa')
            ->set('pageForm.background.image', '/storage/buildr/bg.jpg')
            ->set('pageForm.background.position', 'top center')
            ->set('pageForm.background.attachment', 'fixed')
            ->call('savePage');

        $css = \Buildr\Render\PageCss::for($page->fresh());
        $this->assertStringContainsString('background-color:#f7f9fa', $css);
        $this->assertStringContainsString("background-image:url('/storage/buildr/bg.jpg')", $css);
        $this->assertStringContainsString('background-position:top center', $css);
        $this->assertStringContainsString('background-attachment:fixed', $css);
        $this->assertStringContainsString('background-size:cover', $css); // default

        // container background image with position settings
        $container = $page->nodes()->where('is_draft', true)->where('type', 'container')->first();
        $component->call('selectNode', $container->id)
            ->call('setTab', 'style')
            ->set('settings.style.bg_image', '/storage/buildr/hero.jpg')
            ->set('settings.style.bg_position', 'bottom right')
            ->set('settings.style.bg_size', 'contain');

        $compiled = $this->publishedRender($page)['css'];
        $this->assertStringContainsString("background-image:url('/storage/buildr/hero.jpg')", $compiled);
        $this->assertStringContainsString('background-position:bottom right', $compiled);
        $this->assertStringContainsString('background-size:contain', $compiled);
        $this->assertStringContainsString('background-repeat:no-repeat', $compiled); // default
    }

    public function test_page_settings_save_updates_slug_and_seo(): void
    {
        [$page, $component] = $this->publishedPage();

        Page::create(['title' => 'Other', 'slug' => 'about']);

        $component->call('openPage')
            ->set('pageForm.title', 'About Us')
            ->set('pageForm.slug', 'about')          // taken → uniquified
            ->set('pageForm.seo_title', 'About Us | Blue Heron')
            ->set('pageForm.seo_description', 'Learn about the crew.')
            ->assertSet('pageDirty', true)
            ->call('savePage')
            ->assertSet('pageDirty', false);

        $fresh = $page->fresh();
        $this->assertSame('About Us', $fresh->title);
        $this->assertSame('about-2', $fresh->slug);
        $this->assertSame('About Us | Blue Heron', $fresh->seo_title);
        $this->assertSame('Learn about the crew.', $fresh->seo_description);
    }
}
