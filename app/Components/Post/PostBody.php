<?php

/**
 * The singular body. Post content is HTML by design: the_content pipeline
 * already sanitised it (kses, blocks, shortcodes), so escaping here would
 * corrupt the markup every block author wrote. This component is the one
 * sanctioned unescaped output in the theme, and the mapper is the boundary
 * that declares the taint escape.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class PostBody extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $content,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/body.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $content = $this->content;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
