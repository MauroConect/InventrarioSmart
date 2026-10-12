const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const vm = require('node:vm');

const source = readFileSync(resolve(__dirname, '../../public/js/marketing-configurator.js'), 'utf8');
function demo(keys = ['catalog.scanner', 'catalog.weighted', 'sales.open_orders', 'services.appointments', 'finance.credit', 'finance.cheques']) {
    const context = {};
    vm.createContext(context);
    vm.runInContext(source, context);
    return context.moduleConfigurator(keys.map(key => ({ key, name: key })));
}

test('profiles select the appropriate available functions and preview', () => {
    const state = demo();
    assert.equal(state.isEnabled('catalog.scanner'), true);
    state.chooseProfile('gastronomia');
    assert.equal(state.selected.length, 2);
    assert.equal(state.isEnabled('sales.open_orders'), true);
    assert.match(state.profileImage, /comercio-heladeria/);
    state.chooseProfile('servicios');
    assert.equal(state.selected.length, 1);
    assert.equal(state.isEnabled('services.appointments'), true);
    assert.match(state.profileTitle, /atender/);
});

test('optional modules toggle independently and reset to the chosen example', () => {
    const state = demo();
    state.toggleModule('finance.credit');
    assert.equal(state.selectedModules.length, 2);
    state.toggleModule('catalog.scanner');
    assert.equal(state.selected.length, 1);
    assert.equal(state.isEnabled('finance.credit'), true);
    state.chooseProfile('retail');
    assert.equal(state.selected.length, 1);
    assert.equal(state.isEnabled('catalog.scanner'), true);
    assert.equal(state.isEnabled('finance.credit'), false);
});

test('base modules and unavailable features cannot be toggled into the example', () => {
    const state = demo(['catalog.scanner']);
    state.toggleModule('core.cash');
    state.toggleModule('sales.tables');
    assert.equal(state.selected.length, 1);
    state.chooseProfile('gastronomia');
    assert.equal(state.selected.length, 0);
    state.chooseProfile('unknown');
    assert.equal(state.activeProfile, 'gastronomia');
});
