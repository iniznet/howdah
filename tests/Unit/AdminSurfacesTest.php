<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Admin\ContentModelColumns;
use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\StatusScreen;
use Iniznet\Mahout\Fields\Admin\FieldMetabox;
use Iniznet\Mahout\Fields\Admin\OptionScreenManager;
use Iniznet\Mahout\Fields\Admin\WriteFailureNoticeRenderer;
use PHPUnit\Framework\TestCase;

/**
 * The admin surface inventory: every named surface in 16-admin-and-editor §2
 * resolves to a class that exists, whether the theme owns it or the field
 * package does, and this test is the one that fails when a surface is
 * removed. The no-list-table and no-quick-edit rules are the greps the spec
 * asks for, and they run here rather than in a shell.
 */
final class AdminSurfacesTest extends TestCase
{
    /** @return array<string, class-string> */
    private static function inventory(): array
    {
        return [
            'FieldPanel (metabox composition)' => FieldMetabox::class,
            'ContentModelColumns' => ContentModelColumns::class,
            'OptionScreen (derived settings page)' => OptionScreenManager::class,
            'StatusScreen' => StatusScreen::class,
            'FieldWriteFailedNotice' => WriteFailureNoticeRenderer::class,
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

    /**
     * The named surfaces are a published contract, so the gate reads the
     * committed reference and not the private planning corpus: `/docs/planning/`
     * is git-ignored by design, and a test that read it could never run in a
     * fork pull request — which is exactly where 19 §5 promises the same
     * `composer check` runs. The reference table and this inventory are the
     * two halves of one list, and a surface named in only one of them fails.
     */
    public function testTheSurfaceInventoryMatchesThePublishedReference(): void
    {
        $reference = file_get_contents(dirname(__DIR__, 2).'/docs/reference/admin-surfaces.md');

        self::assertIsString($reference, 'The admin surfaces reference is part of the published tree.');

        $named = array_map(
            static fn (string $surface): string => explode(' ', $surface)[0],
            array_keys(self::inventory()),
        );

        foreach ($named as $surface) {
            self::assertStringContainsString('| `'.$surface.'`', $reference, sprintf('The reference names %s; the inventory must keep up, and vice versa.', $surface));
        }

        // The other half: a row added to the document without a class behind it
        // is a surface that exists only in prose, which is law 5's refusal.
        preg_match_all('/^\| `([A-Za-z0-9_]+)`/m', $reference, $rows);

        foreach ($rows[1] as $surface) {
            self::assertContains($surface, $named, sprintf('%s is in the reference but has no entry in the inventory.', $surface));
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

    /**
     * The field panels are the theme's declaration and the package's screens.
     * A metabox registered here would be a second path to the same panel —
     * one that carries no save lifecycle and no post lock — and the reason
     * the wiring moved into `mahout-fields` was to leave exactly one. The
     * `FieldsUiProvider` registration in the composition root is the only
     * admin field surface the theme owns, and this gate keeps it that way.
     */
    public function testTheThemeRegistersNoMetaboxOrFieldRouteOfItsOwn(): void
    {
        foreach (self::themeCode() as $source) {
            self::assertDoesNotMatchRegularExpression('/\badd_meta_box\s*\(/', $source, 'The theme registers a metabox directly; the field package derives it from the declared panels.');
            self::assertDoesNotMatchRegularExpression("/register_rest_field\s*\(|'mahout_fields'/", $source, 'The theme binds a field to a REST route directly; the field package owns the value route and its reads.');
        }
    }

    /**
     * The swap is only alive because its provider registers after `DbProvider`
     * — the clause reads the cached index presence and the connection that
     * provider declares. A re-attachment in a theme provider would be a second
     * `posts_search` filter deciding the same query, so neither hook name may
     * appear in the theme at all.
     */
    public function testTheThemeAttachesNoSearchFilterOfItsOwn(): void
    {
        foreach (self::themeCode() as $source) {
            self::assertDoesNotMatchRegularExpression('/posts_search(_orderby)?/', $source, 'The theme names a core search filter; the indexed path is mahout-db\'s. Docblocks may say so in prose, but no hook string may live here.');
        }
    }

    /**
     * Every theme source, comments included. A gate over what the tree says:
     * a commented-out write path is still a write path somebody left behind.
     *
     * @return list<string>
     */
    private static function themeFiles(): array
    {
        return self::sources(false);
    }

    /**
     * Every theme source with its comments removed: a gate over the code the
     * theme runs, not over the prose that records what moved where. The
     * docblocks are where the contract is written down, and a gate that
     * refused the word would refuse the record of the move.
     *
     * @return list<string>
     */
    private static function themeCode(): array
    {
        return self::sources(true);
    }

    /** @return list<string> */
    private static function sources(bool $stripComments): array
    {
        $app = dirname(__DIR__, 2).'/app';
        $sources = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($app)) as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                $source = (string) file_get_contents($file->getPathname());

                $sources[] = $stripComments ? self::stripComments($source) : $source;
            }
        }

        return $sources;
    }

    /**
     * The source with its comments removed: a gate over the code a file runs,
     * not over the prose that describes what moved where.
     */
    private static function stripComments(string $source): string
    {
        $stripped = '';

        foreach (token_get_all($source) as $token) {
            if (\is_array($token)) {
                if (\in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $stripped .= $token[1];

                continue;
            }

            $stripped .= $token;
        }

        return $stripped;
    }
}
