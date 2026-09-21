const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

test('map location selection saves coordinates without creating an incomplete polygon', async () => {
    const source = fs.readFileSync('resources/views/filament/admin/pages/attendance-settings.blade.php', 'utf8');
    assert.match(source, /<form wire:submit="saveSettings"/);
    assert.doesNotMatch(source, /geofence-save/);
    assert.match(source, /JSON\.parse\(editorElement\.dataset\.geofenceState\)/);
    const script = source.split('<script>')[1].split('const initializeGeofenceEditor')[0];
    const handlers = {};
    let center;
    const map = {
        setView(point) { center = point; return this; },
        scrollWheelZoom: { enable() {} }, dragging: { enable() {} },
        on(event, callback) { handlers[event] = callback; },
    };
    const L = { map: () => map, tileLayer: () => ({ addTo() {} }) };
    const context = { window: { L }, L, setTimeout() {} };
    vm.runInNewContext(script, context);
    const editor = context.window.geofenceEditor([], null, null, 100);
    editor.$refs = { map: { replaceChildren() {} } };
    editor.$watch = () => {};
    editor.$nextTick = callback => callback();
    editor.addPanControl = editor.draw = editor.drawOffice = () => {};
    const saved = { polygonPoints: [] };
    editor.$wire = {
        $set(key, value, live) { assert.equal(live, false); saved[key] = value; },
    };
    editor.init();
    handlers.click({ latlng: { lat: -7.9767346, lng: 110.6157939 } });
    assert.equal(editor.points.length, 0);
    assert.equal(saved.officeLatitude, '-7.9767346');
    assert.equal(saved.officeLongitude, '110.6157939');
    assert.equal(saved.polygonPoints.length, 0);
    editor.polygonMode = true;
    handlers.click({ latlng: { lat: -7.977, lng: 110.616 } });
    assert.equal(editor.points.length, 1);
    assert.equal(saved.polygonPoints.length, 1);
    assert.equal(editor.latitude, '-7.9767346');
    editor.removeLast();
    assert.equal(saved.polygonPoints.length, 0);
    editor.map = null;
    editor.latitude = saved.officeLatitude;
    editor.longitude = saved.officeLongitude;
    editor.initializeMap();
    assert.equal(center[0], -7.9767346);
    assert.equal(center[1], 110.6157939);
});
