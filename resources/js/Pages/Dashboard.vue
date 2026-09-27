<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '../Components/EmptyState.vue';
import PageHeader from '../Components/PageHeader.vue';
import StatCard from '../Components/StatCard.vue';
import TableCard from '../Components/TableCard.vue';
import MainLayout from '../Layouts/MainLayout.vue';
import { formatNumber, formatRupiah, formatSignedRupiah } from '../format';
import type { TransaksiRow } from '../types';

const props = defineProps<{
    appName: string;
    stats: { totalSiswa: number; saldo: number; transaksiHariIni: number; totalTransaksi: number; setoranHariIni: number; penarikanHariIni: number };
    activeSession: { activeYear: string; activeSemester: string; activeClass: string };
    monthly: Array<{ label: string; masuk: number; keluar: number }>;
    types: { masuk: { jumlah: number; total: number }; keluar: { jumlah: number; total: number } };
    recentTransactions: TransaksiRow[];
}>();
const page = usePage();
const teacherName = computed(() => page.props.appSettings?.teacherName || 'Administrator');
const maxBar = Math.max(...props.monthly.flatMap(month => [month.masuk, month.keluar]), 1);
/** Zero stays zero so empty months do not look like they had activity; tiny non-zero values stay visible. */
const barHeight = (value: number) => (value <= 0 ? '0%' : `${Math.max((value / maxBar) * 100, 2)}%`);
const typesTotal = computed(() => props.types.masuk.total + props.types.keluar.total || 1);

const icons = {
    students: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
    wallet: 'M3 7a2 2 0 0 1 2-2h14v4M3 7v12a2 2 0 0 0 2 2h16V9H5a2 2 0 0 1-2-2Zm14 7h.01',
    swap: 'M7 3v18m-4-4 4 4 4-4M17 21V3m-4 4 4-4 4 4',
    list: 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
    plus: 'M12 5v14M5 12h14',
    report: 'M4 3v18h17M8 16v-4m5 4V8m5 8V5',
};
const summaryClasses: Record<string, { text: string; bar: string }> = {
    emerald: { text: 'text-emerald-600', bar: 'bg-emerald-400' },
    rose: { text: 'text-rose-600', bar: 'bg-rose-400' },
};
</script>

<template>
    <MainLayout>
        <Head title="Dashboard" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <PageHeader
                    eyebrow="Dashboard"
                    :title="`Selamat datang kembali, ${teacherName}`"
                    description="Pantau aktivitas tabungan siswa di seluruh kelas."
                >
                    <template #actions>
                        <Link href="/transaksi/create" class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold">＋ Transaksi baru</Link>
                    </template>
                </PageHeader>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Siswa aktif"
                        :value="formatNumber(stats.totalSiswa)"
                        note="Semua kelas, status aktif"
                        tone="indigo"
                        :icon="icons.students"
                    />
                    <StatCard label="Saldo terkumpul" :value="formatRupiah(stats.saldo)" note="Saldo bersih semua kelas" tone="emerald" :icon="icons.wallet" />
                    <StatCard
                        label="Transaksi hari ini"
                        :value="formatNumber(stats.transaksiHariIni)"
                        :note="`Setoran ${formatRupiah(stats.setoranHariIni)}`"
                        tone="orange"
                        :icon="icons.swap"
                    />
                    <StatCard
                        label="Total transaksi"
                        :value="formatNumber(stats.totalTransaksi)"
                        note="Semua kelas, sepanjang periode"
                        tone="blue"
                        :icon="icons.list"
                    />
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[1.55fr_1fr]">
                    <section class="sneat-card p-5 sm:p-6" aria-labelledby="trend-title">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 id="trend-title" class="font-bold text-slate-900">Tren transaksi</h2>
                                <p class="mt-1 text-xs text-slate-500">Perbandingan 6 bulan terakhir, semua kelas</p>
                            </div>
                            <div class="flex gap-3 text-xs font-medium" aria-hidden="true">
                                <span class="text-emerald-600">● Setoran</span>
                                <span class="text-rose-500">● Penarikan</span>
                            </div>
                        </div>
                        <template v-if="stats.totalTransaksi">
                            <div class="mt-8 flex h-52 items-end gap-2 sm:gap-5" aria-hidden="true">
                                <div v-for="month in monthly" :key="month.label" class="flex flex-1 flex-col items-center gap-2">
                                    <div class="flex h-44 w-full items-end justify-center gap-1">
                                        <div
                                            class="w-2/5 rounded-t bg-emerald-400"
                                            :style="{ height: barHeight(month.masuk) }"
                                            :title="`${month.label} · Setoran ${formatRupiah(month.masuk)}`"
                                        ></div>
                                        <div
                                            class="w-2/5 rounded-t bg-rose-400"
                                            :style="{ height: barHeight(month.keluar) }"
                                            :title="`${month.label} · Penarikan ${formatRupiah(month.keluar)}`"
                                        ></div>
                                    </div>
                                    <span class="text-xs sneat-muted">{{ month.label }}</span>
                                </div>
                            </div>
                            <table class="sr-only">
                                <caption>Tren setoran dan penarikan per bulan</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Bulan</th>
                                        <th scope="col">Setoran</th>
                                        <th scope="col">Penarikan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="month in monthly" :key="month.label">
                                        <th scope="row">{{ month.label }}</th>
                                        <td>{{ formatRupiah(month.masuk) }}</td>
                                        <td>{{ formatRupiah(month.keluar) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                        <EmptyState
                            v-else
                            class="mt-8 h-52"
                            title="Belum ada data transaksi"
                            description="Grafik akan terisi otomatis setelah transaksi dicatat."
                        />
                    </section>
                    <section class="sneat-card p-5 sm:p-6" aria-labelledby="summary-title">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 id="summary-title" class="font-bold text-slate-900">Ringkasan transaksi</h2>
                                <p class="mt-1 text-xs text-slate-500">Akumulasi seluruh data</p>
                            </div>
                            <span class="rounded-lg bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-600">
                                {{ formatNumber(stats.totalTransaksi) }} total
                            </span>
                        </div>
                        <div class="mt-7 space-y-6">
                            <div
                                v-for="item in [
                                    { label: 'Setoran', data: types.masuk, color: 'emerald' },
                                    { label: 'Penarikan', data: types.keluar, color: 'rose' },
                                ]"
                                :key="item.label"
                            >
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-slate-600">{{ item.label }}</span>
                                    <span class="font-bold" :class="summaryClasses[item.color].text">{{ formatRupiah(item.data.total) }}</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100" role="presentation">
                                    <div
                                        class="h-2 rounded-full"
                                        :class="summaryClasses[item.color].bar"
                                        :style="{ width: `${(item.data.total / typesTotal) * 100}%` }"
                                    ></div>
                                </div>
                                <p class="mt-2 text-xs sneat-muted">{{ formatNumber(item.data.jumlah) }} transaksi</p>
                            </div>
                        </div>
                    </section>
                </div>

                <TableCard class="mt-6" title="Aktivitas terbaru" description="Transaksi terakhir yang tercatat.">
                    <template #actions><Link href="/transaksi" class="text-sm font-semibold text-indigo-600">Lihat semua →</Link></template>
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Siswa</th>
                            <th scope="col">Jenis</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col" class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in recentTransactions" :key="item.id">
                            <td class="text-slate-500">{{ item.tanggal }}</td>
                            <td class="font-medium text-slate-800">{{ item.siswa }}</td>
                            <td>
                                <span class="sneat-jenis" :class="item.jenis === 'masuk' ? 'is-masuk' : 'is-keluar'">
                                    {{ item.jenis === 'masuk' ? 'Setoran' : 'Penarikan' }}
                                </span>
                            </td>
                            <td class="text-right font-semibold" :class="item.jenis === 'masuk' ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatSignedRupiah(item.jumlah, item.jenis) }}
                            </td>
                            <td class="text-right text-slate-600">{{ formatRupiah(item.saldo) }}</td>
                        </tr>
                        <tr v-if="!recentTransactions.length">
                            <td colspan="5"><EmptyState title="Belum ada aktivitas" description="Mulai dengan mencatat transaksi baru." /></td>
                        </tr>
                    </tbody>
                </TableCard>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <Link
                        v-for="shortcut in [
                            { href: '/transaksi/create', label: 'Catat setoran', note: 'Tambahkan saldo siswa', icon: icons.plus },
                            { href: '/master/siswa', label: 'Kelola siswa', note: 'Lihat data siswa', icon: icons.students },
                            { href: '/laporan', label: 'Buka laporan', note: 'Analisis dan ekspor data', icon: icons.report },
                        ]"
                        :key="shortcut.href"
                        :href="shortcut.href"
                        class="sneat-card group flex items-center gap-3 p-4"
                    >
                        <svg
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="shrink-0 text-indigo-600"
                            aria-hidden="true"
                        >
                            <path :d="shortcut.icon" />
                        </svg>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-indigo-600">{{ shortcut.label }}</span>
                            <span class="mt-1 block text-xs sneat-muted">{{ shortcut.note }}</span>
                        </span>
                    </Link>
                </div>
            </div>
        </main>
    </MainLayout>
</template>
