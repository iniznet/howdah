<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

/**
 * The primary navigation's markup, resolved from the menu assigned to the
 * theme's declared location. Core's own walker renders the list — the label,
 * the href and the aria-current marking are core's — so the resolver is a
 * single call with the theme's declared classes and no parse-back of menu
 * item objects into value types.
 *
 * A location with no menu assigned is the declared empty state: the header
 * renders without a navigation and nothing falls back to an invented menu.
 */
final readonly class PrimaryNav
{
    public const string LOCATION = 'howdah-primary';

    private function __construct()
    {
    }

    /** The already-rendered navigation, escaped by core's walker. */
    public static function primary(): string
    {
        $markup = \wp_nav_menu([
            'theme_location' => self::LOCATION,
            'container' => false,
            'menu_class' => 'nav-list',
            'depth' => 1,
            'echo' => false,
            'fallback_cb' => '__return_empty_string',
        ]);

        return \is_string($markup) ? $markup : '';
    }
}
