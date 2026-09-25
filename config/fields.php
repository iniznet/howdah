<?php

/**
 * The field-panel declarations: one entry per (post type, field group) pair.
 * A feature declares its groups through the field package's FieldGroup, and
 * the panel pairs the group with the post type whose edit screen renders it.
 * The field package's admin provider derives every metabox, save entry and
 * REST binding from this list.
 *
 * The shipped declaration is the worked example: a tagline on the Series
 * post type, read with its post and never filtered, so it declares Meta
 * storage. Delete the entry and the panel stops existing.
 *
 * Labels are plain strings: a declaration is read at boot, before the text
 * domain loads, so a declaration cannot translate. The package's own chrome
 * wording is translated by the package at render time.
 *
 * @return list<\Iniznet\Mahout\Fields\FieldPanel>
 */

declare(strict_types=1);

use Iniznet\Mahout\Fields\FieldGroup;
use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\ObjectContext;
use Iniznet\Mahout\Fields\StorageTarget;
use Iniznet\Mahout\Fields\TextField;

return [
    new FieldPanel('howdah_series', new FieldGroup('series_details', ObjectContext::Post, [
        new TextField('series_tagline', StorageTarget::Meta, label: 'Tagline'),
    ], label: 'Series details')),
];
