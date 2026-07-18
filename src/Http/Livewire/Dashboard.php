<?php

namespace Buildr\Http\Livewire;

use Buildr\Models\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('buildr::admin.layout')]
class Dashboard extends Component
{
    public string $search = '';

    public string $newTitle = '';

    public function createPage(): void
    {
        $title = trim($this->newTitle);
        if ($title === '') {
            return;
        }

        $slug = Str::slug($title) ?: 'page';
        $base = $slug;
        for ($i = 2; Page::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        Page::create(['title' => $title, 'slug' => $slug]);
        $this->newTitle = '';
    }

    public function togglePublish(int $id): void
    {
        $page = Page::findOrFail($id);
        $page->update(['published_at' => $page->published_at ? null : now()]);
    }

    public function deletePage(int $id): void
    {
        Page::findOrFail($id)->delete();
    }

    public function render()
    {
        $pages = Page::withCount('nodes')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->orderBy('title')
            ->get();

        return view('buildr::livewire.dashboard', ['pages' => $pages])
            ->title('Buildr — Pages');
    }
}
