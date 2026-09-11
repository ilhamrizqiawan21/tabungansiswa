<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { activity, pageTransitionActive } from '../activity';

const open = ref(false);
const page = usePage();
const animateEntry = ref(!pageTransitionActive.value);
watch(() => page.url, () => { animateEntry.value = !pageTransitionActive.value; });
const settings = computed(() => (page.props.appSettings as {
    teacherName?: string; schoolName?: string; schoolLogo?: string | null;
}) ?? {});
const logoFailed = ref(false);
watch(() => settings.value.schoolLogo, () => { logoFailed.value = false; });
const items = [
    { label: 'Backup data', href: '/backup', icon: 'M4 16v5h16v-5M12 3v12m-5-5 5 5 5-5' },
    { label: 'Dashboard', href: '/dashboard', icon: 'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z' },
    { label: 'Pengaturan', href: '/pengaturan', icon: 'M4 7h16M4 17h16M8 4v6m8 4v6' },
    { label: 'Siswa', href: '/master/siswa', icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m8 .13a4 4 0 0 1 0 7.75' },
    { label: 'Transaksi', href: '/transaksi', icon: 'M7 3v18m-4-4 4 4 4-4M17 21V3m-4 4 4-4 4 4' },
    { label: 'Approval', href: '/approval', icon: 'm7 12 3 3 7-7M21 12a9 9 0 1 1-5-8' },
    { label: 'Audit log', href: '/audit-log', icon: 'M8 3H5v18h14V3h-3M8 2h8v4H8Zm0 9h8m-8 4h5' },
    { label: 'Laporan', href: '/laporan', icon: 'M4 3v18h17M8 16v-4m5 4V8m5 8V5' },
];
const groups = [{label:'Operasional', paths:['/dashboard','/master/siswa','/transaksi','/laporan']},{label:'Administrasi',paths:['/approval','/audit-log','/backup','/pengaturan']}];
const visibleItems = computed(() => items.filter(item => !['/approval', '/audit-log'].includes(item.href) || (page.props.auth as any)?.admin?.role === 'admin'));
const activeItem = computed(() => items.find(item => page.url.startsWith(item.href)));
const initials = computed(() => (settings.value.schoolName || 'Tabungan Siswa')
    .split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase());
</script>

<template>
    <div class="clay-shell min-h-screen" @keydown.esc="open = false">
        <Head>
            <link v-if="settings.schoolLogo" head-key="app-icon" rel="icon" :href="settings.schoolLogo">
        </Head>
        <Transition name="soft-fade">
            <button v-if="open" class="fixed inset-0 z-30 bg-slate-950/35 lg:hidden" aria-label="Tutup navigasi" @click="open = false"></button>
        </Transition>
        <aside id="app-navigation" :class="['clay-navigation fixed inset-y-0 left-0 z-40 flex w-64 flex-col px-4 py-5 lg:translate-x-0', open ? 'translate-x-0' : '-translate-x-full']">
            <Link href="/dashboard" class="flex items-center gap-3 px-1" @click="open = false">
                <div class="clay-brand shrink-0">
                    <img v-if="settings.schoolLogo && !logoFailed" :src="settings.schoolLogo" alt="Logo aplikasi" class="h-10 w-10 object-contain" @error="logoFailed = true">
                    <span v-else class="text-sm font-bold text-indigo-600">{{ initials }}</span>
                </div>
                <div class="min-w-0">
                    <p class="break-words text-sm font-bold leading-snug text-slate-800">{{ settings.schoolName ?? 'Tabungan Siswa' }}</p>
                    <p class="mt-1 text-[11px] sneat-muted">Tabungan siswa</p>
                </div>
            </Link>
            <div class="mt-8 min-h-0 flex-1 overflow-y-auto">
                <nav class="space-y-6" aria-label="Menu utama"><template v-for="group in groups" :key="group.label"><div v-if="visibleItems.some(item=>group.paths.includes(item.href))"><p class="clay-eyebrow mb-2 px-3">{{group.label}}</p>
                    <Link v-for="item in group.paths.map(path=>visibleItems.find(item=>item.href===path)).filter(Boolean)" :key="item.href" :href="item.href" class="sneat-sidebar-link flex items-center gap-3 px-3 py-2 text-sm font-medium" :class="{ active: page.url.startsWith(item.href) }" :aria-current="page.url.startsWith(item.href) ? 'page' : undefined" @click="open = false">
                        <span class="grid h-6 w-6 shrink-0 place-items-center">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="item.icon" /></svg>
                        </span>
                        {{ item.label }}
                    </Link>
                </div></template></nav>
            </div>
            <div class="clay-profile mt-5 p-4">
                <p class="clay-eyebrow">Pengelola</p>
                <p class="mt-2 truncate text-sm font-semibold text-slate-800">{{ settings.teacherName ?? 'Administrator' }}</p>
            </div>
        </aside>
        <div class="min-w-0 lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 px-4 sm:px-8">
                <button class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-600 lg:hidden" aria-label="Buka navigasi" :aria-expanded="open" aria-controls="app-navigation" @click="open = true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-800">{{ activeItem?.label ?? 'Panel administrasi' }}</p>
                    <p class="mt-0.5 truncate text-xs sneat-muted">{{ settings.schoolName }}</p>
                </div>
                <div class="ml-auto flex shrink-0 items-center gap-3">
                    <div class="hidden text-right md:block">
                        <p class="max-w-56 truncate text-sm font-semibold text-slate-800">{{ settings.teacherName ?? 'Administrator' }}</p>
                        <p class="mt-0.5 text-xs sneat-muted">Pengelola sistem</p>
                    </div>
                    <div class="grid h-10 w-10 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600">{{ (settings.teacherName ?? 'A').charAt(0).toUpperCase() }}</div>
                </div>
            </header>
            <p class="session-bar px-4 py-3 text-xs text-slate-600 sm:px-8">Sesi aktif: {{ (page.props.activeSession as any)?.kelas ?? 'Belum diatur' }} · {{ (page.props.activeSession as any)?.tahun ?? '—' }} · {{ (page.props.activeSession as any)?.semester ?? '—' }} <Link href="/pengaturan" class="ml-2 font-semibold text-indigo-600">Ubah sesi</Link></p>
            <div :key="page.url" class="page-content" :class="{'page-entry': animateEntry}" :aria-busy="activity.pending.value"><slot /></div>
        </div>
    </div>
</template>
