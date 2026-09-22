<?php

/**
 * The embed shell: wp_head and wp_footer fire here and nowhere else in the
 * embed document, and no SiteHeader, no SiteFooter and no navigation render.
 * Plugin interop requires the two hooks; the embed contract requires the
 * omission. Core's theme-compat embed reaches the same shape through
 * get_header('embed') and get_footer('embed'); this theme reaches it without
 * the hierarchy.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Support\ClassResolver;

final readonly class EmbedDocument implements Component
{
    public function __construct(
        private ClassResolver $classes,
        private Component $content,
    ) {
    }

    public function render(): string
    {
        \ob_start();
        $c = $this->classes;
        $content = $this->content;
        require __DIR__.'/markup/embed-shell.php';

        return (string) \ob_get_clean();
    }
}
