<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Support\Str;
use Redberry\PageBuilderPlugin\Abstracts\BaseBlock;
use Redberry\PageBuilderPlugin\Models\PageBuilderBlock;

class PageBlockSerializer
{
    /**
     * @return array<int, array{id: string, type: string, data: array<string, mixed>}>
     */
    public function serialize(Page|Post $page): array
    {
        return $page->pageBuilderBlocks
            ->map(function (PageBuilderBlock $block): array {
                $blockType = $block->block_type;
                $data = $block->data ?? [];

                if (is_subclass_of($blockType, BaseBlock::class)) {
                    $data = $blockType::formatForSingleView($data);
                }

                return [
                    'id' => (string) $block->id,
                    'type' => Str::afterLast($blockType, '\\'),
                    'data' => $data,
                ];
            })
            ->values()
            ->all();
    }
}
