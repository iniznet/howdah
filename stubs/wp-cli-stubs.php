<?php

/**
 * The analysis-only WP-CLI stub. WP-CLI is not a WordPress symbol: the
 * generated core stubs deliberately omit it, and the runtime gate is
 * defined( 'WP_CLI' ) && WP_CLI in CliProvider. The stub exists so the
 * theme's one WP-CLI binding can be analysed at the same level as the rest
 * of app/, not so any code path can skip the gate.
 */

declare(strict_types=1);

class WP_CLI
{
    /**
     * The callable accepts ($args, $assocArgs) when it is a command class;
     * WP-CLI also accepts a command name string or a config array, so the
     * parameter stays untyped exactly as WP-CLI's own signature is.
     *
     * @param array<string, mixed> $args
     */
    public static function add_command(string $name, $callable, array $args = []): void
    {
    }

    /** @param callable|array<int|string, mixed> $callback */
    public static function add_action(string $hook, $callback, int $priority = 10): void
    {
    }

    public static function log(string $message): void
    {
    }

    public static function success(string $message): void
    {
    }

    public static function warning(string $message): void
    {
    }

    /** @param array<string, mixed> $assocArgs */
    public static function confirm(string $question, array $assocArgs = []): bool
    {
        return true;
    }

    /**
     * @param bool $exit when true, halts the request with the failure exit code
     */
    public static function error(string $message, bool $exit = true): void
    {
    }
}
