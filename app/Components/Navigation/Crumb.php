<?php

/**
 * One breadcrumb. The trail's last crumb is the current page: it carries no
 * href, and the markup marks it aria-current.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Navigation;

final readonly class Crumb
{
    public function __construct(
        public string $label,
        public ?string $href = null,
    ) {
    }
}
