<?php

/**
 * Site-level values a render depends on. Not a second request context: the
 * site is a property of QueryContext, read where the query is read.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

final readonly class SiteProfile
{
    public function __construct(
        public string $name,
        public string $description,
        public string $locale,
        public string $scheme,
        public bool $isRtl,
    ) {
    }

    /**
     * The composition-root named constructor: the site facts, read once.
     * It resolves no collaborator.
     */
    public static function fromWordPress(): self
    {
        return new self(
            name: (string) get_bloginfo('name'),
            description: (string) get_bloginfo('description'),
            locale: (string) get_locale(),
            scheme: is_ssl() ? 'https' : 'http',
            isRtl: is_rtl(),
        );
    }
}
