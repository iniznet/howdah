<?php

/**
 * The one declaration of the block templates this theme ships, one row per
 * file with its reason, in the same shape as config/entries.php.
 *
 * Classic mode ships none: every request resolves through index.php and the
 * dispatch table. Block mode adds rows for editorial slugs only; the slug
 * policy in the render-pipeline contract forbids index, single, singular,
 * archive, search, 404 and embed, because each of those would replace a
 * Surface. front-page and home are permitted only when the project declares
 * the front page editorial — and the consequence is stated: the Front and
 * Home Surfaces then do not render.
 *
 * The gate asserts every file under templates/ appears here and no declared
 * slug is in the never set.
 *
 * @return array{editorial_front_page: bool, templates: array<string, string>}
 */

declare(strict_types=1);

return [
    'editorial_front_page' => false,
    'templates' => [],
];
