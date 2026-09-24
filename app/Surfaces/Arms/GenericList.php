<?php

/**
 * The dispatch table's default arm: an unmapped request kind renders the
 * site's declared empty state inside the document shell. It is a defined
 * render, never a fallback that pretends: 8b's feature arms replace it for
 * every kind a feature owns, and this arm's reason states that the request
 * was unmapped.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Surfaces\Arms;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Message\Message;

final readonly class GenericList implements Component
{
    /** The per-request query ceiling this Surface is held to (reader budget). */
    public const int QUERY_CEILING = 6;

    public function __construct(
        private ClassResolver $classes,
        private readonly ?SiteChrome $chrome = null,
    ) {
    }

    public function render(): string
    {
        return new Document(
            $this->classes,
            main: new Message(
                $this->classes,
                heading: \__('Nothing has been published here yet.', 'howdah'),
            ),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }
}
