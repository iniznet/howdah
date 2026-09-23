<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Mahout\Fields\Admin\FieldsUiProvider;
use Iniznet\Mahout\Fields\Contracts\OptionScreens as OptionScreensContract;
use Iniznet\Mahout\Fields\Hooks as FieldHooks;

/**
 * The option screens' composition: the starter theme is opinionless, the
 * composition root still binds the field package's OptionScreens contract,
 * and the package's provider — the only seam that registers a settings page
 * — attaches nothing for the empty declaration. The opt-in and the
 * no-declaration case are one code path, so an empty config and an absent
 * config leave the same menu untouched.
 */
final class OptionScreensTest extends \WP_UnitTestCase
{
    public function testTheCompositionRootBindsAnEmptyOptionScreensCollection(): void
    {
        $screens = Bootstrap::services()->get(OptionScreensContract::class);

        self::assertTrue($screens->isEmpty(), 'the starter theme declares no option screen.');
    }

    public function testTheFieldsUiProviderAttachesNoSettingsPageForAnEmptyDeclaration(): void
    {
        $services = Bootstrap::services();

        self::assertTrue($services->has(OptionScreensContract::class), 'the composition root binds the contract the option pages are derived from.');

        // Detach every callback from the hooks the surface owns, so an
        // attachment is attributed to this boot and to nothing else. Core's
        // own attachments, and the status screen's, are restored on tear_down.
        \remove_all_actions(FieldHooks::ADMIN_MENU);
        \remove_all_actions(FieldHooks::ADMIN_NOTICES);

        (new FieldsUiProvider())->boot($services);

        self::assertFalse(
            \has_action(FieldHooks::ADMIN_MENU),
            'no declaration, no attachment — the opt-in and the empty case are one path.',
        );
    }
}
