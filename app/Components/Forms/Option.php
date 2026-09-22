<?php

/**
 * One select option.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Forms;

final readonly class Option
{
    public function __construct(
        public string $value,
        public string $label,
        public bool $selected = false,
    ) {
    }
}
