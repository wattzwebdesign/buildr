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
        $this->validate(
            ['newTitle' => 'required|string|max:255'],
            ['newTitle.required' => 'Give the page a title first.'],
        );

        $title = trim($this->newTitle);
        if ($title === '') {
            $this->addError('newTitle', 'Give the page a title first.');

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

        if ($page->published_at) {
            $page->update(['published_at' => null]);
        } else {
            \Buildr\Support\Publisher::publish($page);
        }
    }

    public function deletePage(int $id): void
    {
        Page::findOrFail($id)->delete();
    }

    public function render()
    {
        $pages = Page::withCount(['nodes' => fn ($q) => $q->where('is_draft', true)])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->orderBy('title')
            ->get();

        return view('buildr::livewire.dashboard', [
            'pages' => $pages,
            'updateAvailable' => \Buildr\Support\UpdateCheck::available(),
        ])->title('Buildr — Pages');
    }
}
