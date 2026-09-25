<?php

/**
 * The theme's declared display options. Every entry is an OptionScreen: the
 * settings page a tabbed set of sections renders. The page itself is derived
 * from this list by the field package's Admin\FieldsUiProvider — one
 * submenu page per declared screen, its save entry and its write-failure
 * notice — and a screen whose tabs carry no field group at all renders no
 * form: a documentation page is a screen like any other.
 *
 * A screen declares its content one way: its own group — the one-line shape
 * for a plain field page — or tabs of sections, each section either one
 * group's fields (inside the save lifecycle) or a markup file the declaring
 * feature ships. The shipped example shows both, on the two tabs of one
 * page: the footer note's fields, and the guide that documents them.
 *
 * The screen registers its own top-level menu, not a Settings submenu, and
 * reads as a document: the sidebar layout. Both are the declaration's facts.
 *
 * A value is read through the field layer's reader with ObjectRef::option();
 * the package's MetaStorage stores it in wp_options under its own
 * "mahout_fields/<field id>" key. A field declares no default: absence reads
 * null, and a default is the consumer's decision.
 *
 * Delete the entry and the settings page stops existing, and the chrome
 * falls back to the site description.
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
use Iniznet\Mahout\Fields\OptionScreenLayout;
use Iniznet\Mahout\Fields\OptionSection;
use Iniznet\Mahout\Fields\OptionTab;
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
        group: null,
        capability: 'manage_options',
        menuParent: '',
        description: 'How the site takes its display options, and where each one renders.',
        layout: OptionScreenLayout::Sidebar,
        topLevel: true,
        menuIcon: 'dashicons-layout',
        tabs: [
            new OptionTab('Options', [
                OptionSection::fields('Footer note', $displayGroup),
            ]),
            new OptionTab('Guide', [
                OptionSection::content('How display options work', __DIR__.'/../app/Admin/markup/display-guide.php'),
            ]),
        ],
    ),
];
