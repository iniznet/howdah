<?php

/**
 * The posts index — the site's front page when it shows posts, and the
 * posts page when a static front page is declared. One page of teasers,
 * newest first, with pagination from the peek. An empty index renders the
 * site's declared empty state.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\Stack;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Layout\Grid;
use Iniznet\Mahout\Ui\Components\Message\Message;
use Iniznet\Mahout\Ui\Components\Post\Pagination;
use Iniznet\Mahout\Ui\Components\Post\PostCard;
use Iniznet\Mahout\Ui\Components\Typography\Heading;
use Iniznet\Mahout\Ui\Components\Typography\HeadingLevel;

final readonly class BlogIndex implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 8;

    public function __construct(
        private QueryContext $ctx,
        private ContentRepository $content,
        private ClassResolver $classes,
        private readonly ?SiteChrome $chrome = null,
    ) {
    }

    public function render(): string
    {
        $page = $this->ctx->listingPage();
        $list = $this->content->home($page);

        if ([] === $list->items) {
            return new Document(
                $this->classes,
                main: new Stack([
                    new Heading($this->classes, HeadingLevel::One, \__('Latest posts', 'howdah')),
                    new Message($this->classes, heading: \__('Nothing has been published here yet.', 'howdah')),
                ]),
                header: $this->chrome?->header(),
                footer: $this->chrome?->footer(),
            )->render();
        }

        $cards = [];

        foreach ($list->items as $post) {
            $cards[] = new PostCard($this->classes, $post)->render();
        }

        return new Document(
            $this->classes,
            main: new Stack([
                new Heading($this->classes, HeadingLevel::One, \__('Latest posts', 'howdah')),
                new Grid($this->classes, $cards),
                $this->pagination($list->hasMore, $page),
            ]),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }

    private function pagination(bool $hasMore, int $page): Pagination
    {
        $newer = $page > 1 ? \get_pagenum_link($page - 1) : null;
        $older = $hasMore ? \get_pagenum_link($page + 1) : null;

        return new Pagination($this->classes, $newer, $older);
    }
}
