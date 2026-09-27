<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TableCard from '../../Components/TableCard.vue';
import { confirmAction } from '../../confirmation';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Kelas } from '../../types';

defineProps<{ items: Kelas[] }>();

async function remove(kelas: Kelas) {
    if (await confirmAction(`Kelas ${kelas.nama_kelas} yang kosong ini akan dihapus dari daftar.`)) {
        router.delete(`/master/kelas/${kelas.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <MainLayout>
        <Head title="Kelas" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <FlashBanner />
                <PageHeader
                    eyebrow="Master data"
                    title="Kelas"
                    description="Kelola struktur kelas berdasarkan periode akademik. Kelas aktif dipilih di Pengaturan."
                >
                    <template #actions>
                        <Link href="/master/kelas/create" class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold">＋ Tambah kelas</Link>
                    </template>
                </PageHeader>
                <TableCard title="Daftar kelas" :description="`${items.length} kelas terdaftar`">
                    <thead>
                        <tr>
                            <th scope="col">Kelas</th>
                            <th scope="col">Tingkat</th>
                            <th scope="col">Periode</th>
                            <th scope="col">Wali kelas</th>
                            <th scope="col">Siswa</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td class="font-semibold text-slate-800">
                                {{ item.nama_kelas }}
                                <span v-if="item.jurusan" class="ml-2 text-xs font-normal sneat-muted">{{ item.jurusan }}</span>
                            </td>
                            <td class="text-slate-500">{{ item.tingkat }}</td>
                            <td class="text-slate-500">
                                {{ item.tahun_pelajaran ? `${item.tahun_pelajaran.tahun} · ${item.tahun_pelajaran.semester}` : '—' }}
                            </td>
                            <td class="text-slate-500">{{ item.wali_kelas || '—' }}</td>
                            <td class="text-slate-500">{{ item.siswa_count }}</td>
                            <td class="text-right">
                                <div class="sneat-row-actions">
                                    <Link
                                        :href="`/master/kelas/${item.id}/edit`"
                                        class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-600"
                                        :aria-label="`Edit ${item.nama_kelas}`"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="item.siswa_count === 0"
                                        type="button"
                                        class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
                                        :aria-label="`Hapus ${item.nama_kelas}`"
                                        @click="remove(item)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="6">
                                <EmptyState title="Belum ada kelas." description="Buat tahun pelajaran terlebih dahulu, lalu tambahkan kelas." />
                            </td>
                        </tr>
                    </tbody>
                </TableCard>
            </div>
        </main>
    </MainLayout>
</template>
