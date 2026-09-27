<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TableCard from '../../Components/TableCard.vue';
import { confirmAction } from '../../confirmation';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { TahunPelajaran } from '../../types';

defineProps<{ items: TahunPelajaran[] }>();
const showForm = ref(false);
const form = useForm({ tahun: '', semester: 'ganjil' });
const submit = () =>
    form.post('/master/tahun-pelajaran', {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });

async function activate(year: TahunPelajaran) {
    const ok = await confirmAction(
        `Periode ${year.tahun} ${year.semester} akan menjadi periode aktif untuk seluruh aplikasi. Kelas aktif ikut berpindah ke kelas pada periode ini.`,
        { title: 'Aktifkan periode?', confirmLabel: 'Ya, aktifkan', tone: 'primary' },
    );
    if (ok) router.patch(`/master/tahun-pelajaran/${year.id}/aktifkan`, {}, { preserveScroll: true });
}
async function remove(year: TahunPelajaran) {
    if (await confirmAction(`Periode ${year.tahun} ${year.semester} yang belum memiliki kelas ini akan dihapus.`)) {
        router.delete(`/master/tahun-pelajaran/${year.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <MainLayout>
        <Head title="Tahun pelajaran" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <FlashBanner />
                <PageHeader eyebrow="Master data" title="Tahun pelajaran" description="Atur periode akademik yang aktif untuk operasional sekolah.">
                    <template #actions>
                        <button
                            type="button"
                            class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold"
                            :aria-expanded="showForm"
                            aria-controls="year-form"
                            @click="showForm = !showForm"
                        >
                            {{ showForm ? 'Tutup form' : '＋ Tambah periode' }}
                        </button>
                    </template>
                </PageHeader>
                <Transition name="soft-panel">
                    <section v-if="showForm" id="year-form" class="sneat-card mb-6 p-5">
                        <h2 class="font-bold text-slate-900">Tambah tahun pelajaran</h2>
                        <form class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-start" @submit.prevent="submit">
                            <FormField id="year-tahun" label="Tahun" hint="Format: 2026/2027" :error="form.errors.tahun">
                                <input
                                    id="year-tahun"
                                    v-model="form.tahun"
                                    placeholder="2026/2027"
                                    class="w-full"
                                    required
                                    pattern="\d{4}/\d{4}"
                                    :aria-invalid="!!form.errors.tahun"
                                    aria-describedby="year-tahun-hint year-tahun-error"
                                />
                            </FormField>
                            <FormField id="year-semester" label="Semester" :error="form.errors.semester">
                                <select id="year-semester" v-model="form.semester" class="w-full">
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </FormField>
                            <button
                                :aria-busy="form.processing"
                                :disabled="form.processing"
                                class="sneat-primary rounded-lg px-5 py-2.5 text-sm font-semibold sm:mt-6"
                            >
                                {{ form.processing ? 'Menyimpan…' : 'Simpan' }}
                            </button>
                        </form>
                    </section>
                </Transition>
                <TableCard title="Daftar periode" :description="`${items.length} periode terdaftar`">
                    <thead>
                        <tr>
                            <th scope="col">Tahun</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Status</th>
                            <th scope="col">Kelas</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id">
                            <td class="font-semibold text-slate-800">{{ item.tahun }}</td>
                            <td class="capitalize text-slate-500">{{ item.semester }}</td>
                            <td>
                                <span class="sneat-status" :class="item.status === 'aktif' ? 'is-aktif' : 'is-lulus'">
                                    {{ item.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-slate-500">{{ item.kelas_count }} kelas</td>
                            <td class="text-right">
                                <div class="sneat-row-actions">
                                    <button
                                        v-if="item.status !== 'aktif'"
                                        type="button"
                                        class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-600"
                                        @click="activate(item)"
                                    >
                                        Aktifkan
                                    </button>
                                    <button
                                        v-if="item.kelas_count === 0"
                                        type="button"
                                        class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
                                        @click="remove(item)"
                                    >
                                        Hapus
                                    </button>
                                    <span v-if="item.status === 'aktif'" class="text-xs sneat-muted">Periode aktif</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="5">
                                <EmptyState title="Belum ada tahun pelajaran" description="Tambahkan periode pertama untuk mulai mengatur kelas." />
                            </td>
                        </tr>
                    </tbody>
                </TableCard>
            </div>
        </main>
    </MainLayout>
</template>
