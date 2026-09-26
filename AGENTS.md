# AGENTS.md — howdah discipline contract

This file is the working contract for anyone — human or agent — writing code in this repository. It is the complete public contract: every rule a contributor is bound by is stated here in full, and every rule here is enforced by a gate in this repository. The maintainers keep a private, untracked planning corpus in the working checkout; it informs this file and never overrides it — a rule that exists only there does not exist.

**Stack:** WordPress 7.1+ · PHP 8.4+ · Vite (build only) · PHPUnit · PHPStan max · Psalm (taint) · Rector · PHP-CS-Fixer

---

## The six laws

1. **Explicit over implicit.** Every dependency, hook, registration and side effect is greppable from the composition root.
2. **One way to do a thing.** No alternative paths kept "just in case".
3. **Fail fast and loud.** No silent fallback, no degraded mode, no environment-dependent behaviour switches.
4. **Small public surface.** `Contracts` plus a documented handful of concrete classes. Everything else is `@internal`.
5. **Tooling enforces what prose promises.** A gate existing only in a markdown file does not exist.
6. **Layer neutrality.** The theme is correct and complete with no object cache, no page cache and no CDN, and gets faster as each is added — with no configuration change and no code change. It never depends on a layer existing, never caps a site because a layer appeared, and it has no scale mode.

---

## What this is not

**The system is a modular monolith** (ARC-23). One process, one database, one deployable artefact. Module boundaries are enforced by `Contracts`, `@internal` and the architecture rules — never by a network.

Runtime distribution — services, an internal RPC layer, a message broker beyond `wp_cron`, separately deployed module processes — is a non-goal. Seven packages are a publishing and contribution model, not a deployment topology; `NAM-02` (one repository per package) and `ARC-23` (one monolith at runtime) are independent decisions.

Also refused, each for the reason its decision states: MVC (WordPress owns resolution, persistence and no controller concept); facades, service locators and global helpers (untraceable dependencies); reflection-based autowiring (container resolution must be statically traceable); traits as a code-sharing mechanism; the template hierarchy as a render mechanism; third-party PHP runtime libraries (only our own packages, plus analysis tooling); a theme-owned rate limiter, bot filter or consent surface; a Packagist or npm publishing lane. A pull request whose design one of these refusals has already rejected is closed with a pointer to the refusal rather than debated; a contributor who believes the refusal is wrong changes the refusal first, with the rejected alternative and the reason.

---

## Before you write code

Ask these five questions in order:

1. **Where does this go?** Use the layer table below. Do not improvise a new directory.
2. **Will it be queried?** That decides `StorageTarget` at field declaration time.
3. **Which hook emits this?** Hooks come from Providers and Modules only.
4. **Which editor writes this?** A `Table` field is written only through the field panel or the field REST route. A `Meta` field may also be written through `register_post_meta`.
5. **Who may see it?** That decides the Surface's `Cacheability`. A `Shared` Surface carries no nonce and no per-user or per-role value, and a per-user Surface declares how it is bounded.

---

## Layers — where code goes

| Layer | Location | Owns | Must not |
|---|---|---|---|
| Domain | `app/Features/<Name>/` | queries, repositories, schema, hooks, business rules | render HTML |
| Presentation | `app/Components/`, `app/Features/*/Components/` | rendering typed props to HTML | fetch data, touch globals, fire hooks |
| Composition | `app/Render/` | resolving a request to a Surface | contain domain rules |
| Infrastructure | `app/Providers/` | hook attachment, assets, REST, admin | contain domain rules |

```
Providers --> Modules --> Repositories --> Mapper --> Data (DTO)
                              |
                              v
Surfaces --> Components --> Data (DTO)
```

Arrows point one way only. A Component never imports a Repository. A Repository never imports a Component.

### Vertical slices

The starter ships one feature — `app/Features/Series/` — as the worked example: a declared post type and taxonomy (`config/content-types.php`), a field panel (`config/fields.php`), an option screen (`config/display-options.php`), one repository, one Surface and one dispatch arm. Every declaration names itself as deletable: remove the config entries and the feature stops existing. New features take the same shape; the tree below is it.

```
app/Features/Series/
  SeriesModule.php          # registers hooks
  SeriesSchema.php          # CPT, taxonomies, field groups
  SeriesRepository.php      # ONLY file with WP_Query
  SeriesMapper.php          # ONLY file with WP_Post
  SeriesData.php            # readonly DTO
  Surfaces/
  Components/
```

---

## The six-step feature recipe

1. Declare data in `<Feature>Schema.php`, with an explicit `StorageTarget` per field.
2. Register the module — one line in `app/Bootstrap.php`.
3. Write the query in `<Feature>Repository.php`. Prime caches here.
4. Map to a DTO in `<Feature>Mapper.php`.
5. Compose the page with a Surface and Components. Each component gets its own markup file.
6. Test: unit test every component, integration test the repository and the Surface's query ceiling.

---

## Banned — these fail the build

| Banned | Why |
|---|---|
| `extract()` | Variables appear from nowhere |
| `meta_query` in a public API | No `meta_value` index; one join per clause |
| `posts_per_page => -1` | Unbounded cost |
| Raw hook-name strings | Bypasses the `Hooks` constants |
| `get_post_meta()` / `get_user_meta()` on a registered field | Fields are read through the field layer only |
| Components calling repositories | Breaks the layer contract |
| Components referencing `WP_*` types | Components must be testable without WordPress |
| `template_include` routing | WordPress owns resolution |
| Reflection, service locators, facades | Untraceable dependencies |
| Static access to a service — repository, field reader, field query builder, registry, container | A value constructor is not a service locator; inject the collaborator |
| `__get`, `__set`, `__call`, dynamic properties | Invisible to static analysis |
| Trait properties, or `$this->` from a trait calling undeclared members | Concealed dependencies |
| `new \Exception(...)` or a public exception constructor | Untraceable, message drift |
| `error_log()` outside `Diagnostics` | Production noise; one owner for diagnostics output |
| `$_GET` / `$_POST` / `$_REQUEST` / `$_SERVER` / `$_FILES` / `$_COOKIE` outside `Iniznet\Howdah\Support\Request` | No request boundary |
| Inline `<script>` or `<style>` echo | CSP hygiene and cacheability |
| PHPStan baselines, `@phpstan-ignore` without a reason | Hides problems instead of fixing them |
| `mixed` where a union is expressible | Static analysis stops working |
| `START TRANSACTION`, `COMMIT` or `ROLLBACK` outside `mahout-db`'s gateway | One owner for the transaction boundary |
| `wp_cache_flush()`, and any `wp_cache_flush_group()` outside the gated cache service | Core returns `false` and emits `_doing_it_wrong()` when the backend reports no support; the fallback is a salt bump |
| `setcookie()` or `setrawcookie()` | The theme sets no cookie |
| `WP_List_Table` subclasses, and any quick-edit or bulk-edit field write | Neither path can carry the save lifecycle, and neither has a post lock |
| A canonical, `robots` or `description` meta tag, and any hand-set security header | Another party owns those |
| Reflection in production | The fragment key is supplied explicitly; reflection hides the key's contents |
| An arm of the dispatch table that reaches no cacheability terminal, or an `Uncacheable` arm with no stated reason | The builder has no default and no terminal-less path; such an arm fails a test and fails the reference gate |
| A nonce, a per-user value or a per-role value in a `Shared` Surface | A shared cache would replay one visitor's token to another |
| A cache key whose parts are not enumerable from the site's own content graph | A key space an anonymous visitor can invent is a cache an anonymous visitor can fill |
| A `Cache-Control`, `Vary` or validator header set anywhere but the render pipeline's cacheability plan | One policy, one owner, one place: the plan that declares a Surface `Shared` is the only component that knows what a shared cache may be told |
| A vendor purge API call, or any attempt by the theme to purge a page cache or a CDN | The theme owns the seam and the client owns the endpoint |
| A `$wpdb` statement against a howdah table with neither a `LIMIT` nor a primary-key equality | An unbounded statement is a scan |
| A schema query such as `information_schema` on a request path | A schema fact is read once into a non-autoloaded option |
| `LIKE` with a leading wildcard over `post_title`, `post_excerpt` or `post_content` | Measured at 150× to 450× the indexed path |
| `sleep()`, `usleep()`, `set_time_limit()`, or a wait-for-lock loop on a request path | A waiting PHP worker is a worker unavailable to every other request |
| A per-request log line, or query logging, in production | A cost that grows linearly with traffic |
| A second host installing the mahout packages on the same site | One autoloader serves its copies to both hosts while the schema-version option, the field tables and the hook namespace are shared with no owner recorded |

---

## Conventions

### DTOs

`final readonly class`, promoted and fully typed, `list<T>` in docblocks, enums for closed sets, value objects for domain scalars. No `__get`, no `ArrayAccess`, no `toArray()`, no `JsonSerializable`.

Mapping from `WP_Post` happens in a Mapper, never in a DTO and never in a Component.

### Value objects

Enforce invariants with PHP 8.4 property hooks. Use `public private(set)` when a value is externally readable but not externally writable.

### Exceptions

`final`, private constructor, static named constructors, extend the most specific SPL exception, implement the package marker interface, carry typed context getters. Name the condition, not the throw site.

Expected absence returns `?T`. Broken invariants throw.

### Shells and shapes

`final` by default. Interfaces named for the role with no `Interface` suffix. `#[Override]` on every override. Named arguments for optional parameters. No boolean flags.

---

## Storage

> `wp_postmeta` is a load-with-the-entity store, not a query store.

| Need | `StorageTarget` |
|---|---|
| Read with the entity, never filtered | `Meta` |
| Filtered, sorted, aggregated or counted | `Table` |
| Repeater, display-only | `Meta`, one row per leaf, keyed by address |
| Repeater, queried or unbounded | `Table` leaves table, indexed for the member query |
| Option-context field | `Meta` always |

`storage` is required on every field. No default.

A repeater stores no envelope: every leaf is one scalar at its address — the chain of positions and member ids from the root (`tags.2`, `credits.0.role`, `sections.0.blocks.1`). Repeaters nest up to three levels; a member declares `Carried` storage, because the root's target stores every leaf. A queried repeater is the developer's declaration, answered member-qualified (`credits.role`) over the leaves table's index — slower is the developer's choice, not the theme's.

**Limitation:** a `Table` field cannot be bound as a block attribute. Block editor meta binding goes through `register_post_meta`, which only sees meta. If it must live in the editor's meta sidebar, it must be `Meta`. Its write path is the field panel and the field REST route, and nothing else.

**Sensitive values** go in neither target. Constants or environment only.

---

## Admin and editor

- **The admin UI is not optional.** `Table` fields cannot be bound as block attributes, so the field panel and the field REST route are the only write path for `Table` storage.
- `mahout-fields` renders every field control, styles them by default (a scoped stylesheet enqueued only on its own screens), and owns the save lifecycle, the editor registry and the field route. The theme registers screens and renders the shell around them. Taking styling over — globally, or per field through `Contracts\FieldUiPolicy` — removes the default stylesheet and the default classes; no cascade fight.
- Registration is greppable: `Bootstrap.php` names every provider, `AdminProvider` names every menu page, notice and list column the theme owns, and `mahout-fields`' `Admin\FieldsUiProvider` — registered after `FieldsProvider` and driven by the host's `Contracts\Panels` and `Contracts\OptionScreens` bindings — attaches the metaboxes, the save entry, the field route, the write-failure notice and the settings pages the declared panels and screens imply. A theme that declares no panel and no option screen attaches none of them. No admin registration at file scope.
- **The save lifecycle order is fixed.** Autosave guard, revision guard, foreign-form guard, post lock, capability, nonce, field shape, sanitise, store, record. It runs in that order on both the classic and the REST path, because core checks the post lock on the classic path and not on the REST one.
- A nonce failure never calls `wp_die()` inside `save_post`; core has already written the post by then.
- `Meta` fields use `register_post_meta` with `show_in_rest`. `Table` fields are read through `register_rest_field` and written through the field route.
- No `WP_List_Table` subclass, and no quick-edit or bulk-edit field write.
- No admin screen without a capability, and no capability compared by role.

---

## Data integrity

- **The table rows and the revision mirror are one transaction.** `START TRANSACTION`, `COMMIT` and `ROLLBACK` appear only inside `mahout-db`'s gateway, because `$wpdb` has no transaction API.
- Every howdah table declares `ENGINE=InnoDB`. A non-transactional engine makes the boundary decorative, and `get_charset_collate()` does not set the engine.
- The gateway never nests a transaction. A plugin must not open one around a field save.
- The lost-update guard is the post lock plus an `expected_hash` comparison inside the transaction. Nothing is merged automatically.
- **A failed field write rolls back everything and notifies.** Nothing is partially applied, nothing is substituted, nothing is retried, and the post is not compensated.
- Every migration implements `up()` and `down()`. An irreversible reversal throws and blocks the whole rollback run before any statement executes.
- Migrations run from `wp mahout migrate`, from `after_switch_theme`, or lazily on `admin_init` for a user with `manage_options`. Never on the front end, never under AJAX or cron.
- The revision mirror is a registered meta key with `revisions_enabled => true`; core copies and restores it, and the field layer rehydrates the table afterwards.

---

## Hooks

Names are `public const` on a `Hooks` class. Never inline.

```
mahout/{package}/{event}      # library
howdah/{domain}/{event}       # theme
```

- Actions never return. Filters always return the first argument.
- Filters pass values and arrays, never mutable WordPress objects. Pass `QueryContext`, not `$wp_query`. Pass `SeriesData`, not `WP_Post`.
- Emit only from Providers and Modules.
- `wp_head` and `wp_footer` fire inside the `Document` component. Do not remove them.

| Priority | Meaning |
|---|---|
| 5 | pre-empt |
| 10 | default |
| 20 | post-process |
| `PHP_INT_MAX` | enforcement |

A new hook is documented in the same change that introduces it. `composer hooks:check` fails if the generated reference is stale.

---

## Cacheability and throughput

- **Every Surface declares its cacheability.** An arm of the dispatch table reaches one terminal of `mahout-render`'s `SurfacePlanBuilder`: `shared()` is `Cacheability::Shared` over `FragmentScope::Shared`, `uncacheable()` is `Uncacheable` over `Never` and carries the reason. `guardOverflow()` is the listing arms' second path — the same Surface, `Uncacheable` and stated beyond the last page the content graph holds. There is no default and no way to reach a plan without naming the pair. A per-request class that is not the Shared pair is written with `SurfacePlan::wrapped()`, which names class and scope as arguments.
- **No layer is required, and no layer changes the code.** Correct at layer 0, faster at layer 5, one path.
- **The theme owns the purge seam; the client owns the endpoint.** Invalidation emits `howdah/cache/purge`; no vendor API is called.
- **Every statement is bounded.** A `LIMIT` or a primary-key equality, always. A sweep is chunked by key, resumable and runtime-capped, and never runs on a request path.
- **Search is an index, not a scan.** The `FULLTEXT` index on `wp_posts`, the tokeniser, the `MATCH` clause, the two core filters and the loud fallback report are all owned by `mahout-db` (`Search\SearchTerms`, `Search\IndexedSearchSwap`, `Search\SearchProvider`). The theme's repository states the search intent and takes the query args from the swap; it declares no search grammar of its own.

---

## Performance

Required, not optional:

```php
update_meta_cache('post', $ids);
update_object_term_cache($ids, $taxonomies);
$fields->prime($refs);
```

Before mapping any result set. Then one `get_post_meta($id)` per post, not one per field — and the same for the field layer, whose `prime()` files a page's `Table`-stored rows in one statement per kind. Without it the storage target would decide how many statements a page costs, which is exactly the leak the field layer exists to close.

Repository query defaults:

```php
'no_found_rows'          => true,   // unless paginating
'update_post_meta_cache' => false,  // we prime ourselves
'update_post_term_cache' => false,  // we prime ourselves
'ignore_sticky_posts'    => true,
'fields'                 => 'ids',  // when only IDs are needed
```

Every new Surface gets a query-ceiling test. Options over ~1 KB are stored `autoload='no'`.

Production prerequisites, asserted by `doctor` and not optional for the declared capacity to hold: OPcache enabled with `validate_timestamps=0` and a preload file, an autoloader generated with `--classmap-authoritative --optimize`, and an `innodb_buffer_pool_size` sized to the working set. None of them is required for correctness; all of them are required for the theme's declared capacity numbers to hold.

`doctor` also counts the hosts that install the packages — every `wp-content/{plugins,mu-plugins,themes}/*/vendor/iniznet/mahout-*` — and fails when a site contains more than one. The runtime backstop is the kernel's process claim (mahout-kernel ADR-0007): `Kernel::inWordPress()` names the root that owns the process and refuses a second, so the collision cannot arrive quietly. This is not a style preference. Because Composer prepends each host's autoloader and WordPress loads the theme after the plugins, the theme's pinned copies of the shared classes run for both hosts, and the plugin's never run at all.

`doctor` measures rather than recites: it counts the PHP files this installation can put on a request path and compares that with `opcache.max_accelerated_files`, reads the pool size and the site's own table statistics, inspects the installed classmap in a child process, and parses and preloads `preload.php` as far as the platform allows. A prerequisite wrong in every mode fails; the production-only ones are judged against the site's declared `WP_ENVIRONMENT_TYPE`, and an installation that declares nothing is told that those assertions are not being made; a development box gets a warning with its remedy; a check that cannot apply to this root is reported skipped. `vendor/bin/mahout-devtools load:probe --url=… --concurrency=… --requests=…` measures the running site and fails only when a request does not come back — absolute latency is a number to read, never a gate to game.

---

## Static access

**Permitted:** named constructors and codecs that hold no state and resolve no collaborator — `Slug::fromString()`, `Media::fromAttachmentId()`, `SeriesStatus::from()`, `CreditsCodec::encode()`, `PercentageOutOfRange::fromInput()` — plus enums and `*::class` constants.

**Banned:** static access to anything that queries, caches, mutates or resolves a collaborator — repositories, field readers, field query builders, registries, containers.

**Boundary and composition-root exceptions, and nothing else:** `Request::fromSuperglobals()`, `Bootstrap::run()`, `Bootstrap::render()`, `Bootstrap::services()`, `Surfaces::resolve()`. The test is not "is it static" but "does it resolve a collaborator".

---

## Errors and security

- **Failures are loud; output is defined.** A thrown exception is recorded through `Diagnostics` at `critical`. Development rethrows it. Production renders the Error Surface with status `500`. No silent fallback, no substituted data, no white screen. The error boundary is a defined render, not a degraded mode — law 3 still governs data and behaviour.
- **Request input has one boundary.** Superglobals are read only inside `Iniznet\Howdah\Support\Request`, which is injected through constructors. State-changing requests use POST, a verified nonce, and a capability check. A REST route without an explicit `permission_callback` fails the build.
- **Sanitize on write, escape on read.** Exactly one escape per output; double escaping is a defect, not defensiveness.
- **User data does not live in a theme-owned table.** Anything that must survive a theme switch belongs in a plugin.

---

## Gates

```bash
composer format     # PHP-CS-Fixer, @PSR12 + @Symfony
composer stan       # PHPStan, max level, no baseline
composer psalm      # Psalm taint
composer arch       # the architecture rules, carried by the shared analysis composer stan runs
composer rector     # Rector dry-run
composer test       # PHPUnit
composer hooks:check
composer i18n:check # generated POT is current
composer doctor     # installation assembly
composer config:check # divergence: analyzer config, architecture rules and the gate set are referenced, not copied
composer check      # all of the above
```

`composer check` must pass before every commit. No exceptions, no `--no-verify`.

---

## Contributing

Seven package repositories and one theme. Route a change by its **kind**, not by file path: a change to how a package behaves goes to that package's repository; a change that must touch two repositories at once is a contract change (below); a change to a rule on this page changes this page in the same commit.

- A change that cannot be made in one repository is a **contract change**. It follows the procedure in 19 §4: add the new surface, deprecate the old one, tag a package minor, adopt in consumers in dependency order (kernel, then assets, db and content, then fields, then the theme), and remove the old surface only at the next major.
- `Contracts/` is a public promise (19 §7). Semantic versioning governs it; an `@internal` class may change in a patch release.
- A new decision is recorded in the same change that introduces it: the rule lands here, with the rejected alternative and the reason, before or with the code that implements it. A decision that exists only in conversation does not exist.
- A fork pull request runs the same `composer check`, because `mahout-devtools` resolves over VCS with no secret. The quality workflow is triggered by `pull_request`, never `pull_request_target` (19 §5).
- Every repository references the analyzer configuration and architecture rules from `mahout-devtools`; a repository carrying its own copy has diverged (19 §6).

---

## Required tests

| Must have a test |
|---|
| Every component's rendered output |
| Every value object's invariant, including rejection |
| Every exception's named constructor |
| Every repository query shape |
| Field round-trip on `Meta` and on `Table` |
| `meta` to `table` and `table` to `meta` migration |
| Every Surface's query ceiling |
| Every dispatch arm's declared `Cacheability` and `FragmentScope`, and a stated reason on every `Uncacheable` arm |
| One composition root per process: a second root is refused, naming both |
| The layer-0 suite: every front-end test passing with no object cache, no page cache and no CDN |
| Byte-identical output for two anonymous visitors on a `Shared` Surface |
| Single-flight: concurrent misses on one key cause exactly one regeneration |
| The orphan sweep's constant statement count per chunk and strictly advancing cursor |
| The search index's presence and exact column list, and result-set parity between the indexed and core paths |
| A malformed search term never reaching `AGAINST` |
| `doctor` failing on opcache off, a missing preload file, or a non-authoritative autoloader |
| Index presence after migration |
| Zero orphans after a post delete |
| A Surface that throws produces the error Surface in production and rethrows in development |
| A user-scoped field's export and erase paths |
| Contrast of every declared `theme.json` token pairing |

No coverage target. Coverage rewards testing getters.

---

## Simplicity rules

- If a class has one public method and one responsibility, that is correct, not a smell.
- Four repeated lines of `render()` boilerplate are cheaper than a trait that breaks `__DIR__`.
- If `functions.php` grows past eight lines, the design is wrong.
- If `Bootstrap.php` needs a config file, the indirection is wrong.
- If you need `@phpstan-ignore`, you need a different design.
- If a name needs explaining at the call site, name it better.

---

## Scope of this document

This file is the repository's complete public contract. The maintainers keep a
private planning corpus in the working checkout — architecture rationale,
rejected alternatives, delivery sequencing, capacity economics — deliberately
untracked: a consumer of the code reads the rules, not the reasoning behind
them. Nothing in that corpus weakens a rule stated here; a rule that is not
stated here is not a rule.
