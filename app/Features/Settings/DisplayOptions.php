<?php

/**
 * The theme's declared display options, loaded once from
 * config/display-options.php. The settings screen exists only when this
 * collection is non-empty, and every option it renders and saves comes from
 * here and from nowhere else.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Settings;

/**
 * @implements \IteratorAggregate<int, DisplayOption>
 */
final readonly class DisplayOptions implements \IteratorAggregate, \Countable
{
    /** @param list<DisplayOption> $options */
    public function __construct(private array $options)
    {
    }

    public function isEmpty(): bool
    {
        return [] === $this->options;
    }

    public function count(): int
    {
        return count($this->options);
    }

    /** @return \ArrayIterator<int, DisplayOption> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->options);
    }
}
