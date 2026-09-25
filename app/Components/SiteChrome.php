<?php

/**
 * The site chrome: the header and footer every page shares. The shell owns
 * placement; this owns the theme's identity — the wordmark, the primary
 * navigation, the colophon — resolved once per request at the composition
 * root and carried into every Surface's Document.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components;

use Iniznet\Howdah\Support\PrimaryNav;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Fields\ObjectRef;
use Iniznet\Mahout\Render\Component;
use Iniznet\Mahout\Render\SiteProfile;
use Iniznet\Mahout\Ui\ClassResolver;
use Iniznet\Mahout\Ui\Components\Navigation\SiteFooter;
use Iniznet\Mahout\Ui\Components\Navigation\SiteHeader;

final readonly class SiteChrome
{
    public function __construct(
        private ClassResolver $classes,
        private SiteProfile $site,
        private string $homeUrl,
        private string $navigation,
        private readonly ?string $footerNote = null,
    ) {
    }

    /**
     * The composition-root named constructor: the chrome from the request's
     * site facts, plus the display option the colophon renders. The reader
     * arrives resolved; absence falls back to the site description.
     */
    public static function forContext(ClassResolver $classes, SiteProfile $site, FieldReader $fields): self
    {
        $note = $fields->value('footer_note', ObjectRef::option());

        return new self(
            classes: $classes,
            site: $site,
            homeUrl: (string) \home_url('/'),
            navigation: PrimaryNav::primary(),
            footerNote: (null === $note || '' === $note) ? null : (string) $note,
        );
    }

    public function header(): Component
    {
        return new SiteHeader(
            $this->classes,
            $this->site->name,
            $this->homeUrl,
            $this->navigation,
        );
    }

    public function footer(): Component
    {
        return new SiteFooter($this->classes, $this->footerNote ?? $this->site->description);
    }
}
