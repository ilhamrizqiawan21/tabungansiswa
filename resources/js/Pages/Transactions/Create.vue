<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MainLayout from '../../Layouts/MainLayout.vue';
const props = defineProps<{students: Array<{id:number;nis:string;nama:string;saldo?:string|number|null}>;activeClass:any;approvalThreshold:number}>();
const search = ref('');
const today = new Date();
const date = [today.getFullYear(), String(today.getMonth()+1).padStart(2,'0'), String(today.getDate()).padStart(2,'0')].join('-');
const form = useForm({siswa_id:'',tanggal:date,jenis:'masuk',jumlah:'',keterangan:''});
const filtered = computed(() => props.students.filter(s => s.id === Number(form.siswa_id) || (s.nis+' '+s.nama).toLowerCase().includes(search.value.toLowerCase())));
const selected = computed(() => props.students.find(s => s.id === Number(form.siswa_id)));
const money = (v:number) => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR'}).format(v);
const balance = computed(() => Number(selected.value?.saldo ?? 0));
const needsApproval = computed(() => form.jenis === 'keluar' && Number(form.jumlah) >= props.approvalThreshold);
const insufficient = computed(() => !!selected.value && form.jenis === 'keluar' && Number(form.jumlah) > balance.value);
function submit() { if (!form.processing) form.post('/transaksi'); }
</script>
<template>
<MainLayout><Head title="Catat transaksi" /><main class="mx-auto max-w-2xl p-4 sm:p-8">
<Link href="/transaksi" class="text-sm font-semibold text-indigo-600">← Riwayat transaksi</Link>
<section class="sneat-card mt-5 p-5 sm:p-8">
<h1 class="text-2xl font-bold">Catat transaksi</h1>
<p class="mt-2 text-sm text-slate-500">Kelas: {{ activeClass?.nama_kelas ?? 'Belum diatur' }}</p>
<div v-if="!activeClass || !students.length" class="mt-5 rounded-lg bg-amber-50 p-4 text-sm" role="status">
{{ !activeClass ? 'Atur kelas aktif sebelum mencatat transaksi.' : 'Tambahkan siswa ke kelas aktif terlebih dahulu.' }}
<Link :href="!activeClass ? '/pengaturan' : '/master/siswa'" class="ml-2 font-semibold underline">{{ !activeClass ? 'Atur sesi' : 'Kelola siswa' }}</Link>
</div>
<form v-else @submit.prevent="submit" class="mt-6 space-y-5">
<div v-if="Object.keys(form.errors).length" role="alert" class="rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><p class="font-semibold">Transaksi belum tersimpan.</p><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
<fieldset :disabled="form.processing" class="space-y-5">
<div><label for="student-search" class="block text-sm font-medium">Cari nama atau NIS</label><input id="student-search" v-model="search" type="search" class="mt-2 w-full" placeholder="Ketik nama atau NIS"><p class="mt-1 text-xs text-slate-500">{{ filtered.length }} pilihan siswa</p></div>
<div><label for="student" class="block text-sm font-medium">Siswa</label><select id="student" v-model="form.siswa_id" class="mt-2 w-full" required :aria-invalid="!!form.errors.siswa_id"><option value="">Pilih siswa</option><option v-for="s in filtered" :key="s.id" :value="s.id">{{ s.nis }} · {{ s.nama }}</option></select></div>
<p v-if="selected" class="rounded-lg bg-indigo-50 p-3 text-sm" aria-live="polite">Saldo saat ini: <strong>{{ money(balance) }}</strong></p>
<div class="grid gap-5 sm:grid-cols-2"><div><label for="date" class="block text-sm font-medium">Tanggal</label><input id="date" v-model="form.tanggal" type="date" class="mt-2 w-full" required></div><div><label for="type" class="block text-sm font-medium">Jenis transaksi</label><select id="type" v-model="form.jenis" class="mt-2 w-full"><option value="masuk">Setoran</option><option value="keluar">Penarikan</option></select></div></div>
<div><label for="amount" class="block text-sm font-medium">Jumlah (Rp)</label><input id="amount" v-model="form.jumlah" type="number" min="1" step="0.01" class="mt-2 w-full" required :aria-invalid="!!form.errors.jumlah"><p v-if="Number(form.jumlah)>0" class="mt-2 text-sm">{{ money(Number(form.jumlah)) }}</p></div>
<p v-if="insufficient" role="alert" class="text-sm text-rose-700">Nominal melebihi saldo. Kurangi jumlah penarikan.</p>
<p v-else-if="needsApproval" role="status" class="rounded-lg bg-amber-50 p-3 text-sm">Penarikan mulai {{ money(approvalThreshold) }} membutuhkan persetujuan admin. Saldo baru berkurang setelah disetujui.</p>
<div><label for="note" class="block text-sm font-medium">Keterangan (opsional)</label><textarea id="note" v-model="form.keterangan" maxlength="255" rows="3" class="mt-2 w-full"></textarea></div>
<button :aria-busy="form.processing" :disabled="form.processing || insufficient" class="sneat-primary w-full rounded-lg px-4 py-3 font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : needsApproval ? 'Ajukan penarikan' : 'Simpan transaksi' }}</button>
</fieldset></form></section></main></MainLayout>
</template>
