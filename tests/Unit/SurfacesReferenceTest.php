<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Tests\Support\SurfacesReference;
use PHPUnit\Framework\TestCase;

/**
 * The reference gate. The committed docs/reference/surfaces.md must equal a
 * fresh generation from the dispatch table, and the audit must refuse the
 * two shapes the contract forbids: an Uncacheable arm without a reason and
 * a wrapped arm without its Cacheability and FragmentScope. Every negative
 * here mutates a candidate table in memory, never the live tree.
 */
final class SurfacesReferenceTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    private const string DOC = self::ROOT.'/docs/reference/surfaces.md';

    public function testTheCommittedReferenceIsCurrent(): void
    {
        if ('1' === getenv('MAHOUT_SURFACES_REGENERATE')) {
            file_put_contents(self::DOC, SurfacesReference::render(self::ROOT));
        }

        self::assertFileExists(self::DOC, 'the surfaces reference is generated, committed and current.');
        self::assertSame(
            SurfacesReference::render(self::ROOT),
            (string) file_get_contents(self::DOC),
            'docs/reference/surfaces.md drifted from the dispatch table; regenerate with MAHOUT_SURFACES_REGENERATE=1.',
        );
    }

    public function testTheLiveTablePassesTheDeclarationAudit(): void
    {
        self::assertSame(
            [],
            SurfacesReference::audit((string) file_get_contents(self::ROOT.'/app/Surfaces/Surfaces.php')),
            'every live arm declares its Cacheability, its FragmentScope and its reason.',
        );
    }

    public function testTheReferenceNamesEveryLiveArm(): void
    {
        $arms = SurfacesReference::arms(self::ROOT);

        self::assertGreaterThanOrEqual(4, \count($arms), 'the table has the four 8a arms: Embed, Search, NotFound, default.');
        self::assertSame(
            'unmapped request kind',
            $arms[\count($arms) - 1]['reason'],
            'the default arm is written, and its reason is the unmapped-kind statement.',
        );
    }

    public function testAnUncacheableArmWithoutAReasonFailsTheAudit(): void
    {
        $table = "match (true) {\n"
            ."    \$ctx->kind === QueryKind::Search\n"
            ."        => SurfacePlan::uncacheable(\n"
            ."               surface: new SearchResults(\$ctx),\n"
            ."               reason: '',\n"
            ."           ),\n"
            .'};';

        $offences = SurfacesReference::audit($table);

        self::assertNotSame([], $offences, 'an empty reason must fail the audit.');
        self::assertStringContainsString('reason', $offences[0]);
    }

    public function testAWrappedArmWithoutADeclarationFailsTheAudit(): void
    {
        $table = "match (true) {\n"
            ."    \$ctx->kind === QueryKind::Singular\n"
            ."        => SurfacePlan::wrapped(\n"
            ."               surface: new SingleSeries(\$ctx),\n"
            ."               key: FragmentKey::fromParts('x'),\n"
            ."               cache: \$cache,\n"
            ."           ),\n"
            .'};';

        $offences = SurfacesReference::audit($table);

        self::assertSame(2, \count($offences), 'cacheability and fragmentScope are both missing and both refused.');
    }

    public function testAnArmWithoutASurfaceCannotBeFilled(): void
    {
        $this->expectException(\RuntimeException::class);

        SurfacesReference::arms(self::candidateRoot("match (true) {\n    default => SurfacePlan::uncacheable(reason: 'x'),\n};"));
    }

    private static function candidateRoot(string $table): string
    {
        $root = sys_get_temp_dir().'/howdah-surface-ref-'.uniqid();
        mkdir($root.'/app/Surfaces', 0777, true);
        file_put_contents($root.'/app/Surfaces/Surfaces.php', '<?php match (true) '."{\n".$table."\n");

        return $root;
    }
}
