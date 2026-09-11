<script setup lang="ts">
import DownloadLink from "../../Components/DownloadLink.vue";

import {Head,useForm,usePage} from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
defineProps<{items:{name:string;size:number;created_at:string}[]}>();
const page=usePage();
const form=useForm({});
const size=(bytes:number)=>bytes>=1048576?(bytes/1048576).toFixed(1)+' MB':(bytes/1024).toFixed(1)+' KB';
const date=(value:string)=>new Date(value).toLocaleString('id-ID',{dateStyle:'long',timeStyle:'short'});
</script>
<template><MainLayout><Head title="Backup data"/><main class="p-4 sm:p-6 lg:p-8"><div class="mx-auto max-w-5xl space-y-6">
    <div><p class="clay-eyebrow">Penyimpanan</p><h1 class="mt-2 text-2xl font-bold">Backup data</h1><p class="mt-2 text-sm text-slate-500">Simpan salinan seluruh data tabungan, identitas sekolah, dan lampiran.</p></div>
    <p v-if="page.props.flash?.success" role="status" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700">{{page.props.flash.success}}</p><p v-if="page.props.flash?.error" role="alert" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{page.props.flash.error}}</p>
    <section class="sneat-card p-6"><h2 class="font-bold">Buat salinan sekarang</h2><p class="mt-2 text-sm leading-relaxed text-slate-500">Arsip ZIP berisi database, logo/lampiran, dan petunjuk pemulihan. Setelah dibuat, unduh salinannya ke flashdisk atau media lain. Data yang sedang digunakan tetap tersimpan.</p><form class="mt-5" @submit.prevent="form.post('/backup',{preserveScroll:true})"><button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary rounded-lg px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">{{form.processing?'Sedang membuat backup…':'Buat backup baru'}}</button></form></section>
    <section class="sneat-card overflow-hidden"><div class="border-b p-5"><h2 class="font-bold">Arsip tersimpan</h2><p class="mt-1 text-xs text-slate-500">{{items.length}} backup tersedia di laptop ini.</p></div><div class="overflow-x-auto"><table class="sneat-table w-full text-left text-sm"><thead class="text-xs uppercase"><tr><th class="p-4">Waktu backup</th><th class="p-4">Ukuran</th><th class="p-4">Unduh</th></tr></thead><tbody class="divide-y"><tr v-for="item in items" :key="item.name"><td class="p-4"><p class="font-semibold">{{date(item.created_at)}}</p><p class="mt-1 break-all text-xs text-slate-500">{{item.name}}</p></td><td class="whitespace-nowrap p-4">{{size(item.size)}}</td><td class="p-4"><DownloadLink :href="`/backup/${item.name}`" class="whitespace-nowrap font-semibold text-indigo-600">Unduh ZIP ↓</DownloadLink></td></tr><tr v-if="!items.length"><td colspan="3" class="p-10 text-center text-slate-500">Belum ada backup. Buat salinan pertama untuk menyimpan data Anda.</td></tr></tbody></table></div></section>
</div></main></MainLayout></template>
