<?php

namespace Buildr\Http\Livewire;

use Buildr\Models\Media;
use Buildr\Models\Page;
use Buildr\Models\PageNode;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('buildr::admin.layout')]
class MediaLibrary extends Component
{
    use WithFileUploads;

    public string $search = '';

    public array $uploads = [];

    public function updatedUploads(): void
    {
        $this->validate(['uploads.*' => 'image|max:8192']);

        $disk = config('buildr.media_disk', 'public');
        foreach ($this->uploads as $file) {
            Media::create([
                'path' => $file->store('buildr', $disk),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->uploads = [];
    }

    public function deleteMedia(int $id): void
    {
        $media = Media::findOrFail($id);
        Storage::disk(config('buildr.media_disk', 'public'))->delete($media->path);
        $media->delete();
    }

    /**
     * One searchable JSON haystack per page (draft + published node data
     * plus page settings) so usage counts cost two queries total, not 2×N.
     */
    private function pageHaystacks(): array
    {
        $hay = [];
        foreach (PageNode::query()->get(['page_id', 'data']) as $node) {
            $hay[$node->page_id] = ($hay[$node->page_id] ?? '').json_encode($node->data);
        }
        foreach (Page::query()->get(['id', 'settings']) as $page) {
            $hay[$page->id] = ($hay[$page->id] ?? '').json_encode($page->settings);
        }

        return $hay;
    }

    public function render()
    {
        $haystacks = $this->pageHaystacks();

        $items = Media::latest()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->get()
            ->map(function (Media $m) use ($haystacks) {
                $url = $m->url();
                // node data is stored as JSON, where "/" is escaped as "\/"
                $needle = trim(json_encode($url), '"');

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'url' => $url,
                    'size' => $m->size >= 1048576
                        ? round($m->size / 1048576, 1).' MB'
                        : max(1, (int) round($m->size / 1024)).' KB',
                    'date' => $m->created_at->format('M j, Y'),
                    'used' => collect($haystacks)->filter(fn ($h) => str_contains($h, $needle))->count(),
                ];
            })
            ->all();

        return view('buildr::livewire.media', [
            'items' => $items,
            'total' => Media::count(),
            'updateAvailable' => \Buildr\Support\UpdateCheck::available(),
        ])->title('Buildr — Media');
    }
}
