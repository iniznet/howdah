# Contracts

A contract is a promise the theme makes to code outside itself. The theme has exactly two kinds.

## The theme's own public promise

The theme composes seven packages; each package's `Contracts/` directory is its public surface, versioned semantically. Everything else in a package is `@internal`.

The theme's own promise is small and greppable:

| Contract | Where | Promise |
|---|---|---|
| `Hooks` | `app/Support/Hooks.php` | Every hook name the theme emits or reads, as a `public const`. No inline hook-name strings anywhere in the tree. |
| `Surfaces::resolve()` | `app/Render/Surfaces.php` | A request resolves to exactly one `SurfacePlan`. A filter may replace the plan, never the contract. |
| `SurfacePlan` | `app/Render/SurfacePlan.php` | A plan always names its Surface, its `Cacheability` and its `FragmentScope`; an uncacheable plan carries its reason. |
| `QueryContext` | `app/Render/QueryContext.php` | The request's shape, resolved once, passed by value — filters receive it, never a mutable WordPress object. |
| Theme exceptions | `app/Exception/` | Every thrown condition is `final`, implements the package marker, and is built only through named constructors. |

## The reference documents

Two documents are generated from the live tree and enforced by the suite:

- `docs/reference/surfaces.md` — one row per dispatch arm: Surface, cacheability, fragment scope, reason.
- `docs/reference/hooks.md` — one row per hook: name, hook, kind, arguments, since, purpose.

A committed copy that drifts from the code fails the test suite. Both are regenerated with the flags documented in [getting-started.md](getting-started.md).

## Changing a contract

A change that cannot be made inside one package is a contract change: add the new surface, deprecate the old one, tag a package minor, adopt in consumers in dependency order, and remove the old surface only at the next major. `Contracts/` is a semantic-versioning promise; an `@internal` class may change in a patch. The working contract every change must satisfy is `AGENTS.md` at the repository root.
