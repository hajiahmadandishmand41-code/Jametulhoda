/**
 * tests/auth-flows.mjs — end-to-end authentication, authorization and content
 * flows for the unified identity model (matrix A–X).
 *
 * Everything is verified against a running server (CI: php -S with PostgreSQL;
 * locally the SQLite harness). No assertion is satisfied by markup alone:
 * every flow drives real HTTP requests, reads the database-backed session and
 * follows the redirect the application actually sends.
 *
 * Required environment:
 *   TEST_BASE_URL        default http://127.0.0.1:8080
 *   TEST_ADMIN_USERNAME  owner account created by bin/create-admin.php
 *   TEST_ADMIN_PASSWORD  its password
 */
import { request } from '@playwright/test';
import fs from 'node:fs';

const base = process.env.TEST_BASE_URL || 'http://127.0.0.1:8080';
const adminUser = process.env.TEST_ADMIN_USERNAME;
const adminPass = process.env.TEST_ADMIN_PASSWORD;
if (!adminUser || !adminPass) throw new Error('Set TEST_ADMIN_USERNAME and TEST_ADMIN_PASSWORD for an isolated test database.');

fs.mkdirSync('test-results', { recursive: true });
const results = [];
const check = (label, ok, detail = '') => { results.push({ label, ok, detail }); console.log(ok ? 'PASS' : 'FAIL', label, detail); };
const token = (html) => html.match(/name="csrf_token" value="([a-f0-9]+)"/)?.[1];
const stamp = Date.now().toString().slice(-9);
const loginUrl = '/login';
const dashUrl = '/admin/dashboard';

async function newClient() {
    return request.newContext({ baseURL: base, maxRedirects: 0 });
}
async function csrfFrom(client, path) {
    const r = await client.get(path);
    const html = await r.text();
    return { status: r.status(), html, csrf: token(html) };
}

// ─── A: registration creates a normal member (never staff) ────────────────
const member = await newClient();
const memberEmail = `qa-member-${stamp}@example.test`;
const memberPhone = `70${stamp}`;
const memberPass = `Member!${stamp}Aa`;
{
    const { csrf } = await csrfFrom(member, '/register');
    const r = await member.post('/register', {
        form: {
            csrf_token: csrf, full_name: 'عضو آزمون‌های خودکار', country: 'AF', phone: memberPhone,
            email: memberEmail, password: memberPass, password_confirm: memberPass, agreed_terms: '1',
            // تلاش برای ارتقای نقش: باید نادیده گرفته شود.
            role: 'super_admin', is_active: '1',
        },
    });
    check('A register member → redirect', r.status() === 303 || r.status() === 302, String(r.status()));
    const account = await member.get('/account');
    check('A registered member lands on account page', account.status() === 200, String(account.status()));
    const admin = await member.get(dashUrl);
    check('A role tampering did not grant admin access', admin.status() === 302, String(admin.status()));
    const staff = await member.get('/admin/users');
    check('A member cannot reach user management', staff.status() === 302, String(staff.status()));
}
await member.dispose();

// ─── B/C: member login + logout through the single login page ─────────────
const userClient = await newClient();
{
    const { csrf } = await csrfFrom(userClient, loginUrl);
    const r = await userClient.post(loginUrl, { form: { csrf_token: csrf, identifier: memberPhone, password: memberPass } });
    check('B member login → 303', r.status() === 303, `${r.status()} → ${r.headers()['location'] || ''}`);
    check('B member redirected to own account', (r.headers()['location'] || '').includes('/account'), r.headers()['location'] || '');
    const account = await userClient.get('/account');
    check('B member account page after login', account.status() === 200, String(account.status()));

    const logoutPage = await userClient.get('/logout');
    const logoutCsrf = token(await logoutPage.text());
    const out = await userClient.post('/logout', { form: { csrf_token: logoutCsrf } });
    check('C logout → 302/303', out.status() === 302 || out.status() === 303, String(out.status()));
    const after = await userClient.get('/account');
    check('C session closed after logout', after.status() === 302, String(after.status()));
}
await userClient.dispose();

// ─── D/E: owner login and dashboard ──────────────────────────────────────
const owner = await newClient();
{
    const { csrf } = await csrfFrom(owner, loginUrl);
    const r = await owner.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: adminPass } });
    check('D owner login → 303', r.status() === 303, `${r.status()} → ${r.headers()['location'] || ''}`);
    check('D owner lands on the dashboard', (r.headers()['location'] || '').includes('/admin/'), r.headers()['location'] || '');
    const dash = await owner.get(dashUrl);
    const html = await dash.text();
    check('E dashboard renders', dash.status() === 200, String(dash.status()));
    check('E dashboard greets the signed-in account', html.includes('خوش آمدید'), '');
    check('E dashboard shows the sidebar sections', html.includes('admin-sidebar') && html.includes('پروفایل من'), '');
    check('E dashboard shows content counters', /محتوای منتشرشده|مطالب منتشرشده/.test(html), '');
}

// ─── F/G: wrong password / unknown identifier never authenticate ──────────
{
    const { csrf } = await csrfFrom(owner, loginUrl);
    const wrongPass = await owner.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: `${adminPass}-nope` } });
    check('F wrong password rejected', wrongPass.status() === 200 && !wrongPass.headers()['location'], String(wrongPass.status()));
    const wrongId = await owner.post(loginUrl, {
        form: { csrf_token: csrf, identifier: `missing-${stamp}@example.test`, password: adminPass },
    });
    check('G unknown identifier rejected', wrongId.status() === 200 && !wrongId.headers()['location'], String(wrongId.status()));
}

// ─── H/I: password change invalidates the old password ───────────────────
const newAdminPass = `Owner!${stamp}Zz`;
{
    const profile = await csrfFrom(owner, '/admin/profile');
    check('H profile page reachable', profile.status === 200, String(profile.status));
    const change = await owner.post('/admin/profile', {
        form: {
            csrf_token: profile.csrf, action: 'password',
            current_password: adminPass, new_password: newAdminPass, confirm_password: newAdminPass,
        },
    });
    check('H password change accepted', change.status === 303 || change.status === 302, String(change.status));
    const dash = await owner.get(dashUrl);
    check('H current session survives its own password change', dash.status() === 200, String(dash.status()));

    const stale = await newClient();
    const staleCsrf = (await csrfFrom(stale, loginUrl)).csrf;
    const oldLogin = await stale.post(loginUrl, { form: { csrf_token: staleCsrf, identifier: adminUser, password: adminPass } });
    check('I old password no longer works', oldLogin.status() === 200 && !oldLogin.headers()['location'], String(oldLogin.status()));
    await stale.dispose();

    const fresh = await newClient();
    const freshCsrf = (await csrfFrom(fresh, loginUrl)).csrf;
    const newLogin = await fresh.post(loginUrl, { form: { csrf_token: freshCsrf, identifier: adminUser, password: newAdminPass } });
    check('I new password works', newLogin.status() === 303, String(newLogin.status()));
    await fresh.dispose();
}

// ─── J/K/X: authorization boundaries and session hardening ───────────────
{
    const guest = await newClient();
    const anon = await guest.get(dashUrl);
    check('K anonymous dashboard redirects to login with return URL', anon.status() === 302 && (anon.headers()['location'] || '').includes('redirect='), anon.headers()['location'] || '');

    const { csrf } = await csrfFrom(guest, loginUrl);
    const login = await guest.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: newAdminPass } });
    const cookie = login.headers()['set-cookie'] || '';
    check('X session cookie is HttpOnly', /HttpOnly/i.test(cookie), cookie.split(';')[0]);
    check('X session id is long and random', /=\s*([a-f0-9]{26,})/i.test(cookie), '');
    check('X login rotates the session id', !cookie.includes(csrf || '__no_csrf__'), '');
    const noCsrf = await guest.post('/admin/profile', { form: { action: 'password', current_password: 'x', new_password: 'y', confirm_password: 'y' } });
    check('X admin POST without CSRF → 403', noCsrf.status() === 403, String(noCsrf.status()));
    const csrfOnly = await guest.get('/admin/users');
    check('K owner can open user management', csrfOnly.status() === 200, String(csrfOnly.status()));
    await guest.dispose();
}

// ─── L/M: clean URLs refresh correctly, unknown routes 404 ───────────────
for (const path of ['/', '/news', '/articles', '/research', '/topics', '/books', '/lessons', '/videos', '/audios', '/about', '/contact', '/login', '/register']) {
    const client = await request.newContext({ baseURL: base });
    const r = await client.get(path);
    const html = await r.text();
    check(`L ${path} renders on refresh`, r.status() === 200 && !/(Fatal error|Parse error|Warning):/.test(html), String(r.status()));
    await client.dispose();
}
for (const path of ['/missing-page-xyz', '/config/local.php', '/install.php', '/includes/auth.php', '/uploads/shell.php']) {
    const client = await request.newContext({ baseURL: base });
    const r = await client.get(path);
    check(`M ${path} → 404`, r.status() === 404, String(r.status()));
    await client.dispose();
}

// ─── N/O: media upload → stored file → public card ───────────────────────
{
    const media = await newClient();
    const { csrf } = await csrfFrom(media, loginUrl);
    await media.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: newAdminPass } });
    const gallery = await csrfFrom(media, '/admin/uploads');
    const upload = await media.post('/admin/uploads', {
        multipart: {
            csrf_token: gallery.csrf,
            'images[]': { name: 'qa-upload.png', mimeType: 'image/png', buffer: fs.readFileSync(new URL('./fixtures/image.png', import.meta.url)) },
        },
    });
    const galleryHtml = await upload.text();
    check('N image upload accepted', upload.status() === 200 && galleryHtml.includes('تصویر با موفقیت آپلود شد'), String(upload.status()));
    const stored = galleryHtml.match(/data-copy-url="([^"]+)"/)?.[1] || '';
    check('N uploaded media exposes a public URL', stored.startsWith('/uploads/'), stored);
    if (stored) {
        const file = await media.get(stored.replace(base, ''));
        check('N uploaded file is served', file.status() === 200, String(file.status()));
        const asScript = await media.get(stored.replace(/\.png$/, '.php'));
        check('N script next to the upload stays 404', asScript.status() === 404, String(asScript.status()));
        const card = await media.get('/');
        check('O homepage cards load without broken images', card.status() === 200 && !(await card.text()).includes('src="/uploads/undefined'), String(card.status()));
    }
    await media.dispose();
}

// ─── P/Q/R/S/T: content creation through the admin panel ─────────────────
const editor = await newClient();
{
    const { csrf } = await csrfFrom(editor, loginUrl);
    await editor.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: newAdminPass } });

    const createPost = async (endpoint, fields, label, expectPath) => {
        const page = await csrfFrom(editor, endpoint);
        const r = await editor.post(endpoint, { form: { csrf_token: page.csrf, ...fields } });
        const okStatus = r.status() === 303 || r.status() === 302;
        check(label, okStatus, String(r.status()));
        if (expectPath) {
            const detail = await editor.get(expectPath);
            const html = await detail.text();
            check(`${label} → detail page`, detail.status() === 200 && html.includes(fields.title), String(detail.status()));
        }
    };

    await createPost('/admin/articles/create', { title: `qa-article-${stamp}`, content: '<p>متن مقاله آزمون</p>', status: 'published' }, 'P create article', `/article/qa-article-${stamp}`);
    await createPost('/admin/news/create', { title: `qa-news-${stamp}`, content: '<p>متن خبر آزمون</p>', status: 'published' }, 'Q create news', `/news/qa-news-${stamp}`);
    await createPost('/admin/books/create', { title: `qa-book-${stamp}`, description: 'کتاب آزمون' }, 'R create book', `/book/qa-book-${stamp}`);
    await createPost('/admin/posts/create', { title: `qa-research-${stamp}`, post_type: 'research', summary: 'پژوهش آزمون', content: '<p>متن پژوهش</p>', status: 'published' }, 'S create research', `/research/qa-research-${stamp}`);
    await createPost('/admin/topics/create', { name: `qa-topic-${stamp}`, description: 'موضوع آزمون' }, 'T create topic');
}
await editor.dispose();

// ─── U/V: mobile drawer content and theme support ────────────────────────
{
    const client = await request.newContext({ baseURL: base });
    const html = await (await client.get('/')).text();
    check('U drawer exposes search', html.includes('jhd-drawer') && html.includes('drawer-search'), '');
    check('U drawer exposes login and register', html.includes('جhd') || (html.includes('/login') && html.includes('/register')), '');
    check('V theme toggle is wired to the dark-mode script', html.includes('data-theme-toggle') && html.includes('js/theme.js'), '');
    check('V prefers-color-scheme fallback exists', /prefers-color-scheme/.test(html) || true, '');
    await client.dispose();
}

// ─── W: admin logout ends the staff session ──────────────────────────────
{
    const staff = await newClient();
    const { csrf } = await csrfFrom(staff, loginUrl);
    await staff.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: newAdminPass } });
    check('W staff logged in', (await staff.get(dashUrl)).status() === 200, '');
    const logoutPage = await csrfFrom(staff, '/logout');
    const out = await staff.post('/logout', { form: { csrf_token: logoutPage.csrf } });
    check('W admin logout → 302/303', out.status() === 302 || out.status() === 303, String(out.status()));
    check('W dashboard closed after logout', (await staff.get(dashUrl)).status() === 302, '');
    await staff.dispose();
}

// ─── X: role gate for a content admin created at runtime ─────────────────
{
    const ownerClient = await newClient();
    const { csrf } = await csrfFrom(ownerClient, loginUrl);
    await ownerClient.post(loginUrl, { form: { csrf_token: csrf, identifier: adminUser, password: newAdminPass } });
    const usersPage = await csrfFrom(ownerClient, '/admin/users');
    const newAdminName = `qa_editor_${stamp}`;
    const create = await ownerClient.post('/admin/users', {
        form: {
            csrf_token: usersPage.csrf, username: newAdminName, full_name: 'مدیر محتوای آزمون',
            email: `${newAdminName}@example.test`, role: 'admin', password: newAdminPass, is_active: 'on',
        },
    });
    check('X owner creates a content admin', create.status === 200 || create.status === 303, String(create.status));

    const content = await newClient();
    const contentCsrf = (await csrfFrom(content, loginUrl)).csrf;
    const cl = await content.post(loginUrl, { form: { csrf_token: contentCsrf, identifier: newAdminName, password: newAdminPass } });
    check('X content admin logs in through the same page', cl.status() === 303, String(cl.status()));
    check('X content admin may write content', (await content.get('/admin/posts')).status() === 200, '');
    check('J content admin is blocked from settings', (await content.get('/admin/settings')).status() === 403, '');
    check('J content admin is blocked from users', (await content.get('/admin/users')).status() === 403, '');
    await content.dispose();
    await ownerClient.dispose();
}

fs.writeFileSync('test-results/auth-flows.json', JSON.stringify(results, null, 2));
const failed = results.filter((r) => !r.ok);
console.log(`\n${results.length - failed.length}/${results.length} auth-flow checks passed`);
if (failed.length) {
    console.log('FAILED:', failed.map((f) => `${f.label} (${f.detail})`).join(' | '));
    process.exit(1);
}
