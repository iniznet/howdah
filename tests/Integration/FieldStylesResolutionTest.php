<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Mahout\Fields\Admin\FieldStyles;
use Iniznet\Mahout\Fields\Admin\FieldsUiProvider;
use Iniznet\Mahout\Fields\Hooks as FieldHooks;

/**
 * The dev install's stylesheet path: a Composer path repository symlinks the
 * package inside the theme's vendor directory while the real checkout lives
 * outside wp-content, and PHP resolves the link — so the default resolution
 * must follow the link through core's theme API, or a dev checkout fatals on
 * every field screen.
 */
final class FieldStylesResolutionTest extends \WP_UnitTestCase
{
    public function testTheDefaultStylesheetResolvesInsideWpContent(): void
    {
        \remove_all_actions(FieldHooks::ADMIN_ENQUEUE_SCRIPTS);
        (new FieldsUiProvider())->boot(\Iniznet\Howdah\Bootstrap::services());

        \do_action(FieldHooks::ADMIN_ENQUEUE_SCRIPTS, \get_plugin_page_hookname('howdah-display', 'options-general.php'));

        self::assertTrue(\wp_style_is(FieldStyles::HANDLE, 'registered'), 'the declared option screen enqueues the default stylesheet');
        self::assertStringContainsString('wp-content', (string) \wp_styles()->registered[FieldStyles::HANDLE]->src, 'the URL names the served path, inside wp-content');

        \wp_dequeue_style(FieldStyles::HANDLE);
        \wp_deregister_style(FieldStyles::HANDLE);
    }
}
