# Surfaces reference

Generated from app/Surfaces/Surfaces.php by tests/Support/SurfacesReference.php.
One row per plan a dispatch arm reaches: the Surface that renders the request, its
declared cacheability class, its fragment scope and, for an Uncacheable plan, the
reason it is not stored. The plan is the arm's terminal — shared(), uncacheable()
or a guardOverflow() that ends in shared() — and an arm that reaches none fails a
test and fails this reference gate.

Regenerate with MAHOUT_SURFACES_REGENERATE=1 vendor/bin/phpunit --filter SurfacesReferenceTest.

| Arm | Surface | Cacheability | Fragment scope | Reason |
|---|---|---|---|---|
| `QueryKind::Embed === $context->kind` | `EmbedContent` | `Uncacheable` | `Never` | `embed document, rendered for one parent request` |
| `QueryKind::Front === $context->kind && null !== $context->objectId` | `SinglePage` | `Shared` | `Shared` | `` |
| `QueryKind::Front === $context->kind` | `BlogIndex` | `Shared` | `Shared` | `` |
| `QueryKind::Front === $context->kind (out of range)` | `BlogIndex` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `QueryKind::Home === $context->kind` | `BlogIndex` | `Shared` | `Shared` | `` |
| `QueryKind::Home === $context->kind (out of range)` | `BlogIndex` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `QueryKind::Singular === $context->kind && 'post' === $context->postType` | `SinglePost` | `Shared` | `Shared` | `` |
| `QueryKind::Singular === $context->kind && 'page' === $context->postType` | `SinglePage` | `Shared` | `Shared` | `` |
| `QueryKind::Archive === $context->kind && 'howdah_series' === $context->postType` | `SeriesArchive` | `Shared` | `Shared` | `` |
| `QueryKind::Archive === $context->kind && 'howdah_series' === $context->postType (out of range)` | `SeriesArchive` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `QueryKind::Archive === $context->kind` | `ContentArchive` | `Shared` | `Shared` | `` |
| `QueryKind::Archive === $context->kind (out of range)` | `ContentArchive` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `QueryKind::Search === $context->kind` | `SearchResults` | `Uncacheable` | `Never` | `free-text term, unbounded key space` |
| `QueryKind::NotFound === $context->kind` | `NotFound` | `Uncacheable` | `Never` | `a 404 is a statement about the current content graph` |
| `default` | `GenericList` | `Uncacheable` | `Never` | `unmapped request kind` |
