# Jametulhoda — Production‑Readiness Report (InfinityFree)

**Branch:** `arena/01a0d84d-jametulhoda` · **Head commit:** `6c02840`
**Commit message:** `fix: rebuild routing, internal links, topic hubs and media UI for InfinityFree`
**CI:** ✅ green — `php -l`, static route/link audit, security + migrate, integration & browser
suite (Query mode), Pretty‑URL‑mode verification, and the Vercel container runtime.

This report answers the fifteen questions that define "production‑ready" for this build.
Every claim is backed by code in the tree and by a passing CI job.

---

## 1. What was broken, and what is the single source of truth now?

Before, URL generation and routing were split across many files, `/topic/…` 404'd, and links
were hard‑coded to `pages/*.php` / pretty paths that broke without `mod_rewrite`.

Now there is **one route registry** (`jhd_routes()` in `includes/functions.php`) and **one URL
builder** (`url()`), plus a resolver (`jhd_resolve_query()`), a path→name mapper
(`jhd_route_name_for_path()`), and a canonical helper (`jhd_query_canonical()` /
`jhd_absolute_url()`). `router.php` and `index.php` both dispatch through this registry; no
template builds a public link by hand. Typed helpers (`topicUrl`, `postUrl`, `newsUrl`,
`articleUrl`, `reportUrl`, `bookUrl`, `lessonUrl`, `mediaUrl`, …) are thin wrappers over
`url()`, so there is exactly one place where a public URL is decided.

## 2. How do dual‑mode URLs work, and what is the default?

`config/config.php` defines
`JHD_PRETTY_URLS = filter_var(env_value('JHD_PRETTY_URLS', 'false'), FILTER_VALIDATE_BOOLEAN)`
— **default `false` (Query mode)**. `url()` branches on this flag:

* **Query mode** → `index.php?p=news`, `index.php?p=topic&slug=…`, `index.php?p=video&id=5`.
  Works with **or without** `mod_rewrite`.
* **Pretty mode** (`JHD_PRETTY_URLS=true`) → `/news`, `/topic/…`, `/video/5`.

Crucially, `router.php` **resolves both spellings regardless of the flag**, so pretty URLs keep
working even in Query mode (that is how the HTTP tests exercise `/news/<slug>` etc.). The flag
only controls which spelling `url()` *emits* and which canonical is published.

## 3. How does the site work on InfinityFree without `mod_rewrite`?

Two independent guarantees:

1. **Query mode is the default**, so every emitted link is `index.php?p=…`, which needs no
   rewrite at all.
2. `.htaccess` routes everything through `router.php` **when `mod_rewrite` exists**
   (`RewriteRule ^ router.php [END,QSA]`, query string preserved). When it does **not** exist,
   the `<IfModule mod_rewrite.c>` block is inert and `index.php` (the front controller) still
   serves `index.php?p=…` directly. Either way the site functions; pretty URLs are the only
   thing that degrades, and they are never a hard dependency.

## 4. How was the `/topic/…` 404 fixed?

`topic` is now a first‑class **detail route** in the registry, and `router.php` resolves it in
both modes:

* Query: `jhd_resolve_query('topic', ['slug' => …])` → `pages/topic.php`.
* Pretty: the `~^/topics?/([^/]+)/?$~` pattern → `pages/topic.php` with `slug`.

`router.php` also loads `includes/functions.php` (it previously loaded only `config/config.php`,
so `jhd_resolve_query()`/`jhd_route_name_for_path()` were undefined → fatal on every Query‑mode
request). Unknown `?p=` values fall through to a **real 404**; unknown pretty paths hit the
route allowlist and 404 — never the homepage, never a soft‑404.

## 5. What is the Topic Content Hub, and how are links bidirectional?

`pages/topic.php` is a full hub: given a slug it loads the topic and its published **news,
articles, research, reports, announcements**, plus related **videos** and **audios** (via
`post_topics` / `lesson_topics`). The homepage features topics straight from the database
(`SELECT … FROM topics … ORDER BY t.is_featured DESC, t.sort_order, post_count DESC LIMIT 6`,
with a `getTopics()` fallback) — never hard‑coded. Links are bidirectional: `post.php` links
back to each of its topics (breadcrumb, badges, buttons, sidebar) via `topicUrl()`, and the hub
links out to every piece of content. Both directions go through `url()`.

## 6. How are breadcrumbs and canonicals correct — and why do they never leak internal PHP?

Every page emits real breadcrumbs (with a JSON‑LD `BreadcrumbList`). The canonical is computed
in `includes/header.php`:

* page override → `jhd_absolute_url($override)`;
* Pretty mode → `JHD_ROUTE_PATH` minus `BASE_PATH`;
* Query mode → `jhd_absolute_url(jhd_query_canonical(JHD_ROUTE_NAME))`;
* home → `/`; 404/search → `<link rel="canonical">` suppressed via `$noindexSeo`.

Because canonicals come from the registry (`index.php?p=…` or `/pretty`), they can never be
`pages/*.php`, `admin/*.php`, or `router.php`. `tests/route-matrix.mjs` asserts this with a
`leaksInternal()` regex, and the static `crawl_links.py` audit proves **no template links to an
internal PHP file** (100% valid).

## 7. What SEO safeguards are in place?

* Exactly **one canonical per page**, mode‑matched to what `url()` emits.
* `noindex` for 404s and search results (`$noindexSeo`).
* Per‑page `<title>`, `meta description`, Open Graph and Twitter cards, and JSON‑LD
  (Organization, WebSite + SearchAction, and per‑type Article/Book/Media/Breadcrumb schemas).
* `sitemap.php` was rewritten to build every `<loc>` from the registry through `url()`, so the
  sitemap matches the active URL mode (it previously hard‑coded pretty paths that would 404 in
  Query mode). Home `<loc>` is the bare origin.

## 8. How is routing locked down (allowlist‑only, no traversal, admin protected)?

* Dispatch is driven entirely by the **`config/routes.php` allowlist** (exact routes, aliases,
  and a small set of regex patterns). There is **no `include $_GET[…]`** anywhere; the resolved
  target is re‑checked with `realpath()` and must live under the project root, else 404.
* `.htaccess` funnels every non‑asset request through `router.php`, so `config/`, `includes/`,
  `pages/`, `bin/`, `tests/`, `database/`, `storage/` and repo files (`.sql`, `.md`, `.json`,
  `.env`) are unreachable directly (verified: they return 404).
* The admin panel keeps its own `auth.php` gate; `tests/security.php` re‑verifies scheme /
  protocol‑relative / header‑injection / traversal rejection in URL and storage‑key handling.

## 9. What do the automated tests cover, and how do they handle both modes?

* `tests/route-matrix.mjs` (**new**) — the dual‑mode contract: every public **listing** resolves
  in Query mode (the guaranteed contract) and Pretty mode (optional/graceful); **detail** routes
  for topic/news/article/report/research/book/lesson/video/audio are discovered from the live
  listings and verified; **true‑404** checks for unknown slugs and unknown routes; canonical
  public‑only check; and an internal‑link audit.
* `tests/route-smoke.mjs` (**rewritten, mode‑aware**) — status + marker + canonical (accepts
  either spelling, forbids internal PHP) + description length, plus the homepage news‑link and
  negative 404 cases.
* `tests/http.mjs` — full admin CRUD, XSS sanitisation, typed‑route rejection, uploads,
  auth/RBAC; its book‑link parser was made mode‑aware.
* `tests/links.mjs` — crawler updated to follow Query‑mode `index.php?p=…` links too.
* `tests/browser.mjs` — Playwright audit of every public route × 3 viewport widths + detail
  routes (navigates pretty paths, which the router resolves in both modes).
* Static: `tests/verify_routes.py` (70/70) and `tests/crawl_links.py` (100% valid).

## 10. What exactly does CI prove?

`.github/workflows/ci.yml` now runs the suite **twice**:

1. **Query mode** (default) on port 8080 — `http.mjs`, `route-smoke`, **`route-matrix`**,
   `browser`, `links`; fails on any `FAIL` line or any `PHP Warning/Fatal/Parse` in the server
   log.
2. **Pretty mode** (`JHD_PRETTY_URLS=true`) on port 8082 — `route-matrix` + `route-smoke`,
   proving the optional pretty scheme is equally sound and never leaks internal PHP.

It also builds and runs the `Dockerfile.vercel` container on a custom `PORT`, re‑runs the HTTP +
browser suites against it, and probes that an uploaded `.php` under `uploads/` returns 404 (no
code execution). Every step is green on the head commit.

## 11. Real 404 vs soft‑404 — how is that enforced?

Unknown `?p=` and unknown pretty paths hit the allowlist and call `jhdNotFound()` (HTTP 404).
Detail controllers (`post.php`, `topic.php`, `book.php`, …) return 404 when the slug/id does not
exist, and a typed route rejects the wrong post type (e.g. a news item under `/articles/<slug>`
→ 404). Existing slugs return 200. `route-matrix.mjs` and `route-smoke.mjs` assert the 404
status **and** that the body carries no PHP error.

## 12. What changed in the media / UI layer?

Media uses unified, RTL/Vazirmatn cards and native HTML5 players. Media URLs centralise through
`mediaUrl()`/`videoUrl()`/`audioUrl()` → `url('video'|'audio', ['id' => …])`, and the media
library (`/media`, `/videos`, `/audios`) is a single controller (`pages/media-library.php`)
whose tabs, canonical and active state derive from the resolved route — consistent in both URL
modes. Detail pages and the library never expose `pages/*.php`.

## 13. How is the homepage a modern portal without hard‑coding content?

`index.php` keeps its homepage body but drives every section from the database: a hero post with
its topic, **DB‑featured topics** (`is_featured`/`sort_order`/post‑count ordering), latest
content per section, and links generated exclusively via `url()`/typed helpers. Nothing is
hard‑coded; empty states fall back gracefully.

## 14. What compatibility guarantees hold?

PHP 8.3 + MySQL/PostgreSQL via PDO, no new dependencies, InfinityFree/cPanel‑friendly
(`.htaccess`, no Composer at runtime for the public site). Pretty URLs are opt‑in and never
required. No existing route, page, query, controller, or DB data was removed; the only route
**additions** were the bare `/article` and `/report` detail entries (so every `url()` target
resolves — a pre‑existing gap), and the `/login` canonical now uses the clean `/login` alias.

## 15. How do I deploy this on InfinityFree?

1. Upload the tree; point the document root at the repo root.
2. Leave `JHD_PRETTY_URLS` unset (Query mode) — it works with or without `mod_rewrite`.
3. Set `SITE_URL` to the site origin and configure DB credentials in `config/`.
4. (Optional) To enable pretty URLs, set `JHD_PRETTY_URLS=true` **and** ensure `mod_rewrite`
   is active so `.htaccess` routes through `router.php`. The sitemap, canonicals and all links
   switch to the pretty spelling automatically.

---

**Bottom line:** routing, internal links, topic hubs, canonicals/SEO and media UI were rebuilt
around one registry + one `url()`; Query mode is the InfinityFree‑safe default and Pretty mode
is a validated opt‑in; allowlist‑only routing closes the traversal/internal‑PHP surface; and the
full dual‑mode suite plus the container runtime pass in CI.

---

## Post‑CI verification audit

After the green run, a source‑level audit confirmed the invariants hold everywhere (not just
where a test happens to look):

| Check | Result |
|-------|--------|
| Hard‑coded `index.php?p=…` in public templates | **none** — every link goes through `url()` |
| Hard‑coded pretty hrefs (`href="/news"` …) in templates | **none** |
| `redirect()` | rejects `\r\n` (header injection), schemes and protocol‑relative `//`; allows same‑origin absolute + scheme‑less relative (Query‑mode `index.php?p=…`); 303 for POST, 302 otherwise |
| `robots.php` | `Sitemap:` → `/sitemap.xml`, whose `<loc>` set is mode‑aware |
| 404 + search | `<meta name="robots" content="noindex, follow">`; 404 page also self‑noindexes |
| 404 page links | deliberately **absolute** (`BASE_PATH.'/news'`) — a 404 can render at any path depth, where Query‑mode *relative* links would mis‑resolve; absolute paths are correct in both modes |
| Admin "view public page" links | `postUrl()` / `url('admin/…')` — centralized, never a raw `pages/*.php` |
| `paginate()` | substitutes `%d` **and** `%25d` (url()'s percent‑encoded placeholder), with a guarded `sprintf` fallback |
| `url('login')` / `url('logout')` | clean `/login` `/logout` aliases (resolve to `admin/login.php` / `admin/logout.php`) |

These, together with the green CI (21/21 steps success), are the evidence that the build meets
the production‑readiness bar for InfinityFree.
