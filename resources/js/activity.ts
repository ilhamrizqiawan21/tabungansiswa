import { computed, reactive, readonly, ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function createActivityTracker() {
    const tasks = reactive(new Map<string, string>());
    const message = computed(() => [...tasks.values()].at(-1) ?? 'Memuat…');
    const pending = computed(() => tasks.size > 0);

    return {
        pending,
        message,
        start(id: string, label: string) { tasks.set(id, label); },
        finish(id: string) { tasks.delete(id); },
        clear() { tasks.clear(); },
    };
}

export const activity = createActivityTracker();
export const pageTransitionActive = ref(false);
let transitionVersion = 0;
export function trackPageTransition(transition: Pick<ViewTransition, 'finished'>) {
    const version = ++transitionVersion;
    pageTransitionActive.value = true;
    const finish = () => { if (version === transitionVersion) pageTransitionActive.value = false; };
    transition.finished.then(finish, finish);
}
const error = ref('');
export const activityError = readonly(error);
export function dismissActivityError() { error.value = ''; }
export function reportActivityError(message: string) { error.value = message; }

export function installActivityEvents() {
    const remove = [
        router.on('start', ({ detail: { visit } }) => {
            if (visit.prefetch || visit.showProgress === false) return;
            const path = visit.url.pathname;
            const label = visit.method === 'get' ? 'Memuat halaman…'
                : path === '/backup' ? 'Membuat backup data…'
                : path.endsWith('/import') ? 'Mengimpor data siswa…'
                : visit.method === 'delete' ? 'Menghapus data…'
                : path.startsWith('/approval/') ? 'Memproses persetujuan…'
                : 'Menyimpan perubahan…';
            dismissActivityError();
            activity.start(visit.id, label);
        }),
        router.on('finish', ({ detail: { visit } }) => activity.finish(visit.id)),
        router.on('networkError', () => reportActivityError('Koneksi ke aplikasi terputus. Periksa koneksi, lalu coba lagi.')),
    ];
    return () => { remove.forEach(unsubscribe => unsubscribe()); activity.clear(); };
}

export function downloadFilename(disposition: string): string {
    const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
    const plain = disposition.match(/filename="([^"]+)"/i)?.[1] ?? disposition.match(/filename=([^;]+)/i)?.[1];
    let name = plain?.trim() || 'unduhan';
    if (encoded) {
        try { name = decodeURIComponent(encoded); } catch { /* Use the plain filename when the extended header is invalid. */ }
    }
    return name.replace(/[\\/]/g, '-');
}

export async function downloadFile(href: string, label = 'Menyiapkan unduhan…') {
    const id = `download-${crypto.randomUUID()}`;
    activity.start(id, label);
    dismissActivityError();
    try {
        const response = await fetch(href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const disposition = response.headers.get('Content-Disposition') ?? '';
        if (!response.ok || !/attachment/i.test(disposition)) {
            throw new Error('File belum dapat diunduh. Silakan coba lagi.');
        }
        const file = await response.blob();
        const url = URL.createObjectURL(file);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = downloadFilename(disposition);
        document.body.append(anchor);
        anchor.click();
        anchor.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    } catch {
        reportActivityError('Unduhan gagal. Pastikan aplikasi masih berjalan, lalu coba kembali.');
    } finally {
        activity.finish(id);
    }
}
