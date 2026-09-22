<?php

/**
 * The embed document's Surface: a thumbnail, a title, a short excerpt and a
 * link home — parity with core's theme-compat/embed-content.php, which this
 * theme replaces.
 *
 * The embed reads through the same repository as the singular Surface: the
 * mapper, the DTO and the query priming are shared, only the document
 * differs. The content feature binds that repository in slice 8b; until it
 * does, this Surface throws rather than render a substitute. The error
 * boundary turns the throw into the defined failure render.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Content\Surfaces;

use Iniznet\Howdah\Render\Component;
use Iniznet\Howdah\Render\Exception\SurfaceDataMissing;
use Iniznet\Howdah\Render\QueryContext;

final readonly class EmbedContent implements Component
{
    public function __construct(private QueryContext $ctx)
    {
    }

    public function render(): string
    {
        throw SurfaceDataMissing::forObject($this->ctx->objectId);
    }
}
