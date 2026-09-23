<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Support;

/**
 * The surfaces reference generator. It parses the dispatch table and emits
 * docs/reference/surfaces.md — one row per plan an arm can reach, with its
 * Surface, its declared Cacheability, its FragmentScope and, for an
 * Uncacheable plan, the reason. A row it cannot fill fails generation: an arm
 * that reaches no terminal fails a test AND fails this reference gate.
 *
 * The table is written with `mahout-render`'s SurfacePlanBuilder, so the
 * declaration a row reads is the arm's TERMINAL: `shared()` is the Shared
 * class over the Shared scope, `uncacheable()` is the Uncacheable class over
 * the Never scope and carries the reason, and `guardOverflow()` is an arm's
 * second path — the same Surface, Uncacheable and stated, beyond the last
 * page the content graph holds. The pair is not an argument an author can
 * omit, so the gate reads the terminal and refuses an arm that never reaches
 * one.
 *
 * The parser is deliberately shape-tolerant: a condition may sit on its own
 * line or share the arrow's line, because formatting is not the contract —
 * the declaration is.
 */
final class SurfacesReference
{
    private const string TABLE_PATH = '/app/Surfaces/Surfaces.php';

    /** The one call each arm begins with, and therefore the arm's anchor. */
    private const string ARM = '->surface(';

    private const string SHARED = '->shared(';

    private const string GUARDED = '->guardOverflow(';

    private const string REFUSING = '->uncacheable(';

    /**
     * Every arm of the dispatch table, parsed, one row per plan it reaches.
     *
     * @return list<array{arm: string, surface: string, cacheability: string, scope: string, reason: string}>
     */
    public static function arms(string $root): array
    {
        $source = (string) file_get_contents($root.self::TABLE_PATH);
        $table = self::table($source);
        $anchors = self::anchors($table);
        $arms = [];

        foreach ($anchors as $index => $at) {
            $start = 0 === $index ? 0 : $anchors[$index - 1];
            $preceding = (string) substr($table, $start, $at - $start);
            $body = (string) substr($table, $at, self::bodyEnd($table, $at) - $at);

            foreach (self::rows(self::armName($preceding), $body) as $row) {
                $arms[] = $row;
            }
        }

        return $arms;
    }

    /** The committed reference document for a tree. */
    public static function render(string $root): string
    {
        $rows = '';

        foreach (self::arms($root) as $arm) {
            $rows .= sprintf(
                '| %s | %s | %s | %s | %s |%s',
                self::cell($arm['arm']),
                self::cell($arm['surface']),
                self::cell($arm['cacheability']),
                self::cell($arm['scope']),
                self::cell($arm['reason']),
                "\n",
            );
        }

        $header = "# Surfaces reference\n\n"
            ."Generated from app/Surfaces/Surfaces.php by tests/Support/SurfacesReference.php.\n"
            ."One row per plan a dispatch arm reaches: the Surface that renders the request, its\n"
            ."declared cacheability class, its fragment scope and, for an Uncacheable plan, the\n"
            ."reason it is not stored. The plan is the arm's terminal — shared(), uncacheable()\n"
            ."or a guardOverflow() that ends in shared() — and an arm that reaches none fails a\n"
            ."test and fails this reference gate.\n\n"
            ."Regenerate with MAHOUT_SURFACES_REGENERATE=1 vendor/bin/phpunit --filter SurfacesReferenceTest.\n\n"
            ."| Arm | Surface | Cacheability | Fragment scope | Reason |\n"
            ."|---|---|---|---|---|\n";

        return $header.$rows;
    }

    /**
     * The gate: every arm of a candidate table must name its Surface and
     * reach a terminal, and every terminal that stores nothing must state
     * why. Every refusal is one finding.
     *
     * @return list<string>
     */
    public static function audit(string $table): array
    {
        $offences = [];

        foreach (self::anchors($table) as $at) {
            $body = (string) substr($table, $at, self::bodyEnd($table, $at) - $at);
            $name = self::armName((string) substr($table, 0, $at));

            if (1 !== preg_match('/new\s+[A-Za-z0-9_\\\\]+/', $body)) {
                $offences[] = 'arm "'.$name.'": no Surface class named.';
                continue;
            }

            $refusing = self::first($body, self::REFUSING);
            $shared = self::first($body, self::SHARED);
            $guard = self::first($body, self::GUARDED);

            if (null === $refusing && null === $shared) {
                $offences[] = 'arm "'.$name.'": no cacheability terminal; an arm ends in shared() or uncacheable().';
                continue;
            }

            if (null !== $refusing && '' === self::reasonAt($body, $refusing)) {
                $offences[] = 'arm "'.$name.'": an Uncacheable arm must state its reason.';
            }

            if (null !== $guard && '' === self::reasonAt($body, $guard)) {
                $offences[] = 'arm "'.$name.'": a guarded arm must state why its out-of-range page is not stored.';
            }
        }

        return $offences;
    }

    /**
     * The rows one arm writes: one for a plain arm, two for a guarded arm,
     * because a guard is the same Surface on two declared paths.
     *
     * @return list<array{arm: string, surface: string, cacheability: string, scope: string, reason: string}>
     */
    private static function rows(string $arm, string $body): array
    {
        $surface = 1 === preg_match('/new\s+([A-Za-z0-9_\\\\]+)/', $body, $m) ? $m[1] : '';

        if ('' === $surface) {
            throw new \RuntimeException('A dispatch arm the reference cannot fill: '.$arm.'.');
        }

        $refusing = self::first($body, self::REFUSING);
        $shared = self::first($body, self::SHARED);
        $guard = self::first($body, self::GUARDED);

        if (null !== $refusing && (null === $shared || $refusing < $shared)) {
            $reason = self::reasonAt($body, $refusing);

            if ('' === $reason) {
                throw new \RuntimeException('An Uncacheable arm without a reason fails the reference gate.');
            }

            return [self::row($arm, $surface, 'Uncacheable', 'Never', $reason)];
        }

        if (null === $shared) {
            throw new \RuntimeException('A dispatch arm with no cacheability terminal fails the reference gate: '.$arm.'.');
        }

        if (null === $guard) {
            return [self::row($arm, $surface, 'Shared', 'Shared', '')];
        }

        $refusal = self::reasonAt($body, $guard);

        if ('' === $refusal) {
            throw new \RuntimeException('A guarded arm without a reason fails the reference gate.');
        }

        return [
            self::row($arm, $surface, 'Shared', 'Shared', ''),
            self::row($arm.' (out of range)', $surface, 'Uncacheable', 'Never', $refusal),
        ];
    }

    /**
     * @return array{arm: string, surface: string, cacheability: string, scope: string, reason: string}
     */
    private static function row(string $arm, string $surface, string $cacheability, string $scope, string $reason): array
    {
        return [
            'arm' => $arm,
            'surface' => $surface,
            'cacheability' => $cacheability,
            'scope' => $scope,
            'reason' => $reason,
        ];
    }

    /**
     * @return list<int>
     */
    private static function anchors(string $table): array
    {
        $offsets = [];
        $cursor = 0;

        while (($at = strpos($table, self::ARM, $cursor)) !== false) {
            $offsets[] = $at;
            $cursor = $at + strlen(self::ARM);
        }

        if ([] === $offsets) {
            throw new \RuntimeException('No dispatch arm was parsed; the reference cannot be generated.');
        }

        return $offsets;
    }

    /** The offset of one call inside an arm's body, or null when it has none. */
    private static function first(string $body, string $needle): ?int
    {
        $at = strpos($body, $needle);

        return false === $at ? null : $at;
    }

    /** The single-quoted reason a terminal or a guard carries, possibly empty. */
    private static function reasonAt(string $body, int $at): string
    {
        $close = strpos($body, '(', $at);

        if (false === $close) {
            return '';
        }

        $rest = (string) substr($body, $close + 1);

        return 1 === preg_match("/^\s*'([^']*)'/", $rest, $m) ? $m[1] : '';
    }

    /** The end of one arm's body: the next arm's anchor, or the table's. */
    private static function bodyEnd(string $table, int $at): int
    {
        $next = strpos($table, self::ARM, $at + strlen(self::ARM));

        return false === $next ? strlen($table) : (int) $next;
    }

    private static function table(string $source): string
    {
        $start = strpos($source, 'match (true) {');

        if (false === $start) {
            throw new \RuntimeException('The dispatch table (match (true)) was not found in Surfaces.php.');
        }

        $open = (int) strpos($source, '{', $start);
        $depth = 0;
        $length = strlen($source);

        for ($i = $open; $i < $length; ++$i) {
            if ('{' === $source[$i]) {
                ++$depth;
            } elseif ('}' === $source[$i]) {
                --$depth;

                if (0 === $depth) {
                    return substr($source, $start, $i + 1 - $start);
                }
            }
        }

        throw new \RuntimeException('Unbalanced braces in the dispatch table.');
    }

    private static function armName(string $preceding): string
    {
        // The arm's condition is everything before the match arrow that
        // immediately precedes the arm's anchor.
        $arrow = strrpos($preceding, '=>');

        if (false !== $arrow) {
            $preceding = (string) substr($preceding, 0, $arrow);
        }

        // Everything up to the last comma before that arrow belongs to the
        // previous arm — a condition carries no comma of its own. The first
        // arm has no previous arm, and what precedes it is the table's brace.
        $separator = strrpos($preceding, ',');

        if (false === $separator) {
            $separator = strrpos($preceding, '{');
        }

        if (false !== $separator) {
            $preceding = (string) substr($preceding, $separator + 1);
        }

        return trim($preceding, " \t\r\n,{");
    }

    private static function cell(string $value): string
    {
        return '`'.$value.'`';
    }
}
