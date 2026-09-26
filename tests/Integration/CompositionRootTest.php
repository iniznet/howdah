<?php

/**
 * The starter demonstrates the rule it is subject to.
 *
 * `Bootstrap::run()` names itself when it builds the kernel, so this process belongs
 * to the composition root and no second host can boot alongside it. The refusal is
 * asserted first and on purpose: if the suite's bootstrap had never claimed the
 * process, `Kernel::inWordPress(self::class)` here would succeed and the test would
 * fail rather than pass because the previous method claimed it. The claim itself is
 * not read directly — it is another package's `@internal`, and the theme has no
 * business reaching into it.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Bootstrap;
use Iniznet\Mahout\Kernel\Exception\SecondCompositionRoot;
use Iniznet\Mahout\Kernel\Kernel;

final class CompositionRootTest extends \WP_UnitTestCase
{
    public function testADifferentRootCannotBootAlongsideTheStarter(): void
    {
        try {
            Kernel::inWordPress(self::class);
        } catch (SecondCompositionRoot $failure) {
            self::assertSame(Bootstrap::class, $failure->claimedBy());
            self::assertSame(self::class, $failure->attemptedBy());

            return;
        }

        self::fail('a second composition root was allowed into a process the starter already owns.');
    }

    public function testTheRootThatOwnsThisProcessMayBuildItsKernelAgain(): void
    {
        self::assertFalse(
            Kernel::inWordPress(Bootstrap::class)->booted(),
            'a host that builds its kernel twice is not a second owner.',
        );
    }
}
