<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Support;

/**
 * The surfaces reference generator. It parses the dispatch table and emits
 * docs/reference/surfaces.md — one row per arm with its Surface, its
 * declared Cacheability, its FragmentScope and, for an Uncacheable arm, the
 * reason. A row it cannot fill fails generation: an arm without a
 * cacheability declaration fails a test AND fails this reference gate.
 *
 * The parser is deliberately shape-tolerant: a condition may sit on its own
 * line or share the arrow's line, because formatting is not the contract —
 * the declaration is.
 */
final class SurfacesReference
{
    private const string TABLE_PATH = '/app/Render/Surfaces.php';

    private const string CALL = 'SurfacePlan::';

    /**
     * Every arm of the dispatch table, parsed.
     *
     * @return list<array{arm: string, surface: string, cacheability: string, scope: string, reason: string}>
     */
    public static function arms(string $root): array
    {
        $source = (string) file_get_contents($root.self::TABLE_PATH);
        $table = self::table($source);
        $arms = [];

        $offsets = [];
        $cursor = 0;

        while (($at = strpos($table, self::CALL, $cursor)) !== false) {
            $offsets[] = $at;
            $cursor = $at + strlen(self::CALL);
        }

        if ([] === $offsets) {
            throw new \RuntimeException('No dispatch arm was parsed; the reference cannot be generated.');
        }

        $arms = [];

        foreach ($offsets as $index => $at) {
            $start = 0 === $index ? 0 : $offsets[$index - 1] + strlen(self::CALL);
            $preceding = (string) substr($table, $start, (int) $at - $start);
            $kind = self::kindAt($table, (int) $at);
            $body = self::armBody($table, (int) $at);

            $arms[] = self::row(self::armName($preceding), $kind, $body);
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
            ."Generated from app/Render/Surfaces.php by tests/Support/SurfacesReference.php.\n"
            ."One row per dispatch arm: the Surface that renders the request, its declared\n"
            ."cacheability class, its fragment scope and, for an Uncacheable arm, the\n"
            ."reason it is not stored. A dispatch arm with no declaration fails a test and\n"
            ."fails this reference gate.\n\n"
            ."Regenerate with MAHOUT_SURFACES_REGENERATE=1 vendor/bin/phpunit --filter SurfacesReferenceTest.\n\n"
            ."| Arm | Surface | Cacheability | Fragment scope | Reason |\n"
            ."|---|---|---|---|---|\n";

        return $header.$rows;
    }

    /**
     * The gate: every arm of a candidate table must name its Surface and its
     * declarations. Every refusal is one finding.
     *
     * @return list<string>
     */
    public static function audit(string $table): array
    {
        $offences = [];
        $starts = [];
        $at = 0;

        while (($at = strpos($table, self::CALL, $at)) !== false) {
            $starts[] = $at;
            $at += strlen(self::CALL);
        }

        foreach ($starts as $index => $at) {
            $kind = self::kindAt($table, $at);
            $name = self::armName((string) substr($table, 0 === $index ? 0 : $starts[$index - 1] + strlen(self::CALL), $at - (0 === $index ? 0 : $starts[$index - 1] + strlen(self::CALL))));
            $body = self::armBody($table, $at);

            if (1 !== preg_match('/new\s+[A-Za-z0-9_\\\\]+/', $body)) {
                $offences[] = 'arm "'.$name.'": no Surface class named.';
                continue;
            }

            if ('wrapped' === $kind) {
                if (1 !== preg_match('/cacheability:\s*Cacheability::[A-Za-z]+/', $body)) {
                    $offences[] = 'arm "'.$name.'": cacheability is not declared; every arm declares a Cacheability.';
                }

                if (1 !== preg_match('/fragmentScope:\s*FragmentScope::[A-Za-z]+/', $body)) {
                    $offences[] = 'arm "'.$name.'": fragmentScope is not declared; every arm declares a FragmentScope.';
                }

                continue;
            }

            if (1 !== preg_match("/reason:\s*'[^']+'/", $body)) {
                $offences[] = 'arm "'.$name.'": an Uncacheable arm must state its reason.';
            }
        }

        return $offences;
    }

    private static function kindAt(string $table, int $at): string
    {
        $rest = substr($table, $at, strlen(self::CALL) + 32);

        return str_contains($rest, self::CALL.'wrapped') ? 'wrapped' : 'uncacheable';
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

    private static function armBody(string $table, int $offset): string
    {
        $call = (int) strpos($table, self::CALL, $offset);
        $open = (int) strpos($table, '(', $call);
        $depth = 0;
        $length = strlen($table);

        for ($i = $open; $i < $length; ++$i) {
            if ('(' === $table[$i]) {
                ++$depth;
            } elseif (')' === $table[$i]) {
                --$depth;

                if (0 === $depth) {
                    return substr($table, $call, $i + 1 - $call);
                }
            }
        }

        throw new \RuntimeException('Unbalanced parentheses in a dispatch arm.');
    }

    private static function armName(string $preceding): string
    {
        // The arm's condition is everything before the match arrow that
        // immediately precedes the SurfacePlan construction.
        $arrow = strrpos($preceding, '=>');

        if (false !== $arrow) {
            $preceding = (string) substr($preceding, 0, $arrow);
        }

        return trim($preceding, " \t\r\n,");
    }

    private static function cell(string $value): string
    {
        return '`'.$value.'`';
    }

    /**
     * One row per arm; an arm the gate cannot fill throws.
     *
     * @return array{arm: string, surface: string, cacheability: string, scope: string, reason: string}
     */
    private static function row(string $arm, string $kind, string $body): array
    {
        $surface = 1 === preg_match('/new\s+([A-Za-z0-9_\\\\]+)/', $body, $m) ? $m[1] : '';
        $cacheability = 1 === preg_match('/cacheability:\s*Cacheability::([A-Za-z]+)/', $body, $c) ? $c[1] : '';
        $scope = 1 === preg_match('/fragmentScope:\s*FragmentScope::([A-Za-z]+)/', $body, $s) ? $s[1] : '';
        $reason = 1 === preg_match("/reason:\s*'([^']*)'/", $body, $r) ? $r[1] : '';

        if ('' === $surface) {
            throw new \RuntimeException('A dispatch arm the reference cannot fill: '.$arm.' ('.$kind.').');
        }

        if ('uncacheable' === $kind) {
            if ('' === $reason) {
                throw new \RuntimeException('An Uncacheable arm without a reason fails the reference gate.');
            }

            return ['arm' => $arm, 'surface' => $surface, 'cacheability' => 'Uncacheable', 'scope' => 'Never', 'reason' => $reason];
        }

        if ('' === $cacheability || '' === $scope) {
            throw new \RuntimeException('A wrapped arm without its Cacheability and FragmentScope fails the reference gate.');
        }

        return ['arm' => $arm, 'surface' => $surface, 'cacheability' => $cacheability, 'scope' => $scope, 'reason' => $reason];
    }
}
