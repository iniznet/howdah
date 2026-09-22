<?php

/**
 * The singular post's meta line: the display date, the author with a link to
 * the author archive, then the category and tag lists. It renders typed
 * props and nothing else; each nested component renders its own escaped
 * bytes, and this markup adds one escape per output of its own.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Post;

use Iniznet\Howdah\Features\Content\PostData;
use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class PostMeta extends MarkupComponent
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
        return __DIR__.'/markup/meta.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $post = $this->post;
        $categories = new PostTermList($this->classes(), $post->categories, \__('Categories', 'howdah'));
        $tags = new PostTermList($this->classes(), $post->tags, \__('Tags', 'howdah'));
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
