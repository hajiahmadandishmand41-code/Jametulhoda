import {chromium} from '@playwright/test';
import fs from 'node:fs';
import assert from 'node:assert/strict';
fs.mkdirSync('test-results',{recursive:true});
const base=process.env.TEST_BASE_URL || 'http://127.0.0.1:8080/';
const browser=await chromium.launch({executablePath:process.env.BROWSER_EXECUTABLE || undefined,args:['--no-sandbox'],headless:true});
try {
  const page=await browser.newPage({viewport:{width:1440,height:1000}});
  const errors=[];
  page.on('pageerror',e=>errors.push(String(e)));
  page.on('response',r=>{if(r.status()>=400) errors.push(r.status()+' '+r.url())});
  await page.goto(base,{waitUntil:'networkidle'});
  await page.screenshot({path:'test-results/home-desktop.png',fullPage:true});
  const viewportAudit=[];
  for (const width of [360,390,414,768,1024,1280,1440]) {
    await page.setViewportSize({width,height:900});
    const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
    const navVisible=await page.locator('.jhd-navbar-desktop').isVisible();
    const menuVisible=await page.locator('#menuToggle').isVisible();
    viewportAudit.push({width,overflow,navVisible,menuVisible});
    assert.equal(overflow,false,`horizontal overflow at ${width}px`);
    assert.equal(navVisible,width>=1200,`desktop navigation breakpoint at ${width}px`);
    assert.equal(menuVisible,width<1200,`drawer toggle breakpoint at ${width}px`);
  }

  // Audit every requested public route in a real browser, not just the landing page.
  // Route smoke checks status/canonical metadata; this layer checks visible content,
  // responsive overflow and actual browser resource/runtime errors.
  const publicRoutes=['news','articles','reports','events','books','lessons','research','media','videos','audios','topics','search','about','contact','qa','login'];
  const pageViewportAudit=[];
  for (const route of publicRoutes) {
    const response=await page.goto(new URL(route,base.endsWith('/')?base:base+'/').href,{waitUntil:'networkidle'});
    assert.equal(response?.status(),200,`HTTP ${route}`);
    assert.equal(await page.locator('h1').first().isVisible(),true,`visible page heading ${route}`);
    assert.equal(route==='login' ? await page.locator('body.jhd-login-site').count()===1 : await page.locator('body.jhd-public-site').count()===1,true,`shared visual shell ${route}`);
    const description=await page.locator('meta[name="description"]').getAttribute('content');
    assert.ok((description||'').trim().length>20,`descriptive metadata ${route}`);
    for (const width of [360,768,1280]) {
      await page.setViewportSize({width,height:900});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
      assert.equal(overflow,false,`horizontal overflow ${route} at ${width}px`);
    }
    pageViewportAudit.push(route);
  }
  console.log('PASS public browser audit: '+pageViewportAudit.length+' routes × 3 widths');

  const detailFixture='test-results/browser-detail-routes.json';
  const detailRoutes=fs.existsSync(detailFixture)?JSON.parse(fs.readFileSync(detailFixture,'utf8')):[];
  for (const route of detailRoutes) {
    const siteRoot=new URL(base.endsWith('/')?base:base+'/');
    const response=await page.goto(new URL(route.replace(/^\/+/,''),siteRoot).href,{waitUntil:'networkidle'});
    assert.equal(response?.status(),200,`HTTP detail ${route}`);
    assert.equal(await page.locator('h1').first().isVisible(),true,`visible detail heading ${route}`);
    assert.equal(await page.locator('body.jhd-public-site').count(),1,`shared detail shell ${route}`);
    for (const width of [360,768,1280]) {
      await page.setViewportSize({width,height:900});
      const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
      assert.equal(overflow,false,`horizontal overflow ${route} at ${width}px`);
    }
  }
  console.log('PASS browser detail audit: '+detailRoutes.length+' database-backed detail routes × 3 widths');

  await page.setViewportSize({width:390,height:844});
  await page.goto(base,{waitUntil:'networkidle'});
  await page.screenshot({path:'test-results/home-mobile.png',fullPage:true});
  await page.locator('#menuToggle').click();
  assert.equal(await page.locator('#menuToggle').getAttribute('aria-expanded'),'true','mobile menu opens');
  assert.equal(await page.locator('#siteDrawer').getAttribute('aria-hidden'),'false','drawer is exposed to assistive technology');
  await page.keyboard.press('Shift+Tab');
  assert.equal(await page.evaluate(()=>document.querySelector('#siteDrawer').contains(document.activeElement)),true,'drawer contains keyboard focus');
  await page.keyboard.press('Escape');
  await page.locator('[data-theme-toggle]').click();
  await page.reload();
  assert.equal(await page.locator('html').getAttribute('data-theme'),'dark','theme persists');
  console.log('PASS responsive widths ' + viewportAudit.map(v=>v.width).join(', ') + ', drawer keyboard focus, no overflow, theme persistence');
  if (process.env.TEST_ADMIN_PASSWORD) {
    await page.goto(new URL('admin/login.php',base).href,{waitUntil:'networkidle'});
    await page.locator('[name=username]').fill(process.env.TEST_ADMIN_USERNAME || 'qa_admin');
    await page.locator('[name=password]').fill(process.env.TEST_ADMIN_PASSWORD);
    await Promise.all([page.waitForURL('**/admin/'),page.locator('button[type=submit]').click()]);
    assert.equal(await page.locator('html').getAttribute('data-theme'),'dark','admin inherits theme');
    assert.equal(await page.locator('body.jhd-admin-site').count(),1,'admin shared shell');
    await page.locator('#sidebarToggle').click();
    assert.equal(await page.locator('#sidebarToggle').getAttribute('aria-expanded'),'true');
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('#sidebarToggle').getAttribute('aria-expanded'),'false');
    await page.goto(new URL('admin/media/',base).href,{waitUntil:'networkidle'});
    assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'admin mobile overflow');
    assert.equal(await page.evaluate(()=>[...document.images].some(i=>i.complete&&!i.naturalWidth)),false,'broken admin images');
    await page.screenshot({path:'test-results/admin-mobile.png',fullPage:true});
    console.log('PASS browser staff login, admin mobile navigation, media, theme');
  }
  assert.deepEqual(errors,[],'browser errors / failed resources');
} finally { await browser.close(); }
