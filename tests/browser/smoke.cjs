const fs = require('node:fs');
const path = require('node:path');
const { spawn, execFileSync } = require('node:child_process');
const assert = require('node:assert/strict');
const http = require('node:http');
const get = url => new Promise((resolve,reject)=>http.get(url,response=>{let body='';response.on('data',chunk=>body+=chunk);response.on('end',()=>resolve({status:response.statusCode,body}));}).on('error',reject));
const root = path.resolve(__dirname, '../..');
const php = 'C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe';
const dir = path.join(root, 'storage/framework/testing', 'browser-smoke-'+Date.now());
fs.mkdirSync(dir, { recursive: true });
const database = path.join(dir, 'database.sqlite');
fs.writeFileSync(database, '');
const env = { ...process.env, APP_ENV:'testing', APP_URL:'http://127.0.0.1:8012', DB_CONNECTION:'sqlite', DB_DATABASE:database, DB_URL:'', CACHE_STORE:'array', SESSION_DRIVER:'file', SESSION_COOKIE:'browser_smoke_session', QUEUE_CONNECTION:'sync', MAIL_MAILER:'array' };
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
async function until(fn, label) { for(let i=0;i<100;i++){try{const result=await fn();if(result)return result;}catch{}await sleep(200);}throw Error('Timeout: '+label); }
let server, browser, socket, diagnose;
async function main() {
    process.stdout.write(execFileSync(php, ['tests/browser/prepare.php'], {cwd:root,env,encoding:'utf8'}));
    server = spawn(php, ['-S','127.0.0.1:8012',path.join(root,'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {cwd:path.join(root,'public'),env,windowsHide:true,stdio:'ignore'});
    await until(async()=> (await get('http://127.0.0.1:8012/admin/login')).status===200, 'test server');
    browser = spawn('C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', ['--headless=new','--disable-gpu','--no-first-run','--remote-debugging-port=9229','--user-data-dir='+path.join(dir,'edge'),'about:blank'], {windowsHide:true,stdio:'ignore'});
    const target = await until(async()=> JSON.parse((await get('http://127.0.0.1:9229/json')).body).find(x=>x.type==='page'), 'browser');
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise(resolve=>socket.addEventListener('open',resolve,{once:true}));
    let sequence=0; const pending=new Map(); const errors=[];
    socket.addEventListener('message', e=>{const m=JSON.parse(e.data);if(m.id){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}else if(m.method==='Runtime.exceptionThrown'){errors.push(m.params.exceptionDetails.text+': '+(m.params.exceptionDetails.exception?.description||''));}});
    const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++sequence;pending.set(id,{resolve,reject});socket.send(JSON.stringify({id,method,params}));});
    const run=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
    diagnose=async()=>{console.error('Browser errors:',errors);console.error(await run('(() => {const e=document.querySelector("[data-geofence-editor]");const d=e&&Alpine.$data(e);return {url:location.href,text:document.body.innerText.slice(-800),latitude:String(d?.latitude),wireLatitude:String(d?.$wire?.officeLatitude),wireGet:String(d?.$wire?.$get("officeLatitude")),snapshot:e?.closest("[wire\\\\:id]")?.getAttribute("wire:snapshot")?.slice(0,1600),leaflet:typeof L,hasMap:!!d?.map,hasRef:!!d?.$refs?.map,init:d?.init?.toString(),editor:e?.outerHTML.slice(0,500)}})()'));};
    await send('Runtime.enable');
    async function login(email='browser@example.test', destination='/admin'){
        await send('Page.navigate',{url:'http://127.0.0.1:8012/admin/login'});
        await until(()=>run('!!document.querySelector("input[type=password]")'), 'login');
        await run(`(() => {const inputs=document.querySelectorAll('input');const password=document.querySelector('input[type=password]');const user=Array.from(inputs).find(x=>x.type==='email'||x.type==='text');for(const [input,value] of [[user,${JSON.stringify(email)}],[password,'Browser-Test-123!']]){input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}));}document.querySelector('form').requestSubmit();})()`);
        await until(()=>run('location.pathname === '+JSON.stringify(destination)), 'authenticated dashboard');
    }
    if (process.argv.includes('--educators') || process.argv.includes('--students')) {
        const students = process.argv.includes('--students');
        const resource = students ? 'student-enrollments' : 'educator-recaps';
        const statClass = students ? '.se-stat' : '.er-stat';
        await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-color-scheme',value:'light'}]});
        await send('Emulation.setDeviceMetricsOverride',{width:students ? 1842 : 1672,height:941,deviceScaleFactor:1,mobile:false});
        await login('school-browser@example.test','/app');
        await send('Page.navigate',{url:'http://127.0.0.1:8012/app/'+resource});
        await until(()=>run('!!document.querySelector('+JSON.stringify(statClass)+')'), resource);
        await sleep(600);
        assert.equal(await run('document.querySelectorAll('+JSON.stringify(statClass)+').length'),4);
        assert.equal(await run('document.querySelector('+JSON.stringify(statClass+' strong')+').textContent'),students ? '74' : '4');
        const capture=await send('Page.captureScreenshot',{format:'png'});
        const screenshot=path.join(root,'storage/framework/testing/'+resource+'-desktop.png');
        fs.writeFileSync(screenshot,Buffer.from(capture.data,'base64'));
        await send('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:true});
        await sleep(300);
        const mobileCapture=await send('Page.captureScreenshot',{format:'png'});
        fs.writeFileSync(path.join(root,'storage/framework/testing/'+resource+'-mobile.png'),Buffer.from(mobileCapture.data,'base64'));
        if (students) {
            await run(`Array.from(document.querySelectorAll('button')).find(button=>button.textContent.trim()==='Lihat').click()`);
            await until(()=>run('document.body.innerText.includes("Detail Jumlah Siswa")'), 'read-only student detail');
            console.log('PASS: Lihat opens student recap detail');
        }
        assert.deepEqual(errors,[]);
        console.log('PASS: school admin '+resource+' renders four cards and existing data without JavaScript errors. Screenshot: '+screenshot);
        return;
    }
    await login();
    await send('Page.navigate',{url:'http://127.0.0.1:8012/admin/presensi/pengaturan?school=2'});
    const editor='Alpine.$data(document.querySelector("[data-geofence-editor]"))';
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'map');
    await run(`${editor}.map.fire('click',{latlng:L.latLng(-7.9767346,110.6157939)})`);
    await run(`(() => {const input=Array.from(document.querySelectorAll('input')).find(x=>x.getAttribute('wire:model.live.debounce.250ms')==='radiusMeters');input.value='350';input.dispatchEvent(new Event('input',{bubbles:true}));})()`);
    await sleep(500);
    const zoomStable = await run(`(async () => {
        const map = Alpine.raw(${editor}.map);
        const layers = [];
        map.eachLayer(layer => { if (layer instanceof L.Marker || layer instanceof L.Circle) layers.push(layer); });
        const coordinates = JSON.stringify(layers.map(layer => layer.getLatLng()));
        for (const zoom of [19, 16, 18, 19]) {
            map.setZoom(zoom, {animate:false});
            await new Promise(resolve => setTimeout(resolve, 80));
            if (layers.some(layer => !map.hasLayer(layer))) return false;
            if (coordinates !== JSON.stringify(layers.map(layer => layer.getLatLng()))) return false;
            for (const layer of layers) {
                const point = map.latLngToContainerPoint(layer.getLatLng());
                if (map.containerPointToLatLng(point).distanceTo(layer.getLatLng()) > 1) return false;
            }
        }
        return layers.length >= 2 && map.getMaxZoom() === 19;
    })()`);
    assert.equal(zoomStable, true, 'zoom preserves layers and geographic coordinates');
    await run('document.querySelector("form.as-form").requestSubmit()');
    await until(()=>run('document.body.innerText.includes("Pengaturan presensi disimpan")'), 'save notification');
    await send('Page.reload');
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'reloaded map');
    const saved=await run(`({lat:${editor}.latitude,lng:${editor}.longitude,radius:${editor}.radius,url:location.search})`);
    assert.equal(Number(saved.lat),-7.9767346);assert.equal(Number(saved.lng),110.6157939);assert.equal(Number(saved.radius),350);assert.match(saved.url,/school=2/);
    console.log('PASS: click, save notification, reload, coordinates, radius and selected school', saved);
    await run(`${editor}.polygonMode=true;${editor}.map.fire('click',{latlng:L.latLng(-7.977,110.615)});${editor}.map.fire('click',{latlng:L.latLng(-7.977,110.617)});${editor}.map.fire('click',{latlng:L.latLng(-7.975,110.617)});`);
    await run('document.querySelector("form.as-form").requestSubmit()');
    await until(()=>run('document.body.innerText.includes("Pengaturan presensi disimpan")'), 'polygon save');
    await send('Page.reload');
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'polygon reload');
    assert.equal(await run(`${editor}.points.length`),3);
    console.log('PASS: polygon survives reload');
    await run(`(async()=>{const form=document.createElement('form');form.method='POST';form.action='/admin/logout';const token=document.createElement('input');token.name='_token';token.value=document.querySelector('meta[name="csrf-token"]').content;form.appendChild(token);document.body.appendChild(form);form.submit();})()`);
    await until(()=>run('location.pathname.includes("login")'), 'logout');
    await login();
    await send('Page.navigate',{url:'http://127.0.0.1:8012/admin/presensi/pengaturan?school=2'});
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'login again map');
    assert.equal(Number(await run(`${editor}.latitude`)),-7.9767346);
    assert.equal(await run(`${editor}.points.length`),3);
    console.log('PASS: location survives logout/login');
    const stored=JSON.parse(execFileSync(php,['tests/browser/inspect.php'],{cwd:root,env,encoding:'utf8'}));
    assert.equal(Number(stored.office_latitude),-7.9767346);assert.equal(Number(stored.office_longitude),110.6157939);assert.equal(stored.radius_meters,350);assert.equal(stored.geofence_polygon.length,3);
    console.log('PASS: direct database verification');
    await send('Page.navigate',{url:'http://127.0.0.1:8012/admin/sk-yayasan/dashboard'});
    await until(()=>run('document.body.innerText.includes("Belum ada pengajuan SK.")'), 'SK dashboard');
    assert.deepEqual(errors,[]);
    console.log('PASS: SK dashboard, empty state, no JavaScript exceptions');
    await run(`(() => {const form=document.createElement('form');form.method='POST';form.action='/admin/logout';const token=document.createElement('input');token.name='_token';token.value=document.querySelector('meta[name="csrf-token"]').content;form.appendChild(token);document.body.appendChild(form);form.submit();})()`);
    await until(()=>run('location.pathname.includes("login")'), 'admin logout');
    await login('school-browser@example.test','/app');
    await send('Page.navigate',{url:'http://127.0.0.1:8012/app/presensi/pengaturan'});
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'school admin map');
    assert.equal(Number(await run(`${editor}.latitude`)),-7.9767346);
    await run(`${editor}.map.fire('click',{latlng:L.latLng(-7.9768000,110.6158000)})`);
    await run('document.querySelector("form.as-form").requestSubmit()');
    await until(()=>run('document.body.innerText.includes("Pengaturan presensi disimpan")'), 'school save');
    await send('Page.reload');
    await until(()=>run('!!document.querySelector(".leaflet-container")'), 'school reload');
    assert.equal(Number(await run(`${editor}.latitude`)),-7.9768);
    assert.deepEqual(errors,[]);
    console.log('PASS: school admin can load, move, save and reload location in app panel');
    await run(`(() => {const form=document.createElement('form');form.method='POST';form.action='/app/logout';const token=document.createElement('input');token.name='_token';token.value=document.querySelector('meta[name="csrf-token"]').content;form.appendChild(token);document.body.appendChild(form);form.submit();})()`);
    await until(()=>run('location.pathname.includes("login")'), 'school logout');
    await login('teacher-browser@example.test','/app');
    await send('Browser.grantPermissions',{origin:'http://127.0.0.1:8012',permissions:['geolocation']});
    await send('Emulation.setGeolocationOverride',{latitude:-7.9768,longitude:110.6158,accuracy:800});
    await send('Page.navigate',{url:'http://127.0.0.1:8012/app/presensi-saya'});
    await until(()=>run('!!document.querySelector("[x-data^=attendanceGps]")'), 'teacher attendance');
    await run(`Array.from(document.querySelectorAll('button')).find(x=>x.textContent.trim()==='Masuk'||x.textContent.trim()==='Presensi Masuk').click()`);
    await until(()=>run('document.body.innerText.includes("Memperbaiki akurasi GPS")'), 'waiting for better GPS');
    await send('Emulation.setGeolocationOverride',{latitude:-7.9768,longitude:110.6158,accuracy:10});
    await until(()=>run('document.body.innerText.includes("Tercatat")||document.body.innerText.includes("Presensi masuk berhasil")'), 'teacher check-in');
    assert.deepEqual(errors,[]);
    console.log('PASS: teacher waits for GPS accuracy to improve and checks in successfully');
    console.log('Fixture database: '+database);
}
main().catch(async error=>{console.error(error);if(diagnose)await diagnose();process.exitCode=1;}).finally(()=>{socket?.close();browser?.kill();server?.kill();});
