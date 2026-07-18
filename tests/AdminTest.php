<?php

namespace Buildr\Tests;

use Buildr\Http\Livewire\Dashboard;
use Buildr\Models\Page;
use Livewire\Livewire;

class AdminTest extends TestCase
{
    public function test_pages_dashboard_renders(): void
    {
        Page::create(['title' => 'Home', 'slug' => 'home', 'published_at' => now()]);

        $this->get('/buildr/pages')
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Published');
    }

    public function test_create_publish_and_delete_page(): void
    {
        Livewire::test(Dashboard::class)
            ->set('newTitle', 'About Us')
            ->call('createPage');

        $page = Page::where('slug', 'about-us')->firstOrFail();
        $this->assertFalse($page->isPublished());

        Livewire::test(Dashboard::class)->call('togglePublish', $page->id);
        $this->assertTrue($page->fresh()->isPublished());

        Livewire::test(Dashboard::class)->call('deletePage', $page->id);
        $this->assertNull(Page::find($page->id));
    }

    public function test_admin_css_is_served(): void
    {
        $this->get('/buildr/assets/admin.css')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }
}
