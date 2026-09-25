<?php

/**
 * The theme's declared display options. Every entry is an OptionScreen: a
 * mahout-fields field group declared for the option context, paired with the
 * settings page that renders it. The page itself is derived from this list
 * by the field package's Admin\FieldsUiProvider — one submenu page per
 * declared screen, its save entry and its write-failure notice.
 *
 * A value is read through the field layer's reader with ObjectRef::option();
 * the package's MetaStorage stores it in wp_options under its own
 * "mahout_fields/<field id>" key. A field declares no default: absence reads
 * null, and a default is the consumer's decision.
 *
 * The shipped declaration is the worked example: one footer note rendered by
 * the site chrome's colophon. Delete the entry and the settings page stops
 * existing, and the chrome falls back to the site description.
 *
 * Labels are plain strings: a declaration is read at boot, before the text
 * domain loads, so a declaration cannot translate.
 *
 * @return list<\Iniznet\Mahout\Fields\OptionScreen>
 */

declare(strict_types=1);

use Iniznet\Mahout\Fields\FieldGroup;
use Iniznet\Mahout\Fields\ObjectContext;
use Iniznet\Mahout\Fields\OptionScreen;
use Iniznet\Mahout\Fields\StorageTarget;
use Iniznet\Mahout\Fields\TextField;

$displayGroup = new FieldGroup('display_options', ObjectContext::Option, [
    new TextField('footer_note', StorageTarget::Meta, label: 'Footer note'),
]);

return [
    new OptionScreen(
        pageSlug: 'howdah-display',
        pageTitle: 'Display',
        menuTitle: 'Display',
        group: $displayGroup,
        capability: 'manage_options',
    ),
];
