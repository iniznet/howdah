<?php

/**
 * The WP-CLI seam. One provider names every command; registration is gated
 * on WP_CLI being defined, because a WP_CLI::add_command() call on a site
 * without WP-CLI is a fatal, and the commands register on cli_init, which
 * WP-CLI fires before it dispatches. The theme operates without WP-CLI: this
 * is the migration fallback's first choice, not a boot dependency.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Cli\MigrateCommand;
use Iniznet\Howdah\Cli\MigratePlanner;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Db\MigrationRunner;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Kernel\Diagnostics;

final class CliProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // The command set is an operator convenience; nothing to register.
    }

    public function boot(Container $container): void
    {
        if (!\defined('WP_CLI') || !\WP_CLI) {
            return;
        }

        \WP_CLI::add_action(
            Hooks::CLI_INIT,
            static function () use ($container): void {
                $planner = new MigratePlanner(
                    $container->get(MigrationRunner::class),
                    $container->get(Diagnostics::class),
                );

                \WP_CLI::add_command('mahout migrate', new MigrateCommand($planner));
            },
        );
    }
}
