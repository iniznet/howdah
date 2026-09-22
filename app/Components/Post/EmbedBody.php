<?php

/**
 * The embed document's content: a thumbnail when the post has one, the
 * linked title, the excerpt and a link home — parity with core's
 * theme-compat/embed-content.php, which this theme replaces. The embed
 * document owns its own h1; the page shell renders none.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Features\Content\PostData;
use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class EmbedBody extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly PostData $post,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/embed-body.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $post = $this->post;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
