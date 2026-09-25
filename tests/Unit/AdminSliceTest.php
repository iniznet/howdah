<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Admin\MigrationFailedNotice;
use Iniznet\Howdah\Admin\MigrationRequiredNotice;
use Iniznet\Howdah\Admin\MigrationSnapshot;
use Iniznet\Howdah\Exception\InvalidFieldDeclaration;
use Iniznet\Howdah\Features\Fields\FieldPanels;
use Iniznet\Howdah\Support\PanelRequest;
use Iniznet\Mahout\Db\MigrationStatus;
use Iniznet\Mahout\Db\SchemaVersion;
use Iniznet\Mahout\Fields\FieldGroup;
use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\ObjectContext;
use Iniznet\Mahout\Fields\StorageTarget;
use Iniznet\Mahout\Fields\TextField;
use PHPUnit\Framework\TestCase;

/**
 * The admin slice's invariants: the panel request exposes exactly the save
 * contract's shape and nothing else, the declared panels are the only source
 * of the pairs the field package registers, and the migration notices render
 * only for the states that warrant them.
 */
final class AdminSliceTest extends TestCase
{
    public function testAnAbsentPostKeyIsAbsentAndNotMerelyNull(): void
    {
        $request = PanelRequest::fromArray(['mahout_fields_panel_nonce' => 'abc']);

        self::assertTrue($request->has('mahout_fields_panel_nonce'));
        self::assertFalse($request->has('mahout_fields_panel'));
        self::assertSame('abc', $request->string('mahout_fields_panel_nonce'));
        self::assertNull($request->string('missing'));
    }

    public function testAGroupMapIsTakenVerbatimAndNonStringsAreDropped(): void
    {
        $request = PanelRequest::fromArray([
            'mahout_fields_panel' => [
                'fixture_group' => [
                    'ct_text' => 'kept',
                    'ct_list' => ['a', 7, 'b'],
                    'ct_broken' => 42,
                ],
                7 => 'dropped',
            ],
            'mahout_fields_hash' => ['fixture_group' => ['hash' => 'abc123'], 'other' => 'x'],
        ]);

        $groups = $request->groups();

        self::assertSame(['fixture_group'], array_keys($groups));
        self::assertSame('kept', $groups['fixture_group']['ct_text']);
        self::assertSame(['a', 'b'], $groups['fixture_group']['ct_list']);
        self::assertNull($groups['fixture_group']['ct_broken']);
        self::assertSame('abc123', $request->hashes()['fixture_group'] ?? null);
        self::assertSame([], $request->hashes()['other'] ?? []);
    }

    public function testAScalarBodyYieldsNoGroups(): void
    {
        self::assertSame([], PanelRequest::fromArray(['mahout_fields_panel' => 'not-an-array'])->groups());
        self::assertSame([], PanelRequest::fromArray([])->groups());
        self::assertSame([], PanelRequest::fromArray(['mahout_fields_hash' => 7])->hashes());
    }

    public function testTheDeclaredConfigIsAWorkedExamplePanel(): void
    {
        $panels = require dirname(__DIR__, 2).'/config/fields.php';

        self::assertIsArray($panels);
        self::assertCount(1, $panels, 'the worked example declares one field panel.');
        self::assertInstanceOf(FieldPanel::class, $panels[0]);
        self::assertSame('howdah_series', $panels[0]->postType);
    }

    public function testAJunkConfigEntryIsLoud(): void
    {
        $this->expectException(InvalidFieldDeclaration::class);
        $this->expectExceptionMessage('must list');

        throw InvalidFieldDeclaration::forType('string');
    }

    public function testAPanelFiltersByItsPostTypeAndIsIterable(): void
    {
        $group = new FieldGroup('fixture_group', ObjectContext::Post, [new TextField('ct_text', StorageTarget::Table)], 'Fixture');
        $panels = new FieldPanels([new FieldPanel('fixture_post', $group)]);

        self::assertCount(1, $panels->forPostType('fixture_post'));
        self::assertSame([], $panels->forPostType('other_type'));
        self::assertFalse($panels->isEmpty());
        self::assertSame([$group], array_map(static fn (FieldPanel $panel): FieldGroup => $panel->group, iterator_to_array($panels)));
    }

    public function testTheCollectionOfNoDeclarationIsEmpty(): void
    {
        self::assertTrue((new FieldPanels([]))->isEmpty());
    }

    public function testACurrentSchemaRendersNothingAndAPendingOneWarns(): void
    {
        \ob_start();
        (new MigrationRequiredNotice(new SchemaVersion(3, 3)))->render();
        self::assertSame('', (string) \ob_get_clean());

        \ob_start();
        (new MigrationRequiredNotice(new SchemaVersion(4, 3)))->render();
        $html = (string) \ob_get_clean();

        self::assertStringContainsString('notice-warning', $html);
        self::assertStringContainsString('version 3', $html);
    }

    public function testAFailedRunReportsItsReferenceAndASuccessRendersNothing(): void
    {
        \ob_start();
        (new MigrationFailedNotice(null))->render();
        self::assertSame('', (string) \ob_get_clean());

        \ob_start();
        (new MigrationFailedNotice('diag-7f3a'))->render();
        $html = (string) \ob_get_clean();

        self::assertStringContainsString('notice-error', $html);
        self::assertStringContainsString('diag-7f3a', $html);
    }

    public function testTheSnapshotMapsTheStatusAndReportsPending(): void
    {
        $snapshot = MigrationSnapshot::fromStatus(
            new MigrationStatus(4, 3, ['a'], ['b', 'c']),
        );

        self::assertFalse($snapshot->isCurrent());
        self::assertSame(['b', 'c'], $snapshot->pending);
        self::assertSame(4, $snapshot->codeVersion);
        self::assertSame(3, $snapshot->storedVersion);

        self::assertTrue(MigrationSnapshot::fromStatus(new MigrationStatus(3, 3, ['a'], []))->isCurrent());
    }
}
