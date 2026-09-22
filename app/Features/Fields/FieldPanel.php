<?php

/**
 * One field panel: a declared field group bound to the post type whose edit
 * screen renders it. The field package's FieldGroup carries no post type --
 * the pairing is the host's declaration, because the same group may serve
 * several post types and core registers one metabox per pair.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Fields;

use Iniznet\Howdah\Exception\InvalidFieldDeclaration;
use Iniznet\Mahout\Fields\FieldGroup;

final readonly class FieldPanel
{
    public function __construct(
        public string $postType,
        public FieldGroup $group,
    ) {
        if ('' === $postType) {
            throw InvalidFieldDeclaration::forEmptyPostType();
        }
    }
}
