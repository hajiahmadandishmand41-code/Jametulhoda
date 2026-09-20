import { request } from '@playwright/test';
import fs from 'node:fs';
const base=process.env.TEST_BASE_URL || 'http://127.0.0.1:8080';
const api=await request.newContext({baseURL:base});
const creds={username:process.env.TEST_ADMIN_USERNAME,password:process.env.TEST_ADMIN_PASSWORD};
if (!creds.username || !creds.password) throw new Error('Set TEST_ADMIN_USERNAME and TEST_ADMIN_PASSWORD for an isolated test database.');
fs.mkdirSync('test-results',{recursive:true});
const results=[];
const check=(label,ok,detail='')=>{results.push({label,ok,detail});console.log(ok?'PASS':'FAIL',label,detail);};
const token=html=>html.match(/name="csrf_token" value="([a-f0-9]+)"/)?.[1];
for(const path of ['/','/about.php','/contact.php','/news.php','/articles.php','/lessons.php','/books.php','/speeches.php','/programs.php','/religious-activities.php','/announcements.php','/search.php?q=test','/category.php?slug=fiqh-osul','/robots.txt','/audio','/video','/files','/library']){const r=await api.get(path);check('GET '+path,r.status()===200,String(r.status()));}
for(const path of ['/missing-page','/.env','/.git/config','/config/database.php','/database.sql','/install.php','/includes/auth.php','/uploads/test.php','/bin/create-admin.php']){const r=await api.get(path);check('protected '+path,r.status()===404,String(r.status()));}
let r=await api.get('/admin/',{maxRedirects:0});check('admin requires login',r.status()===302);
r=await api.get('/admin/login.php');let csrf=token(await r.text());
r=await api.post('/admin/login.php',{form:{...creds,csrf_token:csrf},maxRedirects:0});check('login valid',r.status()===303,String(r.status()));
r=await api.get('/admin/');check('dashboard',r.status()===200,String(r.status()));
for(const path of ['/admin/posts/','/admin/articles/','/admin/news/','/admin/speeches/','/admin/lessons/','/admin/books/','/admin/categories/','/admin/media/','/admin/messages/','/admin/settings.php','/admin/users.php']){const r=await api.get(path);check('admin '+path,r.status()===200,String(r.status()));}
r=await api.get('/admin/posts/create.php');csrf=token(await r.text());
const stamp=Date.now();const title='qa-post-'+stamp;
// Use the existing project logo as a valid JPEG fixture (user filename is deliberately misleading).
const image={name:'unsafe.php.jpg',mimeType:'image/jpeg',buffer:fs.readFileSync(new URL('./fixtures/image.png',import.meta.url))};
r=await api.post('/admin/posts/create.php',{multipart:{csrf_token:csrf,title,status:'published',post_type:'news','page_section[]':'home',summary:'این مطلب برای آزمون محلی ایجاد شده است.',content:'<p>محتوای آزمایشی</p><script>alert("XSS")</script>',featured_image:image,featured_video:{name:'video.mp4',mimeType:'video/mp4',buffer:fs.readFileSync(new URL('./fixtures/video.mp4',import.meta.url))},'audio_files[]':{name:'audio.mp3',mimeType:'audio/mpeg',buffer:fs.readFileSync(new URL('./fixtures/audio.mp3',import.meta.url))}},maxRedirects:0});check('create post + image video audio',r.status()===303,String(r.status()));if(r.status()!==303)fs.writeFileSync('test-results/post-failure.html',await r.text());
const stored=[];
const edit=r.headers().location;let id=edit?.match(/id=(\d+)/)?.[1];
r=await api.get('/post.php?slug='+title);let html=await r.text();check('published detail',r.status()===200,String(r.status()));check('rich HTML XSS removed',!html.includes('<script>alert("XSS")'));
for(const url of new Set([...html.matchAll(/(?:src|href)="(\/uploads\/[^"?]+)"/g)].map(m=>m[1]))){stored.push(url); const a=await api.get(url);check('stored file accessible '+url,a.status()===200,String(a.status()));}
r=await api.get('/admin/books/create.php');csrf=token(await r.text());
r=await api.post('/admin/books/create.php',{multipart:{csrf_token:csrf,title:'qa-book-'+stamp,description:'کتاب آزمون',pdf_file:{name:'document.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF')}},maxRedirects:0});check('upload PDF + create book',r.status()===303,String(r.status()));
r=await api.get('/books.php?q=qa-book-'+stamp);
const bookId=(await r.text()).match(/book\.php\?id=(\d+)/)?.[1];
check('book linked in library',Boolean(bookId));
if(bookId){r=await api.get('/book.php?id='+bookId);check('book detail exists',r.status()===200);r=await api.get('/book.php?id='+bookId+'&download=pdf');check('book PDF download',r.status()===200 && r.headers()['content-type']?.includes('application/pdf'));}
for(const section of ['articles','news','lessons']){r=await api.get('/admin/'+section+'/create.php');csrf=token(await r.text());r=await api.post('/admin/'+section+'/create.php',{form:{csrf_token:csrf,title:'qa-'+section+'-'+stamp,content:'<p>Test content</p>',status:'published',level:'beginner','page_section[]':'home'},maxRedirects:0});check('create '+section,r.status()===303,String(r.status()));if(r.status()!==303)fs.writeFileSync('test-results/'+section+'-failure.html',await r.text());}
if(id){
// Editing retains existing media and updates content using prepared statements.
r=await api.get(edit);csrf=token(await r.text());
r=await api.post(edit,{form:{csrf_token:csrf,title,status:'published',post_type:'news','page_section[]':'home',content:'<p>Updated test content</p>'},maxRedirects:0});
check('edit content',r.status()===303,String(r.status()));
r=await api.get('/post.php?slug='+title);html=await r.text();check('edited content visible',html.includes('Updated test content'));
const likeToken=html.match(/name="csrf-token" content="([a-f0-9]+)"/)?.[1];
r=await api.post('/ajax/like.php',{data:{post_id:Number(id)}});check('like without CSRF rejected',r.status()===403);
r=await api.post('/ajax/like.php',{data:{post_id:Number(id)},headers:{'X-CSRF-Token':likeToken}});check('like valid',r.status()===200 && (await r.json()).success===true);
r=await api.get('/admin/posts/delete.php?id='+id);check('GET delete confirms, no mutation',r.status()===200 && (await r.text()).includes('تأیید عملیات'));r=await api.get('/post.php?slug='+title);check('post still exists after GET delete',r.status()===200);r=await api.post('/admin/posts/delete.php',{form:{id},maxRedirects:0});check('delete missing CSRF rejected',r.status()===403);r=await api.get('/admin/posts/delete.php?id='+id);csrf=token(await r.text());r=await api.post('/admin/posts/delete.php',{form:{id,csrf_token:csrf},maxRedirects:0});check('POST delete valid',r.status()===303,String(r.status()));r=await api.get('/post.php?slug='+title);check('deleted post 404',r.status()===404);for(const url of stored){const file=await api.get(url);check('deleted media returns 404',file.status()===404);}}
r=await api.get('/admin/posts/create.php');csrf=token(await r.text());
r=await api.post('/admin/posts/create.php',{multipart:{csrf_token:csrf,title:'rejected-'+stamp,status:'published',featured_image:{name:'photo.jpg',mimeType:'image/jpeg',buffer:Buffer.from('<?php echo "unsafe"; ?>')}},maxRedirects:0});check('spoofed image rejected',r.status()===200 && (await r.text()).includes('خطا در آپلود'));
// Failed multi-file content uploads must not leak a previously accepted image.
// A standalone media-library upload is intentional and must survive this cleanup.
r=await api.get('/admin/media/'); csrf=token(await r.text());
r=await api.post('/admin/media/',{multipart:{csrf_token:csrf,'images[]':image}});
let library=await r.text();
const galleryId=library.match(/href="\?delete=(\d+)"/)?.[1];
const mediaUrls=text=>[...text.matchAll(/data-copy-url="([^"]+)"/g)].map(m=>m[1]).sort();
const beforeFailedUpload=mediaUrls(library);
check('standalone gallery upload retained',r.status()===200 && library.includes('تصویر با موفقیت آپلود شد') && !!galleryId && beforeFailedUpload.length>0);
r=await api.get('/admin/books/create.php'); csrf=token(await r.text());
r=await api.post('/admin/books/create.php',{multipart:{csrf_token:csrf,title:'qa-rejected-book-'+stamp,description:'آزمون پاک‌سازی',cover_image:image,pdf_file:{name:'fake.pdf',mimeType:'application/pdf',buffer:Buffer.from('not a PDF')}}});
const rejectedBook=await r.text();
check('second upload validation rejects book',r.status()===200 && rejectedBook.includes('خطا در آپلود فایل PDF'),String(r.status()));
if (!rejectedBook.includes('خطا در آپلود فایل PDF')) fs.writeFileSync('test-results/rejected-book.html',rejectedBook);
r=await api.get('/admin/media/'); library=await r.text();
check('failed upload cleaned without deleting library files',JSON.stringify(mediaUrls(library))===JSON.stringify(beforeFailedUpload));
if (galleryId) {
  csrf=token(library);
  // The listing has an upload CSRF field; the delete operation requires POST too.
  r=await api.post('/admin/media/',{form:{csrf_token:csrf,delete:galleryId},maxRedirects:0});
  check('standalone gallery fixture removed',r.status()===303 || r.status()===302);
}
r=await api.get('/admin/users.php');csrf=token(await r.text());
const editorName='qa_editor_'+stamp;
r=await api.post('/admin/users.php',{form:{csrf_token:csrf,username:editorName,full_name:'ویرایشگر آزمون',role:'editor',password:creds.password,is_active:'on'}});check('create editor account',r.status()===200 && (await r.text()).includes('اطلاعات کاربر ذخیره شد'));
const editor=await request.newContext({baseURL:base});
r=await editor.get('/admin/login.php');csrf=token(await r.text());
r=await editor.post('/admin/login.php',{form:{csrf_token:csrf,username:editorName,password:creds.password},maxRedirects:0});check('editor login',r.status()===303);
r=await editor.get('/admin/settings.php');check('editor forbidden from settings',r.status()===403);
r=await editor.get('/admin/users.php');check('editor forbidden from users',r.status()===403);
await editor.dispose();
r=await api.get('/contact.php');csrf=token(await r.text());r=await api.post('/contact.php',{form:{csrf_token:csrf,name:'آزمون تماس',email:'qa@example.test',subject:'آزمون محلی',message:'این پیام برای بررسی فرم تماس ایجاد شده است.'}});check('contact submit',r.status()===200 && /موفقیت|تعداد پیام‌های ارسالی بیش از حد مجاز/.test(await r.text()));
r=await api.get('/admin/logout.php');csrf=token(await r.text());r=await api.post('/admin/logout.php',{form:{csrf_token:csrf},maxRedirects:0});check('logout',r.status()===302||r.status()===303);r=await api.get('/admin/',{maxRedirects:0});check('logged out cannot access admin',r.status()===302);
fs.writeFileSync('test-results/http-results.json',JSON.stringify(results,null,2));await api.dispose();

if(results.some(result=>!result.ok)) process.exitCode=1;
