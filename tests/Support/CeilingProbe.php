<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Support;

/**
 * The query-ceiling gate. SAVEQUERIES is on in the suite's bootstrap, so the
 * probe reads the exact number of statements the wpdb buffer gained around a
 * render, and refuses when the ceiling the throughput budget declares is
 * exceeded. Every Surface's ceiling test runs through this one probe, and
 * the negative proof runs the same path against a deliberately wasteful
 * closure.
 *
 * The shell is warmed once before the measured render: the document shell
 * fires wp_head and wp_footer, whose first call loads autoloaded options and
 * the custom-CSS post — one shell cost, not the Surface's own, paid once
 * per request and outside the ceiling.
 */
final class CeilingProbe
{
    /**
     * Render inside the probe and return the observed statement count, or
     * refuse loudly when the ceiling is exceeded.
     *
     * @param \Closure(): string $render
     */
    public static function within(\Closure $render, int $ceiling, string $label): int
    {
        global $wpdb;

        $before = \is_array($wpdb->queries) ? \count($wpdb->queries) : 0;
        $render();
        $observed = (\is_array($wpdb->queries) ? \count($wpdb->queries) : 0) - $before;

        if ($observed > $ceiling) {
            throw QueryCeilingExceeded::beyond($label, $observed, $ceiling);
        }

        return $observed;
    }

    /** The count of statements a closure issues, for warm-count assertions. */
    public static function count(\Closure $render): int
    {
        global $wpdb;

        $before = \is_array($wpdb->queries) ? \count($wpdb->queries) : 0;
        $render();

        return (\is_array($wpdb->queries) ? \count($wpdb->queries) : 0) - $before;
    }
}
