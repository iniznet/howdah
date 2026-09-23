# Admin surfaces reference

The named admin surfaces of the field-and-admin contract, in the order the
spec lists them. `tests/Unit/AdminSurfacesTest.php` fails when this list and
the code inventory drift in either direction, so the names live in the
published tree and the gate runs in a fork pull request.

This document is hand-maintained; its authority for *which surfaces exist* is
§2 of the admin specification in the private planning corpus, which is never
published. Nothing committed links into `/docs/planning/`: a fork clone has no
such directory, so a published file that pointed there would carry a dead link
and a test that read it could not run. A new surface is named in that spec
first, then here, then built — the same order the hook contract follows.

| Surface | Owner | Why the gate watches it |
|---|---|---|
| `FieldPanel` | `mahout-fields` — the `FieldPanel` value, the `Admin\FieldMetabox` assembler and the control templates | The pairing of a field group with the post type whose edit screen renders it. A theme that registered a metabox by hand would own a second path to the same panel, with no save lifecycle and no post lock on it |
| `ContentModelColumns` | `howdah`'s `AdminProvider`, through the theme's `Admin\ContentModelColumns` | Read-only `Table` field values and one filter dropdown per filterable field, on the core list screen. No `WP_List_Table` subclass and no quick-edit write path |
| `ThemeSettingsScreen` | `howdah`'s `AdminProvider` | The theme's own display options, behind `edit_theme_options` |
| `StatusScreen` | `howdah`'s `AdminProvider` | Schema version, migration state and the `doctor` summary, behind `manage_options` and a nonce |
| `FieldWriteFailedNotice` | `mahout-fields` — `Admin\WriteFailureNoticeRenderer` | A rolled-back field write, reported with its support reference. The rendered string belongs to the `mahout-fields` text domain, not the theme's, and the theme's POT has no entry for it |
| `MigrationRequiredNotice` | `howdah`'s `AdminProvider` | The stored schema version trails the code constant and the lazy path has not yet run |
| `MigrationFailedNotice` | `howdah`'s `AdminProvider` | A failed migration, with its support reference |

`FieldPanelMeta`, `RevisionRestoredNotice` and `BlockEditorFieldPanel` are named
by the same spec section and are not yet built. They are deliberately absent
from this table: the gate watches the surfaces that exist, and an unbuilt one is
a roadmap row, not a missing class.
