<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DownloadLink from '../../Components/DownloadLink.vue';
import EmptyState from '../../Components/EmptyState.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import StatCard from '../../Components/StatCard.vue';
import TableCard from '../../Components/TableCard.vue';
import { formatNumber, formatRupiah, formatSignedRupiah } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Option, Paginated, TransaksiRow } from '../../types';

type Filters = { start_date?: string; end_date?: string; jenis?: string; kelas_id?: string | number; siswa_id?: string | number };
const props = defineProps<{
    items: Paginated<TransaksiRow>;
    filters: Filters;
    summary: { masuk: number; keluar: number; count: number };
    classes: Option[];
    students: Array<{ id: number; nis: string; nama: string }>;
}>();

const form = useForm({
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
    jenis: props.filters.jenis ?? '',
    kelas_id: props.filters.kelas_id ? Number(props.filters.kelas_id) : ('' as number | ''),
    siswa_id: props.filters.siswa_id ? Number(props.filters.siswa_id) : ('' as number | ''),
});
/** Query string of the applied filters (not the unsaved form) so exports match the table. */
const appliedQuery = computed(() =>
    new URLSearchParams(
        Object.entries(props.filters)
            .filter(([, value]) => value)
            .map(([key, value]) => [key, String(value)]),
    ).toString(),
);
const apply = () => form.get('/laporan', { preserveScroll: true });
/** Student list depends on the class, so reload it when the class changes. */
function changeClass() {
    form.siswa_id = '';
    router.reload({ data: { ...form.data(), siswa_id: '' }, only: ['students'] });
}
</script>

<template>
    <MainLayout>
        <Head title="Laporan" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <PageHeader eyebrow="Analitik" title="Laporan transaksi" description="Ringkasan dan histori transaksi tabungan siswa.">
                    <template #actions>
                        <Link href="/laporan/rekap" class="rounded-lg border px-4 py-2.5 text-sm font-semibold">Rekap saldo per kelas</Link>
                        <DownloadLink :href="`/laporan/export-xlsx?${appliedQuery}`" class="rounded-lg border px-4 py-2.5 text-sm font-semibold">
                            ↓ Excel (.xlsx)
                        </DownloadLink>
                        <DownloadLink :href="`/laporan/export-pdf?${appliedQuery}`" class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold">
                            ↓ PDF
                        </DownloadLink>
                    </template>
                </PageHeader>
                <form class="sneat-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="apply">
                    <FormField id="report-start" label="Dari tanggal" :error="form.errors.start_date">
                        <input id="report-start" v-model="form.start_date" type="date" class="w-full" />
                    </FormField>
                    <FormField id="report-end" label="Sampai tanggal" :error="form.errors.end_date">
                        <input id="report-end" v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full" />
                    </FormField>
                    <FormField id="report-type" label="Jenis transaksi">
                        <select id="report-type" v-model="form.jenis" class="w-full">
                            <option value="">Semua jenis</option>
                            <option value="masuk">Setoran</option>
                            <option value="keluar">Penarikan</option>
                        </select>
                    </FormField>
                    <FormField id="report-class" label="Kelas">
                        <select id="report-class" v-model="form.kelas_id" class="w-full" @change="changeClass">
                            <option value="">Semua kelas</option>
                            <option v-for="kelas in classes" :key="kelas.id" :value="kelas.id">{{ kelas.label }}</option>
                        </select>
                    </FormField>
                    <FormField id="report-student" label="Siswa" :hint="form.kelas_id ? undefined : 'Pilih kelas dulu'">
                        <select id="report-student" v-model="form.siswa_id" class="w-full" :disabled="!form.kelas_id" aria-describedby="report-student-hint">
                            <option value="">Semua siswa</option>
                            <option v-for="student in students" :key="student.id" :value="student.id">{{ student.nis }} · {{ student.nama }}</option>
                        </select>
                    </FormField>
                    <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-5">
                        <button class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold" :disabled="form.processing" :aria-busy="form.processing">
                            Terapkan filter
                        </button>
                        <Link href="/laporan" class="rounded-lg border px-4 py-2.5 text-sm">Reset filter</Link>
                    </div>
                </form>
                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Total transaksi" :value="formatNumber(summary.count)" />
                    <StatCard label="Total setoran" :value="formatRupiah(summary.masuk)" tone="emerald" />
                    <StatCard label="Total penarikan" :value="formatRupiah(summary.keluar)" tone="rose" />
                </div>
                <TableCard title="Detail transaksi">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Siswa</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col" class="text-right">Saldo</th>
                            <th scope="col">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id">
                            <td>{{ item.tanggal }}</td>
                            <td class="font-semibold">{{ item.siswa }}</td>
                            <td>
                                <span class="sneat-jenis" :class="item.jenis === 'masuk' ? 'is-masuk' : 'is-keluar'">
                                    {{ item.jenis === 'masuk' ? 'Setoran' : 'Penarikan' }}
                                </span>
                            </td>
                            <td class="text-right" :class="item.jenis === 'masuk' ? 'text-emerald-700' : 'text-rose-700'">
                                {{ formatSignedRupiah(item.jumlah, item.jenis) }}
                            </td>
                            <td class="text-right">{{ formatRupiah(item.saldo) }}</td>
                            <td class="text-slate-500">{{ item.keterangan || '—' }}</td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="6"><EmptyState title="Tidak ada transaksi untuk filter ini." /></td>
                        </tr>
                    </tbody>
                    <template #footer><Pagination :data="items" /></template>
                </TableCard>
            </div>
        </main>
    </MainLayout>
</template>
