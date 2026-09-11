<script setup lang="ts">
import Pagination from '../../Components/Pagination.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
const props = defineProps<{ students: {id:number;nis:string;nama:string}[]; items:any; filters:any; summary:{masuk:number;keluar:number} }>();
const page = usePage();
const form = useForm({search:props.filters.search ?? '', siswa_id:props.filters.siswa_id ?? '', jenis:props.filters.jenis ?? '', start_date:props.filters.start_date ?? '', end_date:props.filters.end_date ?? ''});
const apply = () => form.get('/transaksi', {preserveState:true, preserveScroll:true});
const rp = (value:number) => Number(value ?? 0).toLocaleString('id-ID');
</script>
<template>
    <MainLayout><Head title="Transaksi"/><main class="p-4 sm:p-6 lg:p-8"><div class="mx-auto max-w-7xl space-y-6">
        <div v-if="page.props.flash?.success" role="status" class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700">{{page.props.flash.success}}</div>
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="clay-eyebrow">Operasional</p><h1 class="mt-2 text-2xl font-bold">Transaksi</h1><p class="mt-1 text-sm text-slate-500">Cari setoran dan penarikan pada kelas aktif, lalu cetak buktinya.</p></div><Link href="/transaksi/create" class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold text-white">＋ Catat transaksi</Link></div>
        <form @submit.prevent="apply" class="sneat-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
            <div><label for="transaction-search" class="clay-label">Cari nama atau NIS</label><input id="transaction-search" v-model="form.search" type="search" maxlength="100" placeholder="Contoh: Budi atau 00123" class="w-full"></div>
            <div><label for="transaction-student" class="clay-label">Siswa</label><select id="transaction-student" v-model="form.siswa_id" class="w-full"><option value="">Semua siswa</option><option v-for="student in students" :key="student.id" :value="student.id">{{student.nis}} · {{student.nama}}</option></select></div>
            <div><label for="transaction-type" class="clay-label">Jenis transaksi</label><select id="transaction-type" v-model="form.jenis" class="w-full"><option value="">Semua jenis</option><option value="masuk">Setoran</option><option value="keluar">Penarikan</option></select></div>
            <div><label for="transaction-start" class="clay-label">Dari tanggal</label><input id="transaction-start" v-model="form.start_date" type="date" class="w-full"></div>
            <div><label for="transaction-end" class="clay-label">Sampai tanggal</label><input id="transaction-end" v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full"></div>
            <div class="flex items-end gap-3"><button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold text-white">Terapkan filter</button><Link href="/transaksi" class="rounded-lg border px-4 py-3 text-sm">Reset</Link></div>
            <p v-for="(error,key) in form.errors" :key="key" role="alert" class="text-sm text-rose-700">{{error}}</p>
        </form>
        <div class="grid gap-4 sm:grid-cols-3"><div class="sneat-card p-5"><p class="text-sm text-slate-500">Setoran sesuai filter</p><p class="mt-2 text-xl font-bold text-emerald-700">Rp {{rp(summary.masuk)}}</p></div><div class="sneat-card p-5"><p class="text-sm text-slate-500">Penarikan sesuai filter</p><p class="mt-2 text-xl font-bold text-rose-700">Rp {{rp(summary.keluar)}}</p></div><div class="sneat-card p-5"><p class="text-sm text-slate-500">Transaksi ditemukan</p><p class="mt-2 text-xl font-bold">{{items.total}}</p></div></div>
        <section class="sneat-card overflow-hidden"><div class="border-b p-5"><h2 class="font-bold">Riwayat transaksi</h2></div><div class="overflow-x-auto"><table class="sneat-table w-full text-left text-sm"><thead class="text-xs uppercase"><tr><th class="p-4">Tanggal</th><th class="p-4">Siswa</th><th class="p-4">Jenis</th><th class="p-4 text-right">Jumlah</th><th class="p-4">Keterangan</th><th class="p-4">Bukti</th></tr></thead><tbody class="divide-y"><tr v-for="item in items.data" :key="item.id"><td class="whitespace-nowrap p-4">{{item.tanggal}}</td><td class="p-4 font-semibold"><Link :href="`/master/siswa/${item.siswa_id}/buku`" class="text-indigo-600">{{item.siswa}}</Link></td><td class="p-4"><span :class="item.jenis==='masuk'?'text-emerald-700':'text-rose-700'">{{item.jenis==='masuk'?'Setoran':'Penarikan'}}</span></td><td class="whitespace-nowrap p-4 text-right font-semibold">Rp {{rp(item.jumlah)}}</td><td class="p-4 text-slate-500">{{item.keterangan||'—'}}</td><td class="p-4"><a :href="`/transaksi/${item.id}/bukti`" target="_blank" rel="noopener" class="whitespace-nowrap font-semibold text-indigo-600">Cetak bukti ↗</a></td></tr><tr v-if="!items.data.length"><td colspan="6" class="p-10 text-center text-slate-500">Tidak ada transaksi yang sesuai. Coba ubah filter atau catat transaksi baru.</td></tr></tbody></table></div><Pagination :data="items"/></section>
    </div></main></MainLayout>
</template>
