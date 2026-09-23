<?php

/**
 * The not-found render. A 404 is a statement about the current content
 * graph, so the arm is Uncacheable and the render is the site's defined
 * not-found page.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Surfaces\Arms;

use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\QueryContext;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Message\Message;

final readonly class NotFound implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 6;

    public function __construct(
        private QueryContext $ctx,
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        return new Document(
            $this->classes,
            main: new Message(
                $this->classes,
                heading: \__('Nothing was found at this address.', 'howdah'),
                detail: $this->ctx->site->name,
            ),
        )->render();
    }
}
