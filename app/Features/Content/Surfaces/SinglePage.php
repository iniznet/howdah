<?php

/**
 * The page Surface: the page's body inside the one document shell. A static
 * front page resolves here too, through the Front arm — the same page, the
 * same bytes, one fragment key.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Mahout\Content\PostData;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\Exception\SurfaceDataMissing;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Render\Stack;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Post\Pagination;
use Iniznet\Mahout\Ui\Components\Post\PostBody;

final readonly class SinglePage implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 6;

    public function __construct(
        private QueryContext $ctx,
        private ContentRepository $content,
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        $post = $this->content->page($this->objectId(), $this->ctx->contentPage());

        if (null === $post) {
            throw SurfaceDataMissing::forObject($this->ctx->objectId);
        }

        return new Document(
            $this->classes,
            main: new Stack([
                new PostBody($this->classes, $post->content),
                $this->postPages($post),
            ]),
        )->render();
    }

    /** The queried object's id; a singular request without one cannot render. */
    private function objectId(): int
    {
        $id = $this->ctx->objectId;

        if (null === $id || $id < 1) {
            throw SurfaceDataMissing::forObject($id);
        }

        return $id;
    }

    /** The page's own content pagination, from the peeked page count. */
    private function postPages(PostData $post): Pagination
    {
        $newer = $post->page > 1
            ? \add_query_arg('page', (string) ($post->page - 1), $post->permalink)
            : null;
        $older = $post->page < $post->pageCount
            ? \add_query_arg('page', (string) ($post->page + 1), $post->permalink)
            : null;

        return new Pagination($this->classes, $newer, $older);
    }
}
