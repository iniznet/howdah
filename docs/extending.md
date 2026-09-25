# Extending

## Adding a feature

A feature is a vertical slice under `app/Features/<Name>/` with a fixed shape —
the shipped Series example is it:

```
config/content-types.php   # the declared post types and taxonomies
config/fields.php          # the declared field panels, storage target per field
config/display-options.php # the declared option screens
app/Features/Series/
  SeriesModule.php          # declares the repository into the container
  SeriesRepository.php      # composes a QuerySpec per intent — the only query lane
  SeriesMapper.php          # the only file with WP_Post
  SeriesData.php  SeriesPage.php
  Surfaces/  Components/
```

1. Declare the content model and the fields in `config/` — the field package's
   admin surfaces derive from those lists and from nothing else.
2. Register the module — one line in `app/Bootstrap.php`.
3. Write the query in `<Feature>Repository.php`, composing a `QuerySpec` per
   intent against the shared `PostReader`; the mechanics stay in
   `mahout-content`.
4. Map to a DTO in `<Feature>Mapper.php`.
5. Compose the page with a Surface and Components; each component gets its own
   markup file, and the Surface takes one dispatch arm with its declared
   cacheability.
6. Test: unit test every component, integration test the repository and the
   Surface's query ceiling.

## Declaring an option screen

`config/display-options.php` declares whole settings pages. The shipped Display
screen is the worked example: two tabs, the footer note's fields inside the
first tab's field section, and the guide — a markup file under
`app/Admin/markup/` the section names — inside the second. A screen whose
tabs carry no field group renders no form: documentation, information and
guides are screens like any other, and the field package's
`Admin\OptionScreenManager` derives the page, its tabs, its save entry and
its notice from the declaration. The full rules — one content shape per
screen, the option context, the save per active tab — are in mahout-fields'
getting-started, "Option screens".

## Adding a dispatch arm

A new request kind is one arm in `Surfaces::resolve()`, written with `mahout-render`'s `SurfacePlanBuilder`: `->surface(static fn (): Component => new MySurface(...))` then one terminal. `shared($key)` declares the Shared pair, `uncacheable($reason)` declares the pair that stores nothing and must say why, and `guardOverflow($reason)->shared($key)` is a listing arm's second path — the same Surface, uncacheable and stated, beyond the last page the content graph holds. A class other than `Shared` is an arm that names its `Cacheability` and its `FragmentScope` as arguments to `SurfacePlan::wrapped()`. The dispatch-audit test and the surfaces reference both fail when an arm reaches no terminal, so an arm cannot ship half-declared.

## Adding a hook

Hook names are `public const` on `Hooks` — never inline strings. A new hook is documented first, then added to the inventory; the hook reference gate fails when the generated document is stale. Actions never return; filters always return the first argument. Emit only from Providers and Modules.

## Writing a component

Components render typed props to HTML and nothing else: no data fetching, no globals, no hooks, no `WP_*` types. Each component owns one markup file. Escape exactly once per output.

## Tests a change must add

Every component's rendered output. Every value object's invariant, including rejection. Every repository query shape. Every Surface's query ceiling. Byte-identical output for two anonymous visitors on a shared Surface. If `composer check` passes without a new test, the change was smaller than you think.
