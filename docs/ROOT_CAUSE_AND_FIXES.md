# Jametulhoda — Root-cause audit and fixes

Date: 2026-09-25  
Branch: `arena/01a0d91a-jametulhoda`  
Production compared: https://jametulhoda.gt.tc

## A. Problems found

1. `/admin/login` and `/login` returned the **InfinityFree platform 404** (not the app 404).
2. `/admin/login.php` opened, proving PHP files were served directly.
3. Homepage content links used Query mode (`index.php?p=news`) but **ورود** pointed at a pretty path with no physical file.
4. `url('')` / `url('news')` emitted *page-relative* `index.php?…`, so “بازگشت به سایت” on `/admin/login.php` resolved to `/admin/index.php`.
5. `/login` was an alias of admin login — no public membership.
6. Pretty URLs on InfinityFree are not reliable (`mod_rewrite` / hidden `.htaccess` often missing).
7. Admin session cookie used `SameSite=Strict` and `path` that broke on some HTTP→HTTPS cases.
8. Login did not distinguish inactive/invalid-hash accounts in logs (only generic failure).
9. Topic URLs were single-segment only (`/topic/oloum-quran`), not parent/child.
10. GET filters in Query mode dropped `p=` because browsers replace the query string.

## B. Root cause

Production is running a build close to GitHub (homepage already emits `index.php?p=…`) but **pretty paths are not rewritten**.

Evidence:

| URL | Result |
|---|---|
| `/` | 200, Query-mode links |
| `/admin/login` | InfinityFree 404 (`errors.infinityfree.net`) — request never reached PHP |
| `/admin/login.php` | 200 login form |
| `/login` | InfinityFree 404 |

Cause mix:

1. **No physical file** at `admin/login` (only `admin/login.php`).
2. **`.htaccess` rewrite not applied** on production (hidden file not uploaded, or `[END]` / missing `RewriteBase`).
3. **`url('login')` was not in `jhd_routes()`**, so it always generated `/login`.
4. **Admin URLs always pretty** (`/admin/login`) even when `JHD_PRETTY_URLS=false`.
5. **Relative `index.php`** from `/admin/login.php` resolved under `/admin/`.

GitHub routing (`router.php` + `.htaccess` → everything) would have fixed `/admin/login` **if rewrite ran**. Production proved rewrite is not a safe dependency.

## C. Files changed (high level)

- Routing: `includes/functions.php`, `config/routes.php`, `router.php`, `.htaccess`, `index.php` (unchanged dispatch, now resolves auth routes)
- Auth: `includes/auth.php`, `includes/member-auth.php` (new), `admin/login.php`, `php/install.php`, `bin/diagnose-admin.php`
- Public pages: `pages/login.php`, `pages/register.php`, `pages/logout.php`, `pages/account.php`, `pages/password-change.php`
- InfinityFree stubs: `login/`, `register/`, `logout/`, `account/`, `password-change/`, `admin/login/`, `admin/logout/`, root `login.php` / `register.php`
- UI: `includes/header.php`, `includes/footer.php`, `assets/css/design-system.css`, admin sidebar, topic empty state
- Schema: `database/database.mysql.sql`, `database/database.postgres.sql`, `bin/migrate.php`
- Admin: `admin/diagnostics.php`, `admin/members/index.php`, post topic primary
- Tests: `tests/security.php`, `tests/verify_routes.py`, `tests/http.mjs`, `tests/route-matrix.mjs`

## D. Database changes

- New table `members` (public users; never shares admin roles).
- Optional column `post_topics.is_primary`.
- Extra topic seeds (حدیث، سیره، اندیشه اسلامی، زیرموضوع‌های اخلاق/فقه/قرآن).
- Idempotent `CREATE TABLE IF NOT EXISTS` + migrate skips duplicate column.
- Runtime `ensureMembersSchema()` / `ensureCoreAuthTables()` for already-installed hosts.

## E. Route changes

| URL | Controller | Without rewrite |
|---|---|---|
| `/login`, `/login.php`, `index.php?p=login` | `pages/login.php` | stub / query |
| `/register` | `pages/register.php` | stub / query |
| `/account` | `pages/account.php` | stub / query |
| `/admin/login`, `/admin/login.php` | `admin/login.php` | directory stub / physical file |
| `/admin`, `/admin/` | `admin/index.php` | DirectoryIndex |
| `/topic/parent/child` | `pages/topic.php` | pretty needs rewrite or query `?p=topic&slug=` |

`/login` is **no longer** an alias of admin login.

## F. Auth changes

- Admin: `password_hash` / `password_verify` only; plaintext hashes refused; rehash on login; inactive/invalid role denied; rate limit; session regenerate; `SameSite=Lax`; cookie path `/`.
- Public members: phone + country (email optional), separate session keys, cannot open `/admin`.
- Diagnostics never print password or hash.

## G. UI changes

- Header: ورود / ثبت‌نام / ورود مدیر, account menu, nested موضوعات submenu.
- Auth pages use the public design system.
- Homepage CTA includes registration.
- Topic hub empty state.
- Admin sidebar: اعضای سایت + وضعیت سامانه.

## H. Security changes

- Open-redirect / relative-URL bug fixed (all `url()` output is root-relative).
- Username enumeration timing padded with dummy `password_verify`.
- Duplicate register/login errors are generic.
- Admin pages remain `noindex`.
- Auth pages `noindex`.
- CSRF on all state-changing forms.

## I. Test results (this environment)

- `python3 tests/verify_routes.py` — **75/75 PASS**
- `python3 tests/crawl_links.py` — **100% valid internal links**
- PHP CLI not available in this sandbox; CI (`php -l`, `http.mjs`, `route-matrix`) is the HTTP proof.

## J. Remaining issues

- Forgot-password email is not implemented (InfinityFree mail is unreliable). Logged-in password change works.
- If production `.htaccess` is still missing, upload the hidden file; stubs + Query mode keep the site alive without it.
- After deploy, run `php bin/migrate.php` (or open a register page once) so `members` exists on old databases.
- Re-login after deploy (cookie SameSite changed Lax).
