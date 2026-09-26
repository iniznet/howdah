<?php

/**
 * The declared panels' read-only presence on the core list screen: one
 * column per Table-stored field, and one filter dropdown per Choice field
 * whose options are a bounded set an editor can pick from. The column shows
 * what the reader returns, escaped once, and writes nothing; the filter
 * narrows the list through the field query builder's bounded statement, so
 * the listing never scans the custom table directly.
 *
 * This screen is the one place the value table is read row by row, and it is the
 * one place that cannot prime: core hands the column hook a single post id, so no
 * point in this file knows the page's ids. The cost stays bounded by the screen's
 * per-page count rather than by the archive's size, and the screen is behind a
 * capability. A public Surface that reads fields across rows must call
 * `FieldReader::prime()` before mapping; this is not a pattern to copy.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Admin;

use Iniznet\Howdah\Support\Hooks;
use Iniznet\Howdah\Support\Request;
use Iniznet\Mahout\Fields\ChoiceField;
use Iniznet\Mahout\Fields\Contracts\FieldQuery;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\Contracts\Panels;
use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\FieldType;
use Iniznet\Mahout\Fields\ObjectRef;
use Iniznet\Mahout\Fields\Operator;
use Iniznet\Mahout\Fields\StorageTarget;

final readonly class ContentModelColumns
{
    private const string COLUMN_PREFIX = 'howdah_field_';

    private const string FILTER_PREFIX = 'howdah_filter_';

    /** The bounded statement's cap; a list screen never needs more rows than a page can show. */
    private const int FILTER_LIMIT = 1000;

    public function __construct(
        private Panels $panels,
        private FieldReader $reader,
        private FieldQuery $query,
    ) {
    }

    /**
     * One hook triple per declared post type. The dynamic names are core's
     * own list-screen hooks, built here and nowhere else, the same permitted
     * dynamic form the nonce action uses.
     */
    public function register(): void
    {
        foreach ($this->panels as $panel) {
            \add_filter(
                sprintf('manage_%s_posts_columns', $panel->postType),
                fn (array $columns): array => $this->columns($panel, $columns),
                priority: 10,
                accepted_args: 1,
            );

            \add_action(
                sprintf('manage_%s_posts_custom_column', $panel->postType),
                function (string $column, int $postId) use ($panel): void { $this->columnValue($panel, $column, $postId); },
                priority: 10,
                accepted_args: 2,
            );

            \add_action(
                Hooks::RESTRICT_MANAGE_POSTS,
                function (string $postType) use ($panel): void { $this->filterDropdown($panel, $postType); },
                priority: 10,
                accepted_args: 1,
            );

            \add_action(
                Hooks::PRE_GET_POSTS,
                fn (\WP_Query $query): bool => $this->filterQuery($panel, $query),
                priority: 10,
                accepted_args: 1,
            );
        }
    }

    /**
     * One read-only column per Table-stored field, inserted after the title.
     *
     * @param array<array-key, mixed> $columns
     *
     * @return array<array-key, mixed>
     */
    private function columns(FieldPanel $panel, array $columns): array
    {
        $withFields = [];

        foreach ($columns as $key => $title) {
            $withFields[$key] = $title;

            if ('title' === $key) {
                foreach ($this->tableFields($panel) as $field) {
                    $withFields[self::COLUMN_PREFIX.$field->id] = $field->label ?? $field->id;
                }
            }
        }

        return $withFields;
    }

    /**
     * The custom column's body: the reader's value, escaped once. A repeater
     * has no single value; its row reports the item count, which is what a
     * list scan is for.
     */
    private function columnValue(FieldPanel $panel, string $column, int $postId): void
    {
        if (!\str_starts_with($column, self::COLUMN_PREFIX)) {
            return;
        }

        $fieldId = \substr($column, \strlen(self::COLUMN_PREFIX));
        $field = $this->fieldById($panel, $fieldId);

        if (null === $field) {
            return;
        }

        if (FieldType::Repeater === $field->type()) {
            $count = \count($this->reader->items($fieldId, ObjectRef::post($postId)));
            echo \esc_html(sprintf(
                /* translators: %d: the number of stored items. */
                \_n('%d item', '%d items', $count, 'howdah'),
                $count,
            ));

            return;
        }

        $value = $this->reader->value($fieldId, ObjectRef::post($postId));

        if (\is_bool($value)) {
            echo \esc_html($value ? \__('Yes', 'howdah') : \__('No', 'howdah'));

            return;
        }

        echo \esc_html(null === $value ? '' : (string) $value);
    }

    /**
     * One filter dropdown per Choice field. The submitted value re-reads
     * through Request, the one query boundary, so the dropdown never touches
     * a superglobal; the query itself narrows in filterQuery().
     */
    private function filterDropdown(FieldPanel $panel, string $postType): void
    {
        if ($postType !== $panel->postType) {
            return;
        }

        foreach ($this->tableFields($panel) as $field) {
            if (!$field instanceof ChoiceField) {
                continue;
            }

            $selected = Request::fromSuperglobals()->query($this->filterName($field->id));

            echo '<label class="screen-reader-text" for="'.\esc_attr($this->filterName($field->id)).'">'
                .\esc_html($field->label ?? $field->id).'</label>';
            echo '<select name="'.\esc_attr($this->filterName($field->id)).'">';
            echo '<option value="">'.\esc_html__('All', 'howdah').'</option>';

            foreach ($field->options as $option) {
                echo '<option value="'.\esc_attr($option).'"'.\selected((string) $selected, $option, false).'>'
                    .\esc_html($option)
                    .'</option>';
            }

            echo '</select>';
        }
    }

    /**
     * The list query's half of the filter: a declared choice field's
     * submitted value becomes the bounded statement's id list, and an empty
     * id list short-circuits to a knowingly empty result instead of letting
     * the second query run unfiltered.
     */
    private function filterQuery(FieldPanel $panel, \WP_Query $query): bool
    {
        if (!\is_admin() || !$query->is_main_query() || $query->get('post_type') !== $panel->postType) {
            return false;
        }

        foreach ($this->tableFields($panel) as $field) {
            if (!$field instanceof ChoiceField) {
                continue;
            }

            $submitted = Request::fromSuperglobals()->query($this->filterName($field->id));

            if (null === $submitted || '' === $submitted || !\in_array($submitted, $field->options, true)) {
                continue;
            }

            $ids = $this->query->postIds($field->id, Operator::Equals, $submitted, self::FILTER_LIMIT);
            $query->set('post__in', [] === $ids ? [0] : $ids);
        }

        return true;
    }

    private function filterName(string $fieldId): string
    {
        return self::FILTER_PREFIX.\str_replace('-', '_', $fieldId);
    }

    /** @return list<\Iniznet\Mahout\Fields\Field> */
    private function tableFields(FieldPanel $panel): array
    {
        return \array_values(\array_filter(
            $panel->group->fields,
            static fn ($field): bool => StorageTarget::Table === $field->storage,
        ));
    }

    private function fieldById(FieldPanel $panel, string $fieldId): ?\Iniznet\Mahout\Fields\Field
    {
        foreach ($this->tableFields($panel) as $field) {
            if ($field->id === $fieldId) {
                return $field;
            }
        }

        return null;
    }
}
