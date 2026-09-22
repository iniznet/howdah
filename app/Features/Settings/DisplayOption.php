<?php

/**
 * One theme-owned display option: an id (which is also the wp_options key's
 * tail), a label, one of the three shapes the settings screen can render and
 * save, and the shape's default. The declaration is data; the screen is the
 * one renderer and the one sanitiser for all three.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Settings;

use Iniznet\Howdah\Exception\InvalidDisplayOption;

final readonly class DisplayOption
{
    public const string TEXT = 'text';
    public const string BOOLEAN = 'boolean';
    public const string CHOICE = 'choice';

    /**
     * @param list<string> $choices
     *
     * @throws InvalidDisplayOption when the declaration is not renderable
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $type,
        public string|bool $default,
        public array $choices = [],
    ) {
        if ('' === $id) {
            throw InvalidDisplayOption::forEmptyId();
        }

        if (self::CHOICE === $type && [] === $choices) {
            throw InvalidDisplayOption::forChoiceWithoutOptions($id);
        }

        if (self::CHOICE === $type && \is_string($default) && !\in_array($default, $choices, true)) {
            throw InvalidDisplayOption::forDefaultOutsideChoices($id, $default);
        }
    }

    /** The wp_options key this option is stored under. */
    public function optionKey(): string
    {
        return 'howdah_'.str_replace('-', '_', $this->id);
    }
}
