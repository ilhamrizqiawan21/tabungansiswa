<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DownloadLink from '../../Components/DownloadLink.vue';
import EmptyState from '../../Components/EmptyState.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import TableCard from '../../Components/TableCard.vue';
import { formatRupiah } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Option, SiswaStatus } from '../../types';

interface RecapRow {
    id: number;
    nis: string;
    nama: string;
    status: SiswaStatus;
    masuk: number;
    keluar: number;
    saldo: number;
}
const props = defineProps<{ kelas: Option | null; classes: Option[]; rows: RecapRow[]; totals: { masuk: number; keluar: number; saldo: number } }>();
const kelasId = ref<number | ''>(props.kelas?.id ?? '');
const choose = () => router.get('/laporan/rekap', kelasId.value ? { kelas_id: kelasId.value } : {});
</script>

<template>
    <MainLayout>
        <Head title="Rekap saldo per kelas" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <Link href="/laporan" class="text-sm font-semibold text-indigo-600">← Laporan transaksi</Link>
                <PageHeader
                    eyebrow="Analitik"
                    title="Rekap saldo per kelas"
                    description="Saldo akhir setiap siswa dalam satu kelas. Pengajuan yang belum disetujui tidak dihitung."
                >
                    <template v-if="kelas" #actions>
                        <DownloadLink :href="`/laporan/rekap/export-xlsx?kelas_id=${kelas.id}`" class="rounded-lg border px-4 py-2.5 text-sm font-semibold">
                            ↓ Excel (.xlsx)
                        </DownloadLink>
                        <a
                            :href="`/laporan/rekap/cetak?kelas_id=${kelas.id}`"
                            target="_blank"
                            rel="noopener"
                            class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold"
                        >
                            Cetak ↗
                        </a>
                    </template>
                </PageHeader>
                <form class="sneat-card flex flex-wrap items-end gap-3 p-5" @submit.prevent="choose">
                    <FormField id="recap-class" label="Kelas" class="min-w-64 flex-1">
                        <select id="recap-class" v-model="kelasId" class="w-full" required>
                            <option value="">Pilih kelas</option>
                            <option v-for="option in classes" :key="option.id" :value="option.id">{{ option.label }}</option>
                        </select>
                    </FormField>
                    <button class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold">Tampilkan</button>
                </form>
                <template v-if="kelas">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <StatCard label="Total setoran" :value="formatRupiah(totals.masuk)" tone="emerald" />
                        <StatCard label="Total penarikan" :value="formatRupiah(totals.keluar)" tone="rose" />
                        <StatCard label="Saldo kelas" :value="formatRupiah(totals.saldo)" tone="indigo" />
                    </div>
                    <TableCard :title="kelas.label" :description="`${rows.length} siswa`">
                        <thead>
                            <tr>
                                <th scope="col">NIS</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-right">Setoran</th>
                                <th scope="col" class="text-right">Penarikan</th>
                                <th scope="col" class="text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in rows" :key="row.id">
                                <td>{{ row.nis }}</td>
                                <td class="font-semibold">
                                    <Link :href="`/master/siswa/${row.id}/buku`" class="text-indigo-600">{{ row.nama }}</Link>
                                </td>
                                <td>
                                    <span class="sneat-status" :class="`is-${row.status}`">{{ row.status }}</span>
                                </td>
                                <td class="text-right text-emerald-700">{{ formatRupiah(row.masuk) }}</td>
                                <td class="text-right text-rose-700">{{ formatRupiah(row.keluar) }}</td>
                                <td class="text-right font-semibold">{{ formatRupiah(row.saldo) }}</td>
                            </tr>
                            <tr v-if="!rows.length">
                                <td colspan="6"><EmptyState title="Belum ada siswa di kelas ini." /></td>
                            </tr>
                        </tbody>
                    </TableCard>
                </template>
                <EmptyState v-else title="Pilih kelas untuk melihat rekap saldo." />
            </div>
        </main>
    </MainLayout>
</template>
