<?php

/**
 * Appearance > Theme settings. The screen exists only when the theme
 * declares display options; each option is stored under its own wp_options
 * key, saved behind the screen's edit_theme_options capability and a verified
 * nonce, and sanitised by the option's declared shape -- never by a submitted
 * value's own claim about what it is.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Howdah\Exception\InvalidDisplayOption;
use Iniznet\Howdah\Features\Settings\DisplayOption;
use Iniznet\Howdah\Features\Settings\DisplayOptions;
use Iniznet\Howdah\Support\Capabilities;
use Iniznet\Howdah\Support\Request;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Level;

final readonly class ThemeSettingsScreen
{
    public const string PAGE_SLUG = 'howdah-theme-settings';

    private const string FORM_FIELD = 'howdah_settings';

    public function __construct(
        private DisplayOptions $options,
        private Diagnostics $diagnostics,
    ) {
    }

    /** The save action's nonce base; one screen, one action. */
    public static function nonceAction(): string
    {
        return 'howdah_theme_settings_save';
    }

    /**
     * The load-appearance_page_{slug} entry, attached by the provider after
     * it has registered the page. A denied capability stops here; a request
     * that did not submit the form stops at the guard field; a malformed
     * option shape records once and saves nothing.
     */
    public function handleSave(): void
    {
        if (!\current_user_can(Capabilities::EditThemeOptions->value)) {
            return;
        }

        if ('POST' !== Request::fromSuperglobals()->method
            || !Request::panel()->has(self::FORM_FIELD)
        ) {
            return;
        }

        \check_admin_referer(self::nonceAction());

        foreach ($this->options as $option) {
            try {
                \update_option($option->optionKey(), $this->sanitise($option));
            } catch (\Throwable $failure) {
                $this->diagnostics->log(
                    Level::Error,
                    'display option save refused',
                    ['condition' => $failure::class, 'option' => $option->id],
                );
            }
        }
    }

    /** The page body, one declared option per row. */
    public function render(): string
    {
        \ob_start();
        $options = $this->options;
        $values = [];
        $formField = self::FORM_FIELD;
        $boolean = DisplayOption::BOOLEAN;
        $choice = DisplayOption::CHOICE;

        foreach ($options as $option) {
            $values[$option->id] = \get_option($option->optionKey(), $option->default);
        }

        require __DIR__.'/markup/theme-settings.php';

        return (string) \ob_get_clean();
    }

    /**
     * The declared shape's sanitiser. A text option strips markup, a boolean
     * collapses to its checkbox state, and a choice is whitelisted against
     * its own set -- a submitted value outside the choices falls back to the
     * declaration's default, never to the submitted string.
     *
     * @throws InvalidDisplayOption when an option's declared type is unknown
     */
    private function sanitise(DisplayOption $option): string|bool
    {
        $submitted = Request::panel()->value(self::FORM_FIELD, $option->id);

        return match ($option->type) {
            DisplayOption::TEXT => \sanitize_text_field(\is_string($submitted) ? $submitted : ''),
            DisplayOption::BOOLEAN => \is_string($submitted),
            DisplayOption::CHOICE => \in_array($submitted, $option->choices, true)
                ? (string) $submitted
                : (string) $option->default,
            default => throw InvalidDisplayOption::forUnknownType($option->type),
        };
    }
}
