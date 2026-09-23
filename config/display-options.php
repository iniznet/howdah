<?php

/**
 * The theme's declared display options. Every entry is an OptionScreen: a
 * mahout-fields field group declared for the option context, paired with the
 * settings page that renders it. The page itself is derived from this list
 * by the field package's Admin\FieldsUiProvider — one submenu page per
 * declared screen, its save entry and its write-failure notice — so the
 * theme names no settings screen of its own, and a group the option context
 * cannot serve is refused at registration: the context must be
 * ObjectContext::Option, and every field must declare Meta storage, because
 * the option context has no table rows.
 *
 * Field ids change nothing else in the theme. A value is read through the
 * field layer's reader with ObjectRef::option(), and the package's
 * MetaStorage stores it in wp_options under its own "mahout_fields/<field
 * id>" key — the package's prefix, never the theme's "howdah_" one. A field
 * declares no default: absence reads null, and a default is the consumer's
 * decision, not the declaration's.
 *
 * The starter theme is opinionless and declares none, so the package
 * attaches no settings page; a generated theme's features extend this list.
 *
 * @return list<\Iniznet\Mahout\Fields\OptionScreen>
 */

declare(strict_types=1);

return [];
