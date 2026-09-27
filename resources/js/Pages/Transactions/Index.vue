<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import ReversalDialog from '../../Components/ReversalDialog.vue';
import StatCard from '../../Components/StatCard.vue';
import TableCard from '../../Components/TableCard.vue';
import { formatNumber, formatRupiah, formatSignedRupiah } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Paginated, TransaksiRow } from '../../types';

const props = defineProps<{
    items: Paginated<TransaksiRow>;
    filters: { search?: string; siswa_id?: number; jenis?: string; start_date?: string; end_date?: string };
    summary: { masuk: number; keluar: number };
}>();
const page = usePage();
const isAdmin = computed(() => page.props.auth?.admin?.role === 'admin');
const reversal = ref<InstanceType<typeof ReversalDialog> | null>(null);
/** One search box (name or NIS). `siswa_id` is only kept when arriving from a student link, and is dropped once the search is edited. */
const form = useForm({
    search: props.filters.search ?? '',
    siswa_id: props.filters.siswa_id ?? ('' as number | ''),
    jenis: props.filters.jenis ?? '',
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
});
const studentName = computed(() => (props.filters.siswa_id ? props.items.data[0]?.siswa : null));
const apply = () => form.get('/transaksi', { preserveState: true, preserveScroll: true });
</script>

<template>
    <MainLayout>
        <Head title="Transaksi" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <FlashBanner />
                <PageHeader eyebrow="Operasional" title="Transaksi" description="Cari setoran dan penarikan pada kelas aktif, lalu cetak buktinya.">
                    <template #actions>
                        <Link href="/transaksi/create" class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold">＋ Catat transaksi</Link>
                    </template>
                </PageHeader>
                <form class="sneat-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="apply">
                    <FormField id="transaction-search" label="Cari nama atau NIS" :error="form.errors.search">
                        <input
                            id="transaction-search"
                            v-model="form.search"
                            type="search"
                            maxlength="100"
                            placeholder="Contoh: Budi atau 00123"
                            class="w-full"
                            @input="form.siswa_id = ''"
                        />
                    </FormField>
                    <FormField id="transaction-type" label="Jenis transaksi" :error="form.errors.jenis">
                        <select id="transaction-type" v-model="form.jenis" class="w-full">
                            <option value="">Semua jenis</option>
                            <option value="masuk">Setoran</option>
                            <option value="keluar">Penarikan</option>
                        </select>
                    </FormField>
                    <FormField id="transaction-start" label="Dari tanggal" :error="form.errors.start_date">
                        <input id="transaction-start" v-model="form.start_date" type="date" class="w-full" />
                    </FormField>
                    <FormField id="transaction-end" label="Sampai tanggal" :error="form.errors.end_date">
                        <input id="transaction-end" v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full" />
                    </FormField>
                    <p v-if="form.siswa_id" class="text-sm text-slate-600 sm:col-span-2 lg:col-span-4">
                        Menampilkan transaksi
                        <strong>{{ studentName ?? 'satu siswa' }}</strong>
                        . Ubah pencarian untuk melihat siswa lain.
                    </p>
                    <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-4">
                        <button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold">
                            Terapkan filter
                        </button>
                        <Link href="/transaksi" class="rounded-lg border px-4 py-3 text-sm">Reset</Link>
                    </div>
                </form>
                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Setoran sesuai filter" :value="formatRupiah(summary.masuk)" tone="emerald" />
                    <StatCard label="Penarikan sesuai filter" :value="formatRupiah(summary.keluar)" tone="rose" />
                    <StatCard label="Transaksi ditemukan" :value="formatNumber(items.total)" />
                </div>
                <TableCard title="Riwayat transaksi">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Siswa</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col">Keterangan</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" :class="{ 'sneat-row-muted': item.isReversed }">
                            <td>{{ item.tanggal }}</td>
                            <td class="font-semibold">
                                <Link :href="`/master/siswa/${item.siswa_id}/buku`" class="text-indigo-600">{{ item.siswa }}</Link>
                            </td>
                            <td>
                                <span class="sneat-jenis" :class="item.jenis === 'masuk' ? 'is-masuk' : 'is-keluar'">
                                    {{ item.jenis === 'masuk' ? 'Setoran' : 'Penarikan' }}
                                </span>
                                <span v-if="item.isReversal" class="sneat-status is-keluar ml-1">Koreksi</span>
                                <span v-if="item.isReversed" class="sneat-status is-lulus ml-1">Dibatalkan</span>
                            </td>
                            <td class="text-right font-semibold" :class="item.jenis === 'masuk' ? 'text-emerald-700' : 'text-rose-700'">
                                {{ formatSignedRupiah(item.jumlah, item.jenis) }}
                            </td>
                            <td class="text-slate-500">{{ item.keterangan || '—' }}</td>
                            <td class="text-right">
                                <div class="sneat-row-actions">
                                    <a :href="`/transaksi/${item.id}/bukti`" target="_blank" rel="noopener" class="font-semibold text-indigo-600">Bukti ↗</a>
                                    <button
                                        v-if="isAdmin && !item.isReversal && !item.isReversed"
                                        type="button"
                                        class="rounded bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
                                        :aria-label="`Koreksi transaksi ${item.siswa} ${item.tanggal}`"
                                        @click="reversal?.open(item)"
                                    >
                                        Koreksi
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="6">
                                <EmptyState title="Tidak ada transaksi yang sesuai." description="Coba ubah filter atau catat transaksi baru." />
                            </td>
                        </tr>
                    </tbody>
                    <template #footer><Pagination :data="items" /></template>
                </TableCard>
            </div>
            <ReversalDialog ref="reversal" />
        </main>
    </MainLayout>
</template>
