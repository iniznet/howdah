# Surfaces reference

Generated from app/Render/Surfaces.php by tests/Support/SurfacesReference.php.
One row per dispatch arm: the Surface that renders the request, its declared
cacheability class, its fragment scope and, for an Uncacheable arm, the
reason it is not stored. A dispatch arm with no declaration fails a test and
fails this reference gate.

Regenerate with MAHOUT_SURFACES_REGENERATE=1 vendor/bin/phpunit --filter SurfacesReferenceTest.

| Arm | Surface | Cacheability | Fragment scope | Reason |
|---|---|---|---|---|
| `match (true) {
            QueryKind::Embed === $context->kind` | `EmbedContent` | `Uncacheable` | `Never` | `embed document, rendered for one parent request` |
| `uncacheable(
                surface: new EmbedContent($context),
                reason: 'embed document, rendered for one parent request',
            ),
            QueryKind::Search === $context->kind` | `SearchResults` | `Uncacheable` | `Never` | `free-text term, unbounded key space` |
| `uncacheable(
                surface: new SearchResults($context, $classes),
                reason: 'free-text term, unbounded key space',
            ),
            QueryKind::NotFound === $context->kind` | `NotFound` | `Uncacheable` | `Never` | `a 404 is a statement about the current content graph` |
| `uncacheable(
                surface: new NotFound($context, $classes),
                reason: 'a 404 is a statement about the current content graph',
            ),
            default` | `GenericList` | `Uncacheable` | `Never` | `unmapped request kind` |
