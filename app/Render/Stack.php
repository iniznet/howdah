<?php

/**
 * Sequence composition: the Surface's main slot is one Component, so a page
 * of several components nests them in a stack. Every child renders its own
 * escaped bytes; the stack adds nothing.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

final readonly class Stack implements Component
{
    /** @param list<Component> $items */
    public function __construct(
        private readonly array $items = [],
    ) {
    }

    public function render(): string
    {
        $html = '';

        foreach ($this->items as $item) {
            $html .= $item->render();
        }

        return $html;
    }
}
