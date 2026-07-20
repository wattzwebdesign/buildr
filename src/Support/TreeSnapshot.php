<?php

namespace Buildr\Support;

use Buildr\Models\Page;

/** Serialize a page's node tree to a nested array, and rebuild it back. */
final class TreeSnapshot
{
    public static function capture(Page $page, bool $draft = true): array
    {
        $walk = function (?int $parentId) use (&$walk, $page, $draft): array {
            return $page->nodes()
                ->where('is_draft', $draft)
                ->when($parentId === null,
                    fn ($q) => $q->whereNull('parent_id'),
                    fn ($q) => $q->where('parent_id', $parentId))
                ->orderBy('sort')->get()
                ->map(fn ($node) => [
                    'type' => $node->type,
                    'sort' => $node->sort,
                    'visible' => $node->visible,
                    'data' => $node->data,
                    'children' => $walk($node->id),
                ])->all();
        };

        return $walk(null);
    }

    /** Replace the page's draft or published tree with the snapshot. */
    public static function restore(Page $page, array $tree, bool $asDraft = true): void
    {
        // delete existing set (children first via recursion-safe loop)
        $existing = $page->nodes()->where('is_draft', $asDraft)->orderByDesc('id')->get();
        foreach ($existing as $node) {
            $node->delete();
        }

        $insert = function (array $nodes, ?int $parentId) use (&$insert, $page, $asDraft): void {
            foreach ($nodes as $node) {
                $created = $page->nodes()->create([
                    'parent_id' => $parentId,
                    'type' => $node['type'],
                    'sort' => $node['sort'] ?? 0,
                    'visible' => $node['visible'] ?? true,
                    'data' => $node['data'] ?? [],
                    'is_draft' => $asDraft,
                ]);
                $insert($node['children'] ?? [], $created->id);
            }
        };

        $insert($tree, null);
    }
}
