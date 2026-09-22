<?php

/**
 * One listing card: a linked title, the published date, the excerpt. It
 * renders typed props and nothing else — no fetch, no global, no hook.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Features\Content\PostData;
use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class PostCard extends MarkupComponent
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
        return __DIR__.'/markup/card.php';
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
