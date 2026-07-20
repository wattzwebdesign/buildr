<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildr_nodes', function (Blueprint $table) {
            $table->boolean('is_draft')->default(true)->index();
        });

        // Existing trees become the draft; published pages get a published
        // copy of their current tree so live output is uninterrupted.
        $copy = function ($pageId, $sourceParentId, $newParentId) use (&$copy) {
            $children = DB::table('buildr_nodes')
                ->where('page_id', $pageId)
                ->where('is_draft', true)
                ->when($sourceParentId === null,
                    fn ($q) => $q->whereNull('parent_id'),
                    fn ($q) => $q->where('parent_id', $sourceParentId))
                ->orderBy('sort')->get();

            foreach ($children as $node) {
                $newId = DB::table('buildr_nodes')->insertGetId([
                    'page_id' => $node->page_id,
                    'parent_id' => $newParentId,
                    'type' => $node->type,
                    'sort' => $node->sort,
                    'data' => $node->data,
                    'visible' => $node->visible,
                    'is_draft' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $copy($pageId, $node->id, $newId);
            }
        };

        foreach (DB::table('buildr_pages')->whereNotNull('published_at')->pluck('id') as $pageId) {
            $copy($pageId, null, null);
        }
    }

    public function down(): void
    {
        DB::table('buildr_nodes')->where('is_draft', false)->delete();
        Schema::table('buildr_nodes', function (Blueprint $table) {
            $table->dropColumn('is_draft');
        });
    }
};
