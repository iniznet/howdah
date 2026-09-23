<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Features\Fields\OptionScreens;
use Iniznet\Mahout\Fields\FieldGroup;
use Iniznet\Mahout\Fields\ObjectContext;
use Iniznet\Mahout\Fields\OptionScreen;
use Iniznet\Mahout\Fields\StorageTarget;
use Iniznet\Mahout\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * The option screens' declaration: the starter config is the declared empty
 * state, and the collection the composition root binds answers to the field
 * package's OptionScreens contract — the one key the settings pages are
 * derived from, and the one whose emptiness attaches nothing.
 */
final class OptionScreensTest extends TestCase
{
    public function testTheStarterConfigIsTheDeclaredEmptyState(): void
    {
        $screens = require dirname(__DIR__, 2).'/config/display-options.php';

        self::assertIsArray($screens);
        self::assertSame([], $screens);
    }

    public function testTheCollectionOfNoDeclarationIsEmpty(): void
    {
        self::assertTrue((new OptionScreens([]))->isEmpty());
    }

    public function testTheCollectionCarriesItsDeclaredScreens(): void
    {
        $group = new FieldGroup('fixture_group', ObjectContext::Option, [
            new TextField('fixture_text', StorageTarget::Meta),
        ]);
        $screen = new OptionScreen('fixture_screen', 'Fixture options', 'Fixture fields', $group, 'manage_options');
        $screens = new OptionScreens([$screen]);

        self::assertFalse($screens->isEmpty());
        self::assertSame([$screen], iterator_to_array($screens));
    }
}
