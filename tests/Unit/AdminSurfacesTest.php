<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Admin\ContentModelColumns;
use Iniznet\Howdah\Admin\FieldWriteFailedNotice;
use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\StatusScreen;
use Iniznet\Howdah\Admin\ThemeSettingsScreen;
use Iniznet\Mahout\Fields\Admin\FieldMetabox;
use PHPUnit\Framework\TestCase;

/**
 * The admin surface inventory: every named surface in 16-admin-and-editor §2
 * resolves to a class the theme owns, and this test is the one that fails
 * when a surface is removed. The no-list-table and no-quick-edit rules are
 * the greps the spec asks for, and they run here rather than in a shell.
 */
final class AdminSurfacesTest extends TestCase
{
    /** @return array<string, class-string> */
    private static function inventory(): array
    {
        return [
            'FieldPanel (metabox composition)' => FieldMetabox::class,
            'ContentModelColumns' => ContentModelColumns::class,
            'ThemeSettingsScreen' => ThemeSettingsScreen::class,
            'StatusScreen' => StatusScreen::class,
            'FieldWriteFailedNotice' => FieldWriteFailedNotice::class,
            'MigrationRequiredNotice' => MigrationRequiredNotice::class,
            'MigrationFailedNotice' => MigrationFailedNotice::class,
        ];
    }

    public function testEveryNamedSurfaceHasItsClass(): void
    {
        foreach (self::inventory() as $surface => $class) {
            self::assertTrue(class_exists($class), sprintf('%s is missing.', $surface));
        }
    }

    public function testTheSurfaceInventoryMatchesTheSpecsNamedList(): void
    {
        $spec = file_get_contents(dirname(__DIR__, 2).'/docs/planning/16-admin-and-editor.md');

        self::assertIsString($spec);

        foreach (['FieldPanel', 'ContentModelColumns', 'ThemeSettingsScreen', 'StatusScreen', 'FieldWriteFailedNotice', 'MigrationRequiredNotice', 'MigrationFailedNotice'] as $surface) {
            self::assertStringContainsString('| `'.$surface.'`', $spec, sprintf('The spec names %s; the inventory test must keep up.', $surface));
        }
    }

    public function testNoListTableSubclassExists(): void
    {
        foreach (self::themeFiles() as $source) {
            self::assertDoesNotMatchRegularExpression('/extends\s+WP_List_Table/', $source, 'A list table subclass exists; the spec forbids it.');
        }
    }

    public function testNoQuickEditHandlerWritesFields(): void
    {
        foreach (self::themeFiles() as $source) {
            self::assertDoesNotMatchRegularExpression('/bulk_edit_posts|inline-save|quick_edit/', $source, 'A quick-edit write path exists; the spec forbids it.');
        }
    }

    /** @return list<string> */
    private static function themeFiles(): array
    {
        $app = dirname(__DIR__, 2).'/app';
        $sources = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($app)) as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $sources[] = (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }
}
