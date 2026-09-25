# Tutorial — a field, end to end

This walks one vertical slice the whole way: a **subtitle** on posts, editable
in the admin panel, rendered on the single-post page, tested at every lane. It
follows the six-step recipe in [extending.md](./extending.md) on the theme's
real shapes — nothing here is a simplified variant. Budget: about twenty
minutes plus a test run.

## What you will touch

| Lane | File | Why |
|---|---|---|
| Declare | `config/fields.php` | the one place a field group is declared |
| Query | `app/Features/Content/ContentRepository.php` | the only lane that reads values |
| Render | `app/Components/Subtitle.php` + `markup/subtitle.php` | typed props to HTML |
| Compose | `app/Features/Content/Surfaces/SinglePost.php` | the page assembles the parts |
| Test | `tests/Unit/SubtitleTest.php` | rendered output, once |

## 1 — Declare the field

Open `config/fields.php`. The starter ships one declaration — the Series
worked example, `app/Features/Series/` — and every admin surface the field
package renders derives from this list and from nothing else. The example
below adds a second panel:

```php
use Iniznet\Mahout\Fields\FieldGroup;
use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\ObjectContext;
use Iniznet\Mahout\Fields\StorageTarget;
use Iniznet\Mahout\Fields\TextField;

return [
    new FieldPanel('post', new FieldGroup('post_meta_group', ObjectContext::Post, [
        new TextField('subtitle', StorageTarget::Meta, label: 'Subtitle'),
    ], label: 'Post meta')),
];
```

Two declarations are doing the work:

- **`StorageTarget::Meta`** — storage is a required argument with no default.
  Ask the question: *will this be filtered, sorted or counted?* A subtitle is
  read with its post and never queried, so it is `Meta` (load-with-entity).
  Something you would filter or sort by is `Table`.
- **`FieldPanel('post', …)`** — the group paired with the post type whose
  edit screen renders it. One group may serve several post types: one panel
  per pair.

## 2 — Register nothing

This is the step that feels wrong the first time: there is no step. The field
package's admin provider derives the metabox, the classic save entry with its
fixed guard order, the REST read binding and the write-failure notice from the
panel above. The controls arrive styled by default. The theme's
`EditorProvider` already binds the collection; `Bootstrap.php` already
registers the providers. Activate the theme, open a post, and the panel is
there.

## 3 — Read through the field layer

Values are read through `Contracts\FieldReader` — never `get_post_meta`.
The repository is the lane that reads; the component never does:

```php
// app/Features/Content/ContentRepository.php — one method added
public function subtitle(int $postId): ?string
{
    $raw = $this->fields->value('subtitle', ObjectRef::post($postId));

    return '' === $raw ? null : (string) $raw;
}
```

`ContentRepository` gains the reader in its constructor (`FieldReader` is
bound under its contract by `FieldsProvider`, so the container resolves it).
Expected absence is `?T` — an empty subtitle is null, not an empty string.

## 4 — Render it as a component

A component renders typed props and nothing else: no reads, no globals, no
`WP_*` types, one escape per output:

```php
// app/Components/Subtitle.php
final class Subtitle extends MarkupComponent
{
    public function __construct(
        ClassResolver $classes,
        private readonly string $subtitle,
    ) {
        parent::__construct($classes);
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $subtitle = $this->subtitle;
        require __DIR__.'/markup/subtitle.php';

        return (string) \ob_get_clean();
    }
}
```

```php
<?php // app/Components/markup/subtitle.php
/**
 * @var Iniznet\Mahout\Ui\ClassResolver $c
 * @var string                           $subtitle
 */
?>
<p class="<?php echo esc_attr($c('subtitle')); ?>"><?php echo esc_html($subtitle); ?></p>
```

`$c()` resolves the theme's stable class names; see `docs/architecture.md`
for the resolver's contract.

## 5 — Compose it into the page

The Surface is the only place parts become a page. `SinglePost` already holds
the repository, so the body gains one line and the constructor stays as it is
—the subtitle rides the existing query ceiling:

```php
$subtitle = $this->content->subtitle($post->id);

return new Document(
    $this->classes,
    main: new Stack([
        new Stack([
            new Heading($this->classes, HeadingLevel::One, $post->title),
            null !== $subtitle ? new Subtitle($this->classes, $subtitle) : null,
            new PostMeta($this->classes, $post),
        ]),
        // …
    ]),
    // …
);
```

The Surface is declared `Shared`: the subtitle is part of the post's content
graph, the fragment key already carries the post id, and nothing per-user is
rendered. That is why the field read is safe on this arm — the same discipline
that keeps two anonymous visitors byte-identical.

## 6 — Test the lanes you touched

```php
// tests/Unit/SubtitleTest.php
public function testTheSubtitleRendersEscapedOnce(): void
{
    $classes = ClassResolver::fromClassmapFile($themeClassmapPath);
    $markup = (new Subtitle($classes, 'A & B'))->render();

    self::assertSame(1, substr_count($markup, 'A &amp; B'), 'exactly one escape');
    self::assertStringContainsString('howdah-subtitle', $markup);
}

public function testAnEmptySubtitleIsAbsence(): void
{
    self::assertNull($this->repository->subtitle($this->postId));
}
```

Then the gate:

```bash
composer check
```

## Where to go next

- **Reading and querying fields** — `mahout-fields`' `docs/getting-started.md`
  carries the read path, the repeater assembly, and the member-qualified
  query over `Table` storage.

- **Storage rules** — [extending.md](./extending.md) and the AGENTS.md
  storage table decide `Meta` vs `Table` for every new field.
- **A new page kind** — [extending.md](./extending.md) §"Adding a dispatch
  arm" for surfaces with their own URL shape.
- **Styling the admin** — the field package styles its controls by default;
  `AGENTS.md §Admin and editor` states the takeover policy.
