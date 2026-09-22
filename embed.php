<?php

/**
 * The embed document entry point. A shim of the same shape as index.php —
 * one entry point, one call, no logic. It is not a second composition path:
 * is_embed() requests classify as QueryKind::Embed, and the dispatch table
 * is the only place that decides.
 */

declare(strict_types=1);

echo Iniznet\Howdah\Bootstrap::render();
