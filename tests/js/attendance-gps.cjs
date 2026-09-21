const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function setup(maxAccuracy = 100) {
    let position, timeout, cleared = false;
    const values = {}; let submitted = 0;
    const context = {window:{}, navigator:{geolocation:{watchPosition(fn){position=fn;return 1;},clearWatch(){cleared=true;}}},setTimeout(fn){timeout=fn;return 1;},clearTimeout(){}};
    vm.runInNewContext(fs.readFileSync('public/js/attendance-gps.js','utf8'),context);
    const state = context.window.attendanceGps({maxAccuracy});
    state.$wire = {$set(k,v,live){assert.equal(live,false);values[k]=v;},async checkIn(){submitted++;}};
    return {state,values,position(accuracy){position({coords:{latitude:-7.97,longitude:110.61,accuracy}});},timeout(){timeout();},submitted:()=>submitted,cleared:()=>cleared};
}
test('waits for a precise fix and submits one request with all GPS values',async()=>{
    const gps=setup();const pending=gps.state.submit('checkIn',true);
    gps.position(1200);assert.equal(gps.submitted(),0);assert.equal(gps.state.locating,true);
    gps.position(15);await pending;
    assert.equal(gps.submitted(),1);assert.equal(gps.values.accuracy,15);assert.equal(gps.cleared(),true);
});
test('poor accuracy times out without submitting or weakening school limits',async()=>{
    const gps=setup();const pending=gps.state.submit('checkIn',true);
    gps.position(800);gps.timeout();await pending;
    assert.equal(gps.submitted(),0);assert.match(gps.state.gpsMessage,/800 m; batas sekolah 100 m/);
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
