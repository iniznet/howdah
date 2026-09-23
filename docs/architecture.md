# Architecture

The theme is a modular monolith: one process, one database, one deployable artefact. Module boundaries are enforced by contracts, `@internal` markers and the architecture rules — never by a network.

## Layers

| Layer | Location | Owns | Must not |
|---|---|---|---|
| Domain | `app/Features/<Name>/` | queries, repositories, schema, business rules | render HTML |
| Presentation | `app/Components/`, `app/Features/*/Components/` | rendering typed props to HTML | fetch data, touch globals, fire hooks |
| Composition | `app/Render/` | resolving a request to a Surface | contain domain rules |
| Infrastructure | `app/Providers/` | hook attachment, assets, REST, admin | contain domain rules |

Arrows point one way only. Data flows `Providers → Modules → Repositories → Mapper → Data`, then `Surfaces → Components → Data`. A Component never imports a Repository; a Repository never imports a Component.

## The composition root

`app/Bootstrap.php` is the composition root and the only file that wires the theme together. `Bootstrap::run()` boots the provider chain; `Bootstrap::render()` resolves the request and returns the page. `index.php` is one line: `echo Iniznet\Howdah\Bootstrap::render();`.

## The render pipeline

1. **Resolve.** `Surfaces::resolve()` builds a `QueryContext` — the request's shape as a value object — and dispatches on it. The table has one arm per request kind; a filter may swap the plan, and a non-plan result is refused loudly.
2. **Declare.** An arm reaches one terminal of the plan builder: `shared()` names `Cacheability::Shared` over `FragmentScope::Shared`, `uncacheable()` names the pair that stores nothing and carries the reason, and `guardOverflow()` gives a listing arm its second path — the same Surface, uncacheable and stated, beyond the last page the content graph holds. A class that is not the Shared pair is written with `SurfacePlan::wrapped()`, which names its class and its scope as arguments. There is no default and no inferred case.
3. **Render.** The plan's Surface renders through the `Document` component — one shell per request, `wp_head` and `wp_footer` inside it. Cacheable output passes through `CachedFragment`, keyed by a `FragmentKey` whose parts come only from the site's own content graph.
4. **Head.** `mahout-render`'s `Cache\HeaderPolicy` derives the request's cache and validator headers from the plan's declaration, `Cache\ResponseHeaders` narrows core's payload and merges the policy over it, and `Cache\ConditionalGet` answers a matching `If-None-Match` with a `304`. The merged map is delivered through core's `wp_headers` filter and refused if a subscriber hands back anything but the documented shape. What the theme keeps is the record: a derivation the request state reduced is logged in development, because a downgrade nobody can see is a silent fallback.

Failure is defined: a Surface that throws is recorded through diagnostics, and production renders the error Surface with a `500` — never a white screen, never a silent fallback.

## The working contract

The complete rules — storage targets, the save lifecycle, hooks, cacheability, what fails the build — are in `AGENTS.md` at the repository root. It is the binding contract for every change to this tree.
