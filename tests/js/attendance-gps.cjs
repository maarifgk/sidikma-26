const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function setup(maxAccuracy = 100) {
    const positions = []; const timers = {}; let error, watchOptions, cleared = false;
    const values = {}; let submitted = 0;
    const context = {window:{}, navigator:{geolocation:{watchPosition(fn, onError, options){positions.push(fn);error=onError;watchOptions=options;return positions.length;},clearWatch(){cleared=true;}}},setTimeout(fn, ms){timers[ms]=fn;return ms;},clearTimeout(){}};
    vm.runInNewContext(fs.readFileSync('public/js/attendance-gps.js','utf8'),context);
    const state = context.window.attendanceGps({maxAccuracy});
    state.$wire = {$set(k,v,live){assert.equal(live,false);values[k]=v;},async checkIn(){submitted++;}};
    return {state,values,position(accuracy){positions.at(-1)({coords:{latitude:-7.97,longitude:110.61,accuracy}});},latePosition(index, accuracy){positions[index]({coords:{latitude:-7.97,longitude:110.61,accuracy}});},error(code){error(code === undefined ? {} : {code});},status(){timers[5000]();},timeout(){timers[15000]();},options:()=>watchOptions,submitted:()=>submitted,cleared:()=>cleared};
}
test('waits for a precise fix and submits one request with all GPS values',async()=>{
    const gps=setup();const pending=gps.state.submit('checkIn',true);
    gps.position(1200);assert.equal(gps.submitted(),0);assert.equal(gps.state.locating,true);
    gps.position(15);await pending;
    assert.equal(gps.submitted(),1);assert.equal(gps.values.accuracy,15);assert.equal(gps.cleared(),true);
});

test('uses staged status and aligned fifteen second browser timeout', async () => {
    const gps = setup();
    const pending = gps.state.submit('checkIn', true);
    assert.match(gps.state.gpsMessage, /Mencari lokasi GPS/);
    gps.status();
    assert.match(gps.state.gpsMessage, /GPS masih mencari lokasi/);
    assert.equal(gps.options().timeout, 15000);
    gps.position(10);
    await pending;
    assert.match(gps.state.gpsMessage, /GPS ditemukan\. Akurasi: 10 meter/);
});
test('poor accuracy times out without submitting or weakening school limits',async()=>{
    const gps=setup();const pending=gps.state.submit('checkIn',true);
    gps.position(800);gps.timeout();await pending;
    assert.equal(gps.submitted(),0);assert.match(gps.state.gpsMessage,/800 meter, belum memenuhi batas 100 meter/);
    assert.equal(gps.state.locating,false);assert.equal(gps.cleared(),true);
});

test('uses the configured school accuracy limit for the recorded 124 meter fix',async()=>{
    const gps=setup(150);const pending=gps.state.submit('checkIn',true);
    gps.position(124);await pending;
    assert.equal(gps.submitted(),1);
    assert.equal(gps.values.accuracy,124);
});

test('cancelling a GPS search stops the pending submission and allows a retry',async()=>{
    const gps=setup(25);const pending=gps.state.submit('checkIn',true);
    gps.position(124);gps.state.cancel();await pending;
    assert.equal(gps.submitted(),0);
    assert.equal(gps.cleared(),true);
    assert.equal(gps.state.locating,false);
    const retry=gps.state.submit('checkIn',true);gps.position(10);await retry;
    assert.equal(gps.submitted(),1);
});

test('a submission in progress cannot be sent again and failures allow retry',async()=>{
    const gps=setup();let reject;
    gps.state.$wire.checkIn=()=>new Promise((resolve,fail)=>{reject=fail;});
    const pending=gps.state.submit('checkIn',false);
    assert.equal(gps.state.submitting,true);
    await gps.state.submit('checkIn',false);
    reject(new Error('Network unavailable'));await pending;
    assert.equal(gps.state.submitting,false);
    assert.match(gps.state.gpsMessage,/Periksa koneksi/);
    gps.state.$wire.checkIn=async()=>{};
    await gps.state.submit('checkIn',false);
    assert.equal(gps.state.submitting,false);
});

for (const [name, code, message] of [
    ['permission denied', 1, /Izin lokasi ditolak/],
    ['position unavailable', 2, /Lokasi belum tersedia/],
    ['geolocation timeout', 3, /GPS terlalu lama merespons/],
    ['unknown error code', 99, /Lokasi tidak dapat ditemukan/],
    ['error without a code', undefined, /Lokasi tidak dapat ditemukan/],
]) {
    test(`handles ${name} and allows retry`, async () => {
        const gps = setup();
        const pending = gps.state.submit('checkIn', true);
        gps.error(code);
        await pending;
        assert.equal(gps.state.locating, false);
        assert.equal(gps.cleared(), true);
        assert.equal(gps.submitted(), 0);
        assert.match(gps.state.gpsMessage, message);
        const retry = gps.state.submit('checkIn', true);
        gps.position(10);
        await retry;
        assert.equal(gps.submitted(), 1);
    });
}

test('late callbacks after timeout or cancel cannot affect a new search', async () => {
    const gps = setup(25);
    const first = gps.state.submit('checkIn', true);
    gps.position(124);
    gps.timeout();
    await first;
    const firstMessage = gps.state.gpsMessage;
    const second = gps.state.submit('checkIn', true);
    gps.latePosition(0, 10);
    assert.equal(gps.state.locating, true);
    assert.notEqual(gps.state.gpsMessage, firstMessage);
    gps.state.cancel();
    await second;
    assert.equal(gps.state.locating, false);
    assert.equal(gps.submitted(), 0);
});

test('late poor and precise callbacks from search A cannot affect search B', async () => {
    const gps = setup(25);
    const first = gps.state.submit('checkIn', true);
    gps.position(200);
    gps.state.cancel();
    await first;

    const second = gps.state.submit('checkIn', true);
    const secondMessage = gps.state.gpsMessage;
    gps.latePosition(0, 80);
    assert.equal(gps.state.gpsMessage, secondMessage);
    assert.equal(gps.state.locating, true);
    assert.equal(gps.values.accuracy, undefined);

    gps.latePosition(0, 5);
    assert.equal(gps.state.locating, true);
    assert.equal(gps.values.accuracy, undefined);

    gps.position(10);
    await second;
    assert.equal(gps.submitted(), 1);
    assert.equal(gps.values.accuracy, 10);
});

test('destroying an active search invalidates its callbacks and cleans up', async () => {
    const gps = setup();
    const pending = gps.state.submit('checkIn', true);
    gps.state.destroy();
    await pending;
    assert.equal(gps.state.locating, false);
    assert.equal(gps.cleared(), true);
    gps.position(10);
    assert.equal(gps.submitted(), 0);
});
