<?php

/**
 * The render boundary. A Surface returns the page's HTML or throws; the
 * boundary catches the throw, records it at critical through Diagnostics,
 * fires howdah/surface/failed, and — in production only — renders the Error
 * Surface with status 500. In development it rethrows: development sees the
 * trace, production sees the defined page. No white screen, no substitute
 * data, no degraded mode.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Support\ClassResolver;
use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Kernel\Diagnostics;
use Iniznet\Mahout\Kernel\Environment;
use Iniznet\Mahout\Kernel\Level;

final readonly class SurfaceErrorBoundary implements Component
{
    public function __construct(
        private Component $inner,
        private QueryContext $ctx,
        private Diagnostics $diagnostics,
        private Environment $environment,
        private ClassResolver $classes,
    ) {
    }

    public function render(): string
    {
        try {
            return $this->inner->render();
        } catch (\Throwable $e) {
            return $this->handle($e);
        }
    }

    private function handle(\Throwable $e): string
    {
        $reference = $this->diagnostics->log(Level::Critical, 'surface failed', [
            'kind' => $this->ctx->kind->name,
            'object_id' => $this->ctx->objectId,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        \do_action(Hooks::SURFACE_FAILED, $e, $reference);

        if ($this->environment->exposesErrors()) {
            throw $e;
        }

        \status_header(500);

        return new ErrorSurface($this->classes, $reference)->render();
    }
}
