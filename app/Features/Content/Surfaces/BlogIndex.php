<?php

/**
 * The posts index — the site's front page when it shows posts, and the
 * posts page when a static front page is declared. One page of teasers,
 * newest first, with pagination from the peek. An empty index renders the
 * site's declared empty state.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Components\Message\Message;
use Iniznet\Howdah\Components\Post\Pagination;
use Iniznet\Howdah\Components\Post\PostCard;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\Document;
use Iniznet\Howdah\Render\QueryContext;
use Iniznet\Howdah\Render\Stack;
use Iniznet\Howdah\Support\ClassResolver;

final readonly class BlogIndex implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 8;

    public function __construct(
        private QueryContext $ctx,
        private ContentRepository $content,
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        $page = $this->ctx->listingPage();
        $list = $this->content->home($page);

        if ([] === $list->items) {
            return new Document(
                $this->classes,
                main: new Message($this->classes, heading: \__('Nothing has been published here yet.', 'howdah')),
            )->render();
        }

        $items = [];

        foreach ($list->items as $post) {
            $items[] = new PostCard($this->classes, $post);
        }

        $items[] = $this->pagination($list->hasMore, $page);

        return new Document($this->classes, main: new Stack($items))->render();
    }

    private function pagination(bool $hasMore, int $page): Pagination
    {
        $newer = $page > 1 ? \get_pagenum_link($page - 1) : null;
        $older = $hasMore ? \get_pagenum_link($page + 1) : null;

        return new Pagination($this->classes, $newer, $older);
    }
}
