<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Mahout\Fields\Admin\FieldsUiProvider;
use Iniznet\Mahout\Fields\Contracts\OptionScreens as OptionScreensContract;
use Iniznet\Mahout\Fields\Hooks as FieldHooks;
use Iniznet\Mahout\Fields\OptionScreenLayout;

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
    public function testTheCompositionRootBindsTheDeclaredOptionScreens(): void
    {
        $screens = Bootstrap::services()->get(OptionScreensContract::class);

        self::assertFalse($screens->isEmpty(), 'the worked example declares one option screen.');
        self::assertCount(1, iterator_to_array($screens));
    }

    public function testTheDeclaredScreenNamesItsStructure(): void
    {
        $screen = iterator_to_array(Bootstrap::services()->get(OptionScreensContract::class))[0];

        self::assertTrue($screen->topLevel, 'the worked example registers its own menu, not a Settings submenu');
        self::assertSame(OptionScreenLayout::Sidebar, $screen->layout, 'the worked example reads as a document: the sidebar layout');
        self::assertCount(2, $screen->tabs, 'the worked example shows both section kinds, on the two tabs of one page');
        self::assertSame(['Options', 'Guide'], array_map(static fn ($tab) => $tab->label, $screen->tabs));
        self::assertSame(['display_options'], array_map(static fn ($group) => $group->id, $screen->fieldGroups()), 'the fields live inside a tab\'s section');
        self::assertArrayHasKey('Guide', array_flip(array_map(static fn ($tab) => $tab->label, $screen->tabs)));

        $guide = $screen->tabs[1]->sections[0];
        self::assertNull($guide->group, 'the guide tab carries no fields: a documentation page renders no form');
        self::assertFileExists($guide->markupPath ?? '', 'the content section\'s markup is part of the codebase');
    }

    public function testTheFieldsUiProviderAttachesTheDeclaredSettingsPage(): void
    {
        $services = Bootstrap::services();

        self::assertTrue($services->has(OptionScreensContract::class), 'the composition root binds the contract the option pages are derived from.');

        // Detach every callback from the hooks the surface owns, so an
        // attachment is attributed to this boot and to nothing else. Core's
        // own attachments, and the status screen's, are restored on tear_down.
        \remove_all_actions(FieldHooks::ADMIN_MENU);
        \remove_all_actions(FieldHooks::ADMIN_NOTICES);

        (new FieldsUiProvider())->boot($services);

        self::assertTrue(
            \has_action(FieldHooks::ADMIN_MENU),
            'the declared screen attaches its page; an undeclared one attaches nothing.',
        );
    }
}
