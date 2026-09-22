<?php

/**
 * The icon primitive. The set is the theme's own: named paths in a 24x24
 * viewBox, no third-party set. The glyph is decorative by default — a
 * visible label nearby carries the meaning — so it is aria-hidden unless it
 * is asked to name itself.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Components\Primitives;

use Iniznet\Howdah\Exception\IconNotFound;
use Iniznet\Howdah\Render\MarkupComponent;
use Iniznet\Howdah\Support\ClassResolver;

final class Icon extends MarkupComponent
{
    /** The named paths, in a 24x24 viewBox. */
    private const array PATHS = [
        'search' => 'M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 10 5.5 5.5 0 0 1 0-10Z',
        'close' => 'M6.4 5 5 6.4 10.6 12 5 17.6 6.4 19 12 13.4 17.6 19 19 17.6 13.4 12 19 6.4 17.6 5 12 10.6Z',
        'menu' => 'M3 6h18v2H3V6Zm0 5h18v2H3v-2Zm0 5h18v2H3v-2Z',
        'arrow' => 'M12 4 10.6 5.4 16.2 11H4v2h12.2l-5.6 5.6L12 20l8-8Z',
        'check' => 'M9.6 16.2 5.4 12l-1.4 1.4 4.2 4.2 1.4 1.4L20.6 7.8 19.2 6.4Z',
        'warning' => 'M12 2 1 21h22L12 2Zm-1 6h2v7h-2V8Zm1 11.25A1.25 1.25 0 1 1 12 16.75a1.25 1.25 0 0 1 0 2.5Z',
    ];

    public function __construct(
        ClassResolver $classes,
        private readonly string $name,
        private readonly ?string $label = null,
    ) {
        parent::__construct($classes);

        if (!isset(self::PATHS[$name])) {
            throw IconNotFound::because($name);
        }
    }

    #[\Override]
    protected function markupPath(): string
    {
        return __DIR__.'/markup/icon.php';
    }

    #[\Override]
    public function render(): string
    {
        \ob_start();
        $c = $this->classes();
        $path = self::PATHS[$this->name];
        $label = $this->label;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }
}
