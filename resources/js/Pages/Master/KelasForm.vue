<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Kelas, TahunPelajaran } from '../../types';

const props = defineProps<{ years: TahunPelajaran[]; kelas?: Kelas }>();
const form = useForm({
    nama_kelas: props.kelas?.nama_kelas ?? '',
    tingkat: props.kelas?.tingkat ?? 'VII',
    jurusan: props.kelas?.jurusan ?? '',
    tahun_pelajaran_id: props.kelas?.tahun_pelajaran_id ?? ('' as number | ''),
    wali_kelas: props.kelas?.wali_kelas ?? '',
});
const submit = () => (props.kelas ? form.patch(`/master/kelas/${props.kelas.id}`) : form.post('/master/kelas'));
</script>

<template>
    <MainLayout>
        <Head :title="kelas ? 'Edit kelas' : 'Tambah kelas'" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-3xl">
                <Link href="/master/kelas" class="text-sm font-semibold text-indigo-600">← Kembali ke kelas</Link>
                <section class="sneat-card mt-5 p-5 sm:p-7">
                    <PageHeader
                        eyebrow="Master data"
                        :title="kelas ? 'Edit kelas' : 'Tambah kelas'"
                        description="Lengkapi informasi kelas dan periode akademik."
                    />
                    <p v-if="!years.length" class="sneat-banner is-warning mb-5">
                        Belum ada tahun pelajaran.
                        <Link href="/master/tahun-pelajaran" class="font-semibold underline">Tambahkan periode</Link>
                        terlebih dahulu.
                    </p>
                    <form class="space-y-5" @submit.prevent="submit">
                        <fieldset :disabled="form.processing" class="space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField id="nama-kelas" label="Nama kelas" :error="form.errors.nama_kelas">
                                    <input
                                        id="nama-kelas"
                                        v-model="form.nama_kelas"
                                        class="w-full"
                                        placeholder="VII-A"
                                        maxlength="50"
                                        required
                                        :aria-invalid="!!form.errors.nama_kelas"
                                        aria-describedby="nama-kelas-error"
                                    />
                                </FormField>
                                <FormField id="tingkat" label="Tingkat" :error="form.errors.tingkat">
                                    <input id="tingkat" v-model="form.tingkat" list="tingkat-options" class="w-full" maxlength="10" required />
                                    <datalist id="tingkat-options">
                                        <option v-for="level in ['VII', 'VIII', 'IX', 'X', 'XI', 'XII']" :key="level" :value="level" />
                                    </datalist>
                                </FormField>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <FormField id="jurusan" label="Jurusan" optional :error="form.errors.jurusan">
                                    <input id="jurusan" v-model="form.jurusan" class="w-full" placeholder="IPA / IPS / Umum" maxlength="50" />
                                </FormField>
                                <FormField id="tahun-pelajaran" label="Tahun pelajaran" :error="form.errors.tahun_pelajaran_id">
                                    <select
                                        id="tahun-pelajaran"
                                        v-model="form.tahun_pelajaran_id"
                                        class="w-full"
                                        required
                                        :aria-invalid="!!form.errors.tahun_pelajaran_id"
                                        aria-describedby="tahun-pelajaran-error"
                                    >
                                        <option value="">Pilih periode</option>
                                        <option v-for="year in years" :key="year.id" :value="year.id">
                                            {{ year.tahun }} · {{ year.semester }}{{ year.status === 'aktif' ? ' (Aktif)' : '' }}
                                        </option>
                                    </select>
                                </FormField>
                            </div>
                            <FormField id="wali-kelas" label="Wali kelas" optional :error="form.errors.wali_kelas">
                                <input id="wali-kelas" v-model="form.wali_kelas" class="w-full" placeholder="Nama wali kelas" maxlength="100" />
                            </FormField>
                        </fieldset>
                        <div class="flex justify-end gap-3 border-t pt-5">
                            <Link href="/master/kelas" class="rounded-lg border px-4 py-2.5 text-sm font-semibold">Batal</Link>
                            <button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary rounded-lg px-5 py-2.5 text-sm font-semibold">
                                {{ form.processing ? 'Menyimpan…' : kelas ? 'Simpan perubahan' : 'Simpan kelas' }}
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </MainLayout>
</template>
