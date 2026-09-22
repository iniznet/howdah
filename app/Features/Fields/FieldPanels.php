<?php

/**
 * The declared field panels, loaded once from config/fields.php. Every admin
 * surface the field layer needs -- metaboxes, the save entry, the REST route
 * -- is derived from this collection and from nothing else, so a panel that
 * is not declared here does not exist anywhere.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Fields;

/**
 * @implements \IteratorAggregate<int, FieldPanel>
 */
final readonly class FieldPanels implements \IteratorAggregate, \Countable
{
    /** @param list<FieldPanel> $panels */
    public function __construct(private readonly array $panels)
    {
    }

    public function isEmpty(): bool
    {
        return [] === $this->panels;
    }

    public function count(): int
    {
        return count($this->panels);
    }

    /** @return list<FieldPanel> */
    public function forPostType(string $postType): array
    {
        return array_values(array_filter(
            $this->panels,
            static fn (FieldPanel $panel): bool => $panel->postType === $postType,
        ));
    }

    /** @return list<\Iniznet\Mahout\Fields\FieldGroup> */
    public function groups(): array
    {
        return array_map(
            static fn (FieldPanel $panel): \Iniznet\Mahout\Fields\FieldGroup => $panel->group,
            $this->panels,
        );
    }

    /** @return \ArrayIterator<int, FieldPanel> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->panels);
    }
}
