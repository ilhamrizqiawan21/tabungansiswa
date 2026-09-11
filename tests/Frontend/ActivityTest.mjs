import test from 'node:test';
import assert from 'node:assert/strict';
import { createActivityTracker, activity, trackPageTransition, pageTransitionActive, installActivityEvents, downloadFile, downloadFilename, activityError, dismissActivityError } from '../../resources/js/activity.ts';
import { confirmAction, answerConfirmation, confirmation } from '../../resources/js/confirmation.ts';

test('completing an older request does not hide a newer request or an active download', () => {
    const state = createActivityTracker();
    state.start('page', 'Memuat halaman…');
    state.start('download', 'Menyiapkan file…');
    state.finish('page');
    assert.equal(state.pending.value, true);
    assert.equal(state.message.value, 'Menyiapkan file…');
    state.finish('page');
    state.finish('download');
    assert.equal(state.pending.value, false);
});

test('router activity ignores prefetch and clears on cancellation, failure, and disposal', () => {
    const original = globalThis.document;
    const originalWindow = globalThis.window;
    globalThis.window = {};
    globalThis.document = new EventTarget();
    const dispose = installActivityEvents();
    const emit = (name, detail) => document.dispatchEvent(new CustomEvent(`inertia:${name}`, {detail}));
    const visit = {id:'save',method:'post',url:new URL('http://localhost/backup'),prefetch:false,showProgress:true};
    try {
        emit('start', {visit:{...visit,id:'prefetch',prefetch:true}});
        assert.equal(activity.pending.value, false);
        emit('start', {visit});
        assert.equal(activity.message.value, 'Membuat backup data…');
        emit('finish', {visit:{...visit,cancelled:true}});
        assert.equal(activity.pending.value, false);
        emit('start', {visit});
        emit('networkError', {error:new Error('offline')});
        emit('finish', {visit});
        assert.equal(activity.pending.value, false);
        assert.match(activityError.value, /Koneksi/);
        dispose();
        emit('start', {visit});
        assert.equal(activity.pending.value, false);
    } finally { dispose(); dismissActivityError(); globalThis.document = original; globalThis.window = originalWindow; }
});

test('download remains busy until the file body arrives and preserves its server filename', async () => {
    let complete;
    let clicked = '';
    const originalFetch = globalThis.fetch;
    const originalDocument = globalThis.document;
    const originalWindow = globalThis.window;
    globalThis.fetch = async () => ({ok:true,headers:new Headers({'Content-Disposition':"attachment; filename*=UTF-8''laporan%20siswa.xlsx"}),blob:()=>new Promise(resolve=>{complete=resolve;})});
    globalThis.document = {body:{append(){}},createElement:()=>({href:'',download:'',click(){clicked=this.download;},remove(){}})};
    globalThis.window = {setTimeout:callback=>callback()};
    try {
        const task = downloadFile('/laporan/export-xlsx');
        await Promise.resolve();
        assert.equal(activity.pending.value, true);
        assert.equal(clicked, '');
        complete(new Blob(['file']));
        await task;
        assert.equal(clicked, 'laporan siswa.xlsx');
        assert.equal(activity.pending.value, false);
    } finally { globalThis.fetch=originalFetch;globalThis.document=originalDocument;globalThis.window=originalWindow; }
});

test('failed downloads and HTML error pages clear loading and display retry feedback', async () => {
    const originalFetch = globalThis.fetch;
    try {
        globalThis.fetch = async () => {throw new Error('offline');};
        await downloadFile('/backup/file.zip');
        assert.equal(activity.pending.value, false);
        assert.match(activityError.value, /Unduhan gagal/);
        globalThis.fetch = async () => ({ok:true,headers:new Headers({'Content-Type':'text/html'})});
        await downloadFile('/backup/file.zip');
        assert.equal(activity.pending.value, false);
        assert.match(activityError.value, /Unduhan gagal/);
        assert.equal(downloadFilename('attachment; filename="laporan.pdf"'), 'laporan.pdf');
    } finally { globalThis.fetch=originalFetch;dismissActivityError(); }
});

test('dismissed or superseded deletion confirmations never approve an action', async () => {
    const first = confirmAction('Hapus siswa?');
    const second = confirmAction('Hapus kelas?');
    assert.equal(await first, false);
    assert.equal(confirmation.value, 'Hapus kelas?');
    answerConfirmation(false);
    assert.equal(await second, false);
    const approved = confirmAction('Hapus periode?');
    answerConfirmation(true);
    assert.equal(await approved, true);
    assert.equal(confirmation.value, null);
});


test('an interrupted page transition does not clear the next transition', async () => {
    let firstDone, secondDone;
    trackPageTransition({finished:new Promise(resolve=>{firstDone=resolve;})});
    trackPageTransition({finished:new Promise(resolve=>{secondDone=resolve;})});
    firstDone();
    await Promise.resolve();
    assert.equal(pageTransitionActive.value, true);
    secondDone();
    await Promise.resolve();
    assert.equal(pageTransitionActive.value, false);
});
