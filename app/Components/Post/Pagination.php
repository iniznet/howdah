<?php

/**
 * The listing's or the singular post's pagination: one newer link, one
 * older link, resolved by the Surface. A page with neither renders nothing.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Pagination extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly ?string $newerUrl,
        private readonly ?string $olderUrl,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/pagination.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $newer = $this->newerUrl;
        $older = $this->olderUrl;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
