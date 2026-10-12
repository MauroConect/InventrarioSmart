const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const vm = require('node:vm');

const view = readFileSync(resolve(__dirname, '../../resources/views/dashboard.blade.php'), 'utf8');
const script = view.match(/<script>([\s\S]*?)<\/script>/)[1];

function harness(enabled, response = []) {
    const requests = [], intervals = [], cleared = [];
    const context = {
        axios: { get: async (url) => {
            requests.push(url);
            if (url.includes('cheques') && response instanceof Error) throw response;
            return { data: url.includes('cheques') ? response : { total_productos: 5 } };
        } },
        localStorage: { getItem: () => null },
        setInterval: (callback) => { intervals.push(callback); return intervals.length; },
        clearInterval: (id) => cleared.push(id),
        console,
    };
    vm.createContext(context);
    vm.runInContext(script.replace(/@json\(\$dashboardCheques\)/g, String(enabled)), context);
    return { dashboard: context.dashboard(), requests, intervals, cleared };
}

test('disabled module makes no cheque request at startup or refresh', async () => {
    const h = harness(false);
    await h.dashboard.init();
    await h.intervals[0]();
    await h.dashboard.fetchCheques();
    assert.deepEqual(h.requests, ['/api/dashboard/estadisticas', '/api/dashboard/estadisticas']);
    assert.equal(h.dashboard.loadingCheques, false);
    assert.equal(h.dashboard.stats.total_productos, 5);
    h.dashboard.destroy();
    assert.deepEqual(h.cleared, [1]);
});

test('enabled module loads results and finishes its loading state', async () => {
    const h = harness(true, [{ id: 10, numero_cheque: 'FICTICIO' }]);
    await h.dashboard.init();
    assert.deepEqual(h.requests, ['/api/dashboard/estadisticas', '/api/cheques-proximos-vencer']);
    assert.equal(h.dashboard.cheques[0].id, 10);
    assert.equal(h.dashboard.loadingCheques, false);
    assert.equal(h.dashboard.errorCheques, '');
});

test('module removed or permission lost stops future requests', async () => {
    for (const status of [403, 404]) {
        const error = new Error('Access denied'); error.response = { status };
        const h = harness(true, error);
        await h.dashboard.init();
        await h.intervals[0]();
        assert.equal(h.requests.filter(url => url.includes('cheques')).length, 1);
        assert.equal(h.dashboard.chequesEnabled, false);
        assert.equal(h.dashboard.loadingCheques, false);
        assert.equal(h.dashboard.errorCheques, '');
    }
});

test('temporary failure exposes an error instead of an empty or loading result', async () => {
    const h = harness(true, new Error('Temporary connection failure'));
    await h.dashboard.init();
    assert.equal(h.dashboard.chequesEnabled, true);
    assert.equal(h.dashboard.loadingCheques, false);
    assert.match(h.dashboard.errorCheques, /Volvé a intentar/);
    assert.equal(h.dashboard.cheques.length, 0);
    await h.dashboard.fetchCheques();
    assert.equal(h.requests.filter(url => url.includes('cheques')).length, 2);
});
