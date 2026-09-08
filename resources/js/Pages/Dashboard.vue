<script setup lang="ts">
defineProps<{ appName: string }>();
const cards = [{ label: 'Total siswa', value: '—' }, { label: 'Saldo terkumpul', value: 'Rp —' }, { label: 'Transaksi hari ini', value: '—' }];
</script>
<template>
<main class="min-h-screen bg-slate-50 p-6 sm:p-10"><section class="mx-auto max-w-6xl">
<div class="mb-8"><p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Administrasi sekolah</p><h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ appName }}</h1><p class="mt-3 max-w-2xl text-slate-600">Platform pengelolaan tabungan siswa yang rapi, aman, dan mudah dipantau.</p></div>
<div class="grid gap-5 md:grid-cols-3"><article v-for="card in cards" :key="card.label" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><p class="text-sm text-slate-500">{{ card.label }}</p><p class="mt-3 text-2xl font-bold text-slate-950">{{ card.value }}</p><p class="mt-4 text-xs text-slate-400">Menunggu modul database.</p></article></div>
<div class="mt-6 rounded-2xl bg-teal-900 p-7 text-white shadow-lg"><p class="text-sm font-medium text-teal-200">Baseline migrasi siap</p><h2 class="mt-2 text-2xl font-semibold">Selamat datang di versi Laravel</h2><p class="mt-2 max-w-2xl text-teal-100">Modul autentikasi, siswa, kelas, transaksi, laporan, dan approval akan dibangun di atas fondasi ini.</p></div>
</section></main>
</template>
