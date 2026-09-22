<?php

/**
 * One term list: the taxonomy's terms as links. Empty renders nothing.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Features\Content\PostTerm;
use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class PostTermList extends MarkupComponent
{
    /**
     * @param list<PostTerm> $terms
     */
    public function __construct(
        ClassResolver $classes,
        private readonly array $terms,
        private readonly string $label,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/terms.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $terms = $this->terms;
        $label = $this->label;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
