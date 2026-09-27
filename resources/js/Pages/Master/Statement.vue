<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import ReversalDialog from '../../Components/ReversalDialog.vue';
import StatCard from '../../Components/StatCard.vue';
import TableCard from '../../Components/TableCard.vue';
import { formatRupiah, formatSignedRupiah, whatsappNumber } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Paginated, Siswa, TransaksiRow } from '../../types';

const props = defineProps<{
    student: Siswa;
    summary: { masuk: number; keluar: number; saldo: number };
    items: Paginated<TransaksiRow>;
    pendingCount: number;
}>();
const page = usePage();
const isAdmin = computed(() => page.props.auth?.admin?.role === 'admin');
const reversal = ref<InstanceType<typeof ReversalDialog> | null>(null);
const whatsapp = computed(() => {
    const number = whatsappNumber(props.student.kontak);
    if (!number) return null;
    const school = page.props.appSettings?.schoolName ?? 'sekolah';
    const text = `Assalamu'alaikum. Informasi tabungan ${props.student.nama} (NIS ${props.student.nis}) di ${school}: saldo saat ini ${formatRupiah(props.summary.saldo)}. Terima kasih.`;
    return `https://wa.me/${number}?text=${encodeURIComponent(text)}`;
});
</script>

<template>
    <MainLayout>
        <Head :title="'Buku tabungan · ' + student.nama" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <Link href="/master/siswa" class="text-sm font-semibold text-indigo-600">← Daftar siswa</Link>
                <FlashBanner />
                <PageHeader eyebrow="Buku tabungan siswa" :title="student.nama">
                    <template #description>
                        NIS {{ student.nis }} · {{ student.kelas?.nama_kelas ?? 'Tanpa kelas' }} · {{ student.kelas?.tahun_pelajaran?.tahun ?? '—' }}
                        <span v-if="student.status !== 'aktif'" class="sneat-status ml-2" :class="`is-${student.status}`">
                            {{ student.status === 'lulus' ? 'Lulus' : 'Keluar' }}
                        </span>
                        <template v-if="student.kontak">
                            <br />
                            Kontak: {{ student.kontak }}
                        </template>
                    </template>
                    <template #actions>
                        <a v-if="whatsapp" :href="whatsapp" target="_blank" rel="noopener" class="rounded-lg border px-4 py-3 text-sm font-semibold">
                            Kirim info saldo via WhatsApp ↗
                        </a>
                        <a
                            :href="`/master/siswa/${student.id}/buku/cetak`"
                            target="_blank"
                            rel="noopener"
                            class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold"
                        >
                            Cetak buku tabungan ↗
                        </a>
                    </template>
                </PageHeader>
                <div class="grid gap-4 sm:grid-cols-3">
                    <StatCard label="Saldo saat ini" :value="formatRupiah(summary.saldo)" tone="indigo" />
                    <StatCard label="Total setoran" :value="formatRupiah(summary.masuk)" tone="emerald" />
                    <StatCard label="Total penarikan" :value="formatRupiah(summary.keluar)" tone="rose" />
                </div>
                <p v-if="pendingCount" class="sneat-banner is-warning">
                    Ada {{ pendingCount }} pengajuan penarikan yang menunggu persetujuan. Saldo belum dikurangi.
                    <Link v-if="isAdmin" href="/approval" class="font-bold underline">Lihat approval</Link>
                </p>
                <TableCard
                    title="Seluruh riwayat tabungan"
                    description="Transaksi terakhir dicatat tampil paling atas. Buku cetak diurutkan berdasarkan tanggal transaksi."
                >
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col">Keterangan</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id" :class="{ 'sneat-row-muted': item.isReversed }">
                            <td>{{ item.tanggal }}</td>
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
                            <td>{{ item.keterangan || '—' }}</td>
                            <td class="text-right">
                                <div class="sneat-row-actions">
                                    <a :href="`/transaksi/${item.id}/bukti`" target="_blank" rel="noopener" class="font-semibold text-indigo-600">Bukti ↗</a>
                                    <button
                                        v-if="isAdmin && !item.isReversal && !item.isReversed"
                                        type="button"
                                        class="rounded bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
                                        @click="reversal?.open(item)"
                                    >
                                        Koreksi
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="5"><EmptyState title="Belum ada transaksi untuk siswa ini." /></td>
                        </tr>
                    </tbody>
                    <template #footer><Pagination :data="items" /></template>
                </TableCard>
            </div>
            <ReversalDialog ref="reversal" />
        </main>
    </MainLayout>
</template>
