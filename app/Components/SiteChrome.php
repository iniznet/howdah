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
    ) {
    }

    /** The composition-root named constructor: the chrome from the request's site facts. */
    public static function forContext(ClassResolver $classes, SiteProfile $site): self
    {
        return new self(
            classes: $classes,
            site: $site,
            homeUrl: (string) \home_url('/'),
            navigation: PrimaryNav::primary(),
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
        return new SiteFooter($this->classes, $this->site->description);
    }
}
