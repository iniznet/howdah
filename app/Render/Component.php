<?php

/**
 * The render unit. Typed props in, escaped HTML out; it never fetches data,
 * touches a global or fires a hook, and it returns its bytes so nesting
 * works by concatenation.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

interface Component
{
    public function render(): string;
}
