# Surfaces reference

Generated from app/Surfaces/Surfaces.php by tests/Support/SurfacesReference.php.
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
                surface: new EmbedContent($context, $content, $classes),
                reason: 'embed document, rendered for one parent request',
            ),
            QueryKind::Front === $context->kind && null !== $context->objectId` | `SinglePage` | `Shared` | `Shared` | `` |
| `wrapped(
                surface: new SinglePage($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Front === $context->kind` | `BlogIndex` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `uncacheable(
                    surface: new BlogIndex($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                :` | `BlogIndex` | `Shared` | `Shared` | `` |
| `wrapped(
                    surface: new BlogIndex($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::indexKey($context),
                    cache: $cache,
                ),
            QueryKind::Home === $context->kind` | `BlogIndex` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `uncacheable(
                    surface: new BlogIndex($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                :` | `BlogIndex` | `Shared` | `Shared` | `` |
| `wrapped(
                    surface: new BlogIndex($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::indexKey($context),
                    cache: $cache,
                ),
            QueryKind::Singular === $context->kind && 'post' === $context->postType` | `SinglePost` | `Shared` | `Shared` | `` |
| `wrapped(
                surface: new SinglePost($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePost::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Singular === $context->kind && 'page' === $context->postType` | `SinglePage` | `Shared` | `Shared` | `` |
| `wrapped(
                surface: new SinglePage($context, $content, $classes),
                cacheability: Cacheability::Shared,
                fragmentScope: FragmentScope::Shared,
                key: FragmentKey::fromParts(
                    SinglePage::class,
                    $context->objectId ?? 0,
                    $context->contentPage(),
                    $context->site->locale,
                ),
                cache: $cache,
            ),
            QueryKind::Archive === $context->kind` | `ContentArchive` | `Uncacheable` | `Never` | `page beyond the content graph, out of range` |
| `uncacheable(
                    surface: new ContentArchive($context, $content, $classes),
                    reason: 'page beyond the content graph, out of range',
                )
                :` | `ContentArchive` | `Shared` | `Shared` | `` |
| `wrapped(
                    surface: new ContentArchive($context, $content, $classes),
                    cacheability: Cacheability::Shared,
                    fragmentScope: FragmentScope::Shared,
                    key: self::archiveKey($context),
                    cache: $cache,
                ),
            QueryKind::Search === $context->kind` | `SearchResults` | `Uncacheable` | `Never` | `free-text term, unbounded key space` |
| `uncacheable(
                surface: new SearchResults($context, $classes, $content, $services->get(Diagnostics::class)),
                reason: 'free-text term, unbounded key space',
            ),
            QueryKind::NotFound === $context->kind` | `NotFound` | `Uncacheable` | `Never` | `a 404 is a statement about the current content graph` |
| `uncacheable(
                surface: new NotFound($context, $classes),
                reason: 'a 404 is a statement about the current content graph',
            ),
            default` | `GenericList` | `Uncacheable` | `Never` | `unmapped request kind` |
