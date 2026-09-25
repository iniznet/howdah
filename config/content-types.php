<?php

/**
 * The theme's declared content model. Every entry is registered on init
 * through the content package's Contracts surface — the theme's ContentProvider
 * reads this file, and the package's Registrar applies the naming rules, the
 * reserved list and the collision check before core sees anything.
 *
 * The shipped declaration is the worked example: the Series post type with
 * its topic taxonomy. Delete both entries and the feature stops existing —
 * the repository is never queried, the archive arm never matches, and the
 * field panel renders nothing.
 *
 * Labels are plain strings: a declaration is read at boot, before the text
 * domain loads, so a declaration cannot translate.
 *
 * @return list<\Iniznet\Mahout\Content\PostType|\Iniznet\Mahout\Content\Taxonomy|\Iniznet\Mahout\Content\RestRoute>
 */

declare(strict_types=1);

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\Taxonomy;

return [
    new PostType('howdah_series', [
        'labels' => [
            'name' => 'Series',
            'singular_name' => 'Series',
            'all_items' => 'All series',
            'add_new_item' => 'Add new series',
        ],
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-media-default',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
        'rewrite' => ['slug' => 'series'],
    ]),
    new Taxonomy('series_topic', ['howdah_series'], [
        'labels' => [
            'name' => 'Topics',
            'singular_name' => 'Topic',
        ],
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
    ]),
];
