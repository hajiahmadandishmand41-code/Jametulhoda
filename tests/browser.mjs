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
  await page.setViewportSize({width:390,height:844});
  await page.screenshot({path:'test-results/home-mobile.png',fullPage:true});
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),false,'mobile overflow');
  await page.locator('#menuToggle').click();
  assert.equal(await page.locator('#menuToggle').getAttribute('aria-expanded'),'true','mobile menu opens');
  await page.keyboard.press('Escape');
  await page.locator('[data-theme-toggle]').click();
  await page.reload();
  assert.equal(await page.locator('html').getAttribute('data-theme'),'dark','theme persists');
  console.log('PASS desktop, mobile navigation, no overflow, theme persistence');
  if (process.env.TEST_ADMIN_PASSWORD) {
    await page.goto(new URL('admin/login.php',base).href,{waitUntil:'networkidle'});
    await page.locator('[name=username]').fill(process.env.TEST_ADMIN_USERNAME || 'qa_admin');
    await page.locator('[name=password]').fill(process.env.TEST_ADMIN_PASSWORD);
    await Promise.all([page.waitForURL('**/admin/'),page.locator('button[type=submit]').click()]);
    assert.equal(await page.locator('html').getAttribute('data-theme'),'dark','admin inherits theme');
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
