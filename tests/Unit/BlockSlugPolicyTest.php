<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use Iniznet\Howdah\Tests\Support\BlockSlugPolicy;
use PHPUnit\Framework\TestCase;

/**
 * The slug policy is asserted, not reviewed. This theme ships no block
 * template that could replace a Surface: templates/index.html does not
 * exist, and nothing under templates/ escapes the declaration.
 */
final class BlockSlugPolicyTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    public function testTemplatesIndexHtmlDoesNotExist(): void
    {
        self::assertFileDoesNotExist(
            self::ROOT.'/templates/index.html',
            'a block template with slug index replaces index.php, and with it every Surface.',
        );
    }

    public function testTheLiveTreePassesTheSlugPolicy(): void
    {
        self::assertSame([], BlockSlugPolicy::offences(self::ROOT), 'the declared block templates obey the never column.');
    }

    /** The negative: a tree that ships index and declares it fails, per gate. */
    public function testAShippedIndexSlugFailsTheGate(): void
    {
        $root = self::mutatedTree(['index' => 'forbidden by the policy, declared here to prove the gate']);

        $offences = BlockSlugPolicy::offences($root);

        self::assertNotSame([], $offences, 'the gate must refuse a declared index slug.');
        self::assertStringContainsString('never column', $offences[0]);
    }

    /** The negative: an undeclared shipped file fails, per gate. */
    public function testAnUndeclaredShippedTemplateFailsTheGate(): void
    {
        $root = self::mutatedTree([]);

        $offences = BlockSlugPolicy::offences($root);

        self::assertNotSame([], $offences, 'the gate must refuse a shipped-but-undeclared template.');
        self::assertStringContainsString('not declared', $offences[0]);
    }

    /** @param array<string, string> $templates */
    private static function mutatedTree(array $templates): string
    {
        $root = sys_get_temp_dir().'/howdah-slug-gate-'.uniqid();
        mkdir($root.'/config', 0777, true);
        mkdir($root.'/templates', 0777, true);
        file_put_contents($root.'/templates/index.html', '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->');
        file_put_contents($root.'/config/block-templates.php', sprintf(
            "<?php return ['editorial_front_page' => false, 'templates' => %s];",
            var_export($templates, true),
        ));

        return $root;
    }
}
