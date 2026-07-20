<?php

namespace Buildr\Support;

use Buildr\Models\Page;

/** Draft → live publishing with a restorable snapshot per publish. */
final class Publisher
{
    public const KEEP_REVISIONS = 25;

    public static function publish(Page $page): void
    {
        $snapshot = TreeSnapshot::capture($page, draft: true);

        $page->revisions()->create([
            'snapshot' => $snapshot,
            'label' => 'Published '.now()->format('M j, Y g:ia'),
        ]);

        // prune old snapshots
        $stale = $page->revisions()->orderByDesc('id')->skip(self::KEEP_REVISIONS)->take(100)->pluck('id');
        if ($stale->isNotEmpty()) {
            $page->revisions()->whereIn('id', $stale)->delete();
        }

        TreeSnapshot::restore($page, $snapshot, asDraft: false);

        $page->update(['published_at' => now()]);
        self::markClean($page);
    }

    /** Throw away draft changes: rebuild the draft from the published tree. */
    public static function discardDraft(Page $page): void
    {
        TreeSnapshot::restore($page, TreeSnapshot::capture($page, draft: false), asDraft: true);
        self::markClean($page);
    }

    /** Align updated_at with published_at so the dirty flag reads clean. */
    private static function markClean(Page $page): void
    {
        $page->timestamps = false;
        $page->forceFill(['updated_at' => $page->published_at ?? now()])->save();
        $page->timestamps = true;
    }
}
