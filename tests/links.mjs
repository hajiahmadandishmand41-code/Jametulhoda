import fs from 'node:fs';
fs.mkdirSync('test-results',{recursive:true});
const origin=process.env.TEST_BASE_URL || 'http://127.0.0.1:8080';const seen=new Set();const queue=['/'];const errors=[];
while(queue.length && seen.size<180){const path=queue.shift();if(seen.has(path))continue;seen.add(path);const r=await fetch(origin+path);if(r.status>=400){errors.push({path,status:r.status});continue}if(!r.headers.get('content-type')?.includes('text/html'))continue;const html=await r.text();for(const m of html.matchAll(/(?:href|src)="(\/[^"#]*)"/g)){const link=m[1].replaceAll('&amp;','&');if(link.startsWith('//')||link.startsWith('/admin/'))continue;if(!seen.has(link))queue.push(link)}}
console.log(JSON.stringify({checked:seen.size,errors},null,2));fs.writeFileSync('test-results/link-results.json',JSON.stringify({checked:seen.size,errors},null,2));

if(errors.length) process.exitCode=1;
