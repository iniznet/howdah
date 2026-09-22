<?php

/**
 * The save boundary's request adapter: the one reader of the submitted field
 * panel. It implements the field package's RequestInput contract, so the
 * classic save_post path and the REST route share one shape and the field
 * package never reads a superglobal itself. The HTTP surface's reader is
 * Support\Request; this is its sibling for the admin form boundary, and the
 * two are the only superglobal readers in the codebase.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

use Iniznet\Mahout\Fields\Admin\Nonces;
use Iniznet\Mahout\Fields\Contracts\RequestInput;

final readonly class PanelRequest implements RequestInput
{
    /**
     * @param array<string, mixed> $post the unslashed POST body
     */
    private function __construct(private array $post)
    {
    }

    /**
     * The submitted body, already unslashed by the one reader --
     * Support\Request::panel() -- which is the only caller in production;
     * tests and WP-CLI callers build the map directly.
     *
     * @param array<string, mixed> $post
     */
    public static function fromArray(array $post): self
    {
        return new self($post);
    }

    #[\Override]
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->post);
    }

    #[\Override]
    public function string(string $key): ?string
    {
        $value = $this->post[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    #[\Override]
    public function groups(): array
    {
        return $this->arraysOf(Nonces::valueField());
    }

    /**
     * One named value out of a submitted two-level form field -- the admin
     * settings form's field, whose keys are option ids rather than group
     * ids. The theme's own forms read through here; the field package's save
     * boundary keeps reading through the contract's methods.
     */
    public function value(string $field, string $key): ?string
    {
        $raw = $this->post[$field] ?? null;

        if (!\is_array($raw)) {
            return null;
        }

        $value = $raw[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    #[\Override]
    public function hashes(): array
    {
        $hashes = [];

        foreach ($this->arraysOf(Nonces::hashField()) as $groupId => $fields) {
            $value = $fields['hash'] ?? null;
            $hashes[$groupId] = \is_string($value) ? $value : '';
        }

        return $hashes;
    }

    /**
     * The named array field's two-level map, coerced to the contract's shape:
     * a group key must be a string and its payload an array; each value
     * becomes a string, a list of strings or null, and anything else is
     * dropped. The field sanitiser, not this adapter, decides what a value
     * means.
     *
     * @return array<string, array<string, string|list<string>|null>>
     */
    private function arraysOf(string $field): array
    {
        $raw = $this->post[$field] ?? null;

        if (!\is_array($raw)) {
            return [];
        }

        $mapped = [];

        foreach ($raw as $groupId => $fields) {
            if (!\is_string($groupId) || !\is_array($fields)) {
                continue;
            }

            $values = [];

            foreach ($fields as $fieldId => $value) {
                if (!\is_string($fieldId)) {
                    continue;
                }

                $values[$fieldId] = match (true) {
                    \is_string($value) => $value,
                    \is_array($value) => \array_values(\array_filter($value, \is_string(...))),
                    default => null,
                };
            }

            $mapped[$groupId] = $values;
        }

        return $mapped;
    }
}
