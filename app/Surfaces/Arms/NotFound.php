<?php

/**
 * The not-found render. A 404 is a statement about the current content
 * graph, so the arm is Uncacheable and the render is the site's defined
 * not-found page.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Surfaces\Arms;

use Iniznet\Howdah\Components\SiteChrome;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\Document;
use Iniznet\Mahout\Render\Stack;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Actions\Link;
use Iniznet\Mahout\Ui\Components\Message\Message;

final readonly class NotFound implements Component
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
            main: new Stack([
                new Message(
                    $this->classes,
                    heading: \__('Nothing was found at this address.', 'howdah'),
                    detail: \__('The page may have moved, or the address is wrong.', 'howdah'),
                ),
                new Link($this->classes, (string) \home_url('/'), \__('Back to the front page', 'howdah')),
            ]),
            header: $this->chrome?->header(),
            footer: $this->chrome?->footer(),
        )->render();
    }
}
