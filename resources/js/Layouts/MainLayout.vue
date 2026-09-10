<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const open = ref(false);
const page = usePage();
const settings = computed(() => (page.props.appSettings as {
    teacherName?: string; schoolName?: string; schoolLogo?: string | null;
}) ?? {});
const logoFailed = ref(false);
watch(() => settings.value.schoolLogo, () => { logoFailed.value = false; });
const form = useForm({});
const items = [
    { label: 'Dashboard', href: '/dashboard', icon: 'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z' },
    { label: 'Pengaturan', href: '/pengaturan', icon: 'M4 7h16M4 17h16M8 4v6m8 4v6' },
    { label: 'Siswa', href: '/master/siswa', icon: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8m8 .13a4 4 0 0 1 0 7.75' },
    { label: 'Transaksi', href: '/transaksi', icon: 'M7 3v18m-4-4 4 4 4-4M17 21V3m-4 4 4-4 4 4' },
    { label: 'Approval', href: '/approval', icon: 'm7 12 3 3 7-7M21 12a9 9 0 1 1-5-8' },
    { label: 'Audit log', href: '/audit-log', icon: 'M8 3H5v18h14V3h-3M8 2h8v4H8Zm0 9h8m-8 4h5' },
    { label: 'Laporan', href: '/laporan', icon: 'M4 3v18h17M8 16v-4m5 4V8m5 8V5' },
];
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
            <button v-if="open" class="fixed inset-0 z-30 bg-slate-950/35 backdrop-blur-sm lg:hidden" aria-label="Tutup navigasi" @click="open = false"></button>
        </Transition>
        <aside id="app-navigation" :class="['clay-navigation fixed inset-y-0 left-0 z-40 flex w-72 flex-col px-5 py-6 lg:translate-x-0', open ? 'translate-x-0' : '-translate-x-full']">
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
                <p class="clay-eyebrow mb-3 px-3">Ruang kelola</p>
                <nav class="space-y-1" aria-label="Menu utama">
                    <Link v-for="item in items" :key="item.href" :href="item.href" class="sneat-sidebar-link flex items-center gap-3 px-3 py-2.5 text-sm font-medium" :class="{ active: page.url.startsWith(item.href) }" :aria-current="page.url.startsWith(item.href) ? 'page' : undefined" @click="open = false">
                        <span class="grid h-8 w-8 shrink-0 place-items-center">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="item.icon" /></svg>
                        </span>
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
            <div class="clay-profile mt-5 p-4">
                <p class="clay-eyebrow">Pengelola</p>
                <p class="mt-2 truncate text-sm font-semibold text-slate-800">{{ settings.teacherName ?? 'Administrator' }}</p>
                <button class="mt-3 text-xs font-semibold text-indigo-600" :disabled="form.processing" @click="form.post('/logout')">Keluar dari akun →</button>
            </div>
        </aside>
        <div class="min-w-0 lg:pl-72">
            <header class="sticky top-0 z-20 flex h-[72px] items-center justify-between gap-3 px-4 sm:px-8">
                <button class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-600 lg:hidden" aria-label="Buka navigasi" :aria-expanded="open" aria-controls="app-navigation" @click="open = true">☰</button>
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
            <slot />
        </div>
    </div>
</template>
