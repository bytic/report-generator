// Run with: node --test tests/js/export.test.cjs
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const { test } = require('node:test');
const vm = require('node:vm');

const root = path.resolve(__dirname, '../..');
const html = execFileSync('php', ['-r', `
    require 'src/AsyncReport/ReportJobState.php';
    $state = \\ByTIC\\ReportGenerator\\AsyncReport\\ReportJobState::initial('TestReport', [], 'xlsx');
    $token = 'initial-token';
    $progressUrl = '/reports/reportProgress?id_event=42';
    $downloadUrl = '/reports/reportDownload?id_event=42';
    $totalRows = null;
    require 'resources/views/admin/report-generator/export.php';
`], { cwd: root, encoding: 'utf8', env: { ...process.env, XDEBUG_MODE: 'off' } });
const script = html.match(/<script>([\s\S]*?)<\/script>/)[1];

function createPage({ downloadStatus = 200, referrer = 'https://example.test/entries?event=42', historyLength = 2 } = {}) {
    const elements = new Map();
    const events = {};
    const requests = [];
    const timers = [];
    const downloads = [];
    const revoked = [];
    let now = 0;
    let historyBacks = 0;
    let finishDownload;
    const file = new Promise(resolve => { finishDownload = resolve; });

    function element(id) {
        if (!elements.has(id)) {
            elements.set(id, {
                hidden: ['rg-download', 'rg-countdown'].includes(id),
                style: {},
                classList: { remove() {} },
                addEventListener(event, handler) { this[event] = handler; },
                click() { downloads.push({ href: this.href, filename: this.download }); },
            });
        }
        return elements.get(id);
    }

    const location = { origin: 'https://example.test', href: 'https://example.test/export' };
    const BrowserURL = class extends URL {};
    BrowserURL.createObjectURL = blob => {
        assert.equal(blob, 'report-content');
        return 'blob:report';
    };
    BrowserURL.revokeObjectURL = url => revoked.push(url);

    vm.runInNewContext(script, {
        document: { referrer, getElementById: element, querySelector: element },
        window: {
            location,
            history: { length: historyLength, back() { historyBacks++; } },
            addEventListener(event, handler) { events[event] = handler; },
        },
        URL: BrowserURL,
        URLSearchParams,
        Date: { now: () => now },
        setTimeout(handler, delay) { timers.push({ handler, delay }); },
        setInterval(handler, delay) { timers.push({ handler, delay, interval: true }); return handler; },
        clearInterval(handler) {
            const timer = timers.find(timer => timer.handler === handler);
            if (timer) timer.cleared = true;
        },
        async fetch(url, options) {
            requests.push({ url, options });
            if (options) {
                return {
                    ok: true,
                    json: async () => ({ done: true, token: 'final-token', processed: 50, total: 50, percent: 100 }),
                };
            }
            return {
                ok: downloadStatus === 200,
                status: downloadStatus,
                headers: { get: () => "attachment; filename*=utf-8''entries%20report.xlsx" },
                blob: () => file,
            };
        },
    });

    return {
        element, events, requests, timers, downloads, revoked, location, finishDownload,
        get historyBacks() { return historyBacks; },
        setTime(value) { now = value; },
    };
}

async function flush() {
    await new Promise(resolve => setImmediate(resolve));
}

test('download button reuses the file and return countdown starts only after the download', async () => {
    const page = createPage();
    await flush();
    assert.equal(page.element('rg-download').hidden, true);
    assert.equal(page.element('rg-countdown').hidden, true);
    assert.equal(page.requests[0].options.body, '_report_token=initial-token');

    const downloadTimer = page.timers.find(timer => timer.delay === 600);
    const pendingDownload = downloadTimer.handler();
    await flush();
    assert.equal(page.requests[1].url, '/reports/reportDownload?id_event=42&_report_token=final-token');
    assert.equal(page.timers.some(timer => timer.interval), false);

    page.finishDownload('report-content');
    await pendingDownload;
    assert.equal(page.element('rg-download').hidden, false);
    assert.deepEqual(page.downloads, [{ href: 'blob:report', filename: 'entries report.xlsx' }]);
    assert.match(page.element('rg-countdown').textContent, /15 seconds/);
    page.element('rg-download').click();
    assert.equal(page.downloads.length, 2);
    assert.equal(page.requests.length, 2);

    const countdown = page.timers.find(timer => timer.interval);
    page.setTime(14000);
    countdown.handler();
    assert.match(page.element('rg-countdown').textContent, /1 seconds/);
    assert.equal(page.location.href, 'https://example.test/export');
    page.setTime(15000);
    countdown.handler();
    assert.equal(page.location.href, 'https://example.test/entries?event=42');
    assert.equal(countdown.cleared, true);

    page.events.pagehide();
    assert.deepEqual(page.revoked, ['blob:report']);
});

test('download failures are visible and do not start a return timer', async () => {
    const page = createPage({ downloadStatus: 404 });
    await flush();
    await page.timers.find(timer => timer.delay === 600).handler();
    assert.match(page.element('rg-error').textContent, /Download failed.*404/);
    assert.equal(page.element('rg-error').style.display, 'block');
    assert.equal(page.element('rg-download').hidden, true);
    assert.equal(page.element('rg-countdown').hidden, true);
    assert.equal(page.timers.some(timer => timer.interval), false);
    assert.equal(page.downloads.length, 0);
});

test('Back works before completion and preserves the original page query', () => {
    const page = createPage();
    page.element('rg-back').click();
    assert.equal(page.location.href, 'https://example.test/entries?event=42');
});

test('Back uses history without a referrer and root without history', () => {
    const page = createPage({ referrer: '' });
    page.element('rg-back').click();
    assert.equal(page.historyBacks, 1);
    const directPage = createPage({ referrer: '', historyLength: 1 });
    directPage.element('rg-back').click();
    assert.equal(directPage.location.href, '/');
});

test('Back does not navigate directly to an external referrer', () => {
    const page = createPage({ referrer: 'https://external.test/page', historyLength: 1 });
    page.element('rg-back').click();
    assert.equal(page.location.href, '/');
});
