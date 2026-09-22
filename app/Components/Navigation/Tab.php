<?php

/**
 * One tab.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

final readonly class Tab
{
    public function __construct(
        public string $id,
        public string $label,
        public bool $selected = false,
    ) {
    }
}
