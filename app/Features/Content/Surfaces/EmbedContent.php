<?php

/**
 * The embed document's Surface: a thumbnail, a title, a short excerpt and a
 * link home — parity with core's theme-compat/embed-content.php, which this
 * theme replaces.
 *
 * The embed reads through the same repository as the singular Surface: the
 * mapper, the DTO and the query priming are shared, only the document
 * differs. The embed document renders for one parent request and is never
 * stored, so the arm is Uncacheable with that reason.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Features\Content\ContentRepository;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\EmbedDocument;
use Iniznet\Mahout\Render\Exception\SurfaceDataMissing;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Post\EmbedBody;

final readonly class EmbedContent implements Component
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
        $post = $this->content->post($this->objectId(), $this->ctx->contentPage());

        if (null === $post) {
            throw SurfaceDataMissing::forObject($this->ctx->objectId);
        }

        return new EmbedDocument($this->classes, new EmbedBody($this->classes, $post))->render();
    }

    /** The queried object's id; an embed without one cannot render. */
    private function objectId(): int
    {
        $id = $this->ctx->objectId;

        if (null === $id || $id < 1) {
            throw SurfaceDataMissing::forObject($id);
        }

        return $id;
    }
}
