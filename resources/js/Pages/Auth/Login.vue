<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
const form = useForm({ username: '', password: '' });
const submit = () => form.post('/login');
</script>
<template>
<main class="flex min-h-screen items-center justify-center bg-slate-100 p-6"><section class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl ring-1 ring-slate-200"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Tabungan Siswa</p><h1 class="mt-3 text-3xl font-bold text-slate-950">Masuk ke panel admin</h1><p class="mt-2 text-slate-500">Kelola data tabungan sekolah dengan aman.</p><form class="mt-8 space-y-5" @submit.prevent="submit"><div><label class="mb-2 block text-sm font-medium text-slate-700">Username</label><input v-model="form.username" class="w-full rounded-xl border-slate-300 px-4 py-3" autocomplete="username" required><p v-if="form.errors.username" class="mt-2 text-sm text-red-600">{{ form.errors.username }}</p></div><div><label class="mb-2 block text-sm font-medium text-slate-700">Password</label><input v-model="form.password" type="password" class="w-full rounded-xl border-slate-300 px-4 py-3" autocomplete="current-password" required></div><button :disabled="form.processing" class="w-full rounded-xl bg-teal-800 px-4 py-3 font-semibold text-white hover:bg-teal-700 disabled:opacity-60">{{ form.processing ? 'Memproses…' : 'Masuk' }}</button></form></section></main>
</template>
