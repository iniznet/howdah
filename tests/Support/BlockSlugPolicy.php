<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Support;

/**
 * The block-template slug policy, as a tree-level gate. Every file under
 * templates/ must be declared in config/block-templates.php with its reason,
 * and no declared slug may sit in the never column: index (it is the
 * Surfaces entry point), single, singular, archive, search, 404 and embed
 * each replace a Surface. front-page and home are permitted only when the
 * project declares the front page editorial.
 */
final class BlockSlugPolicy
{
    /** @var list<string> */
    public const NEVER = ['index', 'single', 'singular', 'archive', 'search', '404', 'embed'];

    /** @var list<string> */
    public const FRONT_CONDITIONAL = ['front-page', 'home'];

    /** @return list<string> every offence, one per finding */
    public static function offences(string $root): array
    {
        $offences = [];

        $declaration = require $root.'/config/block-templates.php';

        if (!is_array($declaration) || !isset($declaration['editorial_front_page'], $declaration['templates'])
            || !is_bool($declaration['editorial_front_page']) || !is_array($declaration['templates'])) {
            return ['config/block-templates.php is not the documented shape.'];
        }

        $declared = $declaration['templates'];
        $editorialFront = $declaration['editorial_front_page'];

        $shipped = [];

        if (is_dir($root.'/templates')) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $root.'/templates',
                \FilesystemIterator::SKIP_DOTS,
            ));

            foreach ($files as $file) {
                if ($file instanceof \SplFileInfo && 'html' === $file->getExtension()) {
                    $shipped[] = $file->getBasename('.html');
                }
            }
        }

        foreach ($shipped as $slug) {
            if (!isset($declared[$slug]) || !is_string($declared[$slug])) {
                $offences[] = 'templates/'.$slug.'.html is shipped but not declared with a reason.';
            }
        }

        foreach ($declared as $slug => $reason) {
            $slug = (string) $slug;

            if (in_array($slug, self::NEVER, true)) {
                $offences[] = 'slug "'.$slug.'" is in the never column; shipping it replaces a Surface.';
                continue;
            }

            if (in_array($slug, self::FRONT_CONDITIONAL, true) && !$editorialFront) {
                $offences[] = 'slug "'.$slug.'" requires the project to declare the front page editorial.';
            }
        }

        return $offences;
    }
}
