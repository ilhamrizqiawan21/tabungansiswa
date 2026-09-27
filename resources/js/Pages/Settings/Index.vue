<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import PageHeader from '../../Components/PageHeader.vue';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Kelas, TahunPelajaran } from '../../types';

interface Settings {
    teacherName: string;
    schoolName: string;
    teacherPhone: string;
    schoolLogo: string | null;
    activeYearId: number | null;
    activeClassId: number | null;
}
const props = defineProps<{ settings: Settings; years: TahunPelajaran[]; classes: Kelas[] }>();
const form = useForm({
    _method: 'patch',
    teacherName: props.settings.teacherName,
    schoolName: props.settings.schoolName,
    teacherPhone: props.settings.teacherPhone ?? '',
    activeYearId: props.settings.activeYearId ?? ('' as number | ''),
    activeClassId: props.settings.activeClassId ?? ('' as number | ''),
    schoolLogoFile: null as File | null,
});
/** Session choices come from master data (Kelas & Tahun pelajaran), the single source of truth. */
const classesInYear = computed(() => props.classes.filter(kelas => kelas.tahun_pelajaran_id === form.activeYearId));
watch(
    () => form.activeYearId,
    () => {
        if (!classesInYear.value.some(kelas => kelas.id === form.activeClassId)) form.activeClassId = classesInYear.value[0]?.id ?? '';
    },
);
const selectedYear = computed(() => props.years.find(year => year.id === form.activeYearId));
const selectedClass = computed(() => props.classes.find(kelas => kelas.id === form.activeClassId));
const logoInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const preview = computed(() => previewUrl.value ?? props.settings.schoolLogo);
const initials = computed(() =>
    (form.schoolName.trim() || 'Tabungan Siswa')
        .split(/\s+/)
        .slice(0, 2)
        .map(word => word[0])
        .join('')
        .toUpperCase(),
);
const saved = ref(false);

function clearSelection() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    form.schoolLogoFile = null;
    if (logoInput.value) logoInput.value.value = '';
    form.clearErrors('schoolLogoFile');
}
function selectLogo(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    clearSelection();
    saved.value = false;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        form.setError('schoolLogoFile', 'Pilih gambar JPG, PNG, atau WebP.');
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        form.setError('schoolLogoFile', 'Ukuran logo maksimal 2 MB.');
        return;
    }
    form.schoolLogoFile = file;
    previewUrl.value = URL.createObjectURL(file);
}
function submit() {
    saved.value = false;
    form.post('/pengaturan', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearSelection();
            form.defaults();
            saved.value = true;
        },
    });
}
onBeforeUnmount(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});
</script>

<template>
    <MainLayout>
        <Head title="Pengaturan" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-5xl">
                <PageHeader eyebrow="Administrasi" title="Pengaturan" description="Identitas sekolah, profil pengelola, dan sesi aktif." />
                <Transition name="soft-fade">
                    <p v-if="saved" role="status" class="sneat-notice mb-5">Pengaturan tersimpan. Identitas aplikasi sudah diperbarui.</p>
                </Transition>
                <form class="grid items-start gap-6 lg:grid-cols-[1fr_280px]" @submit.prevent="submit" @input="saved = false">
                    <fieldset :disabled="form.processing" class="min-w-0 space-y-6">
                        <section class="sneat-card p-5 sm:p-6">
                            <div class="mb-5">
                                <p class="sneat-eyebrow">01 / Identitas</p>
                                <h2 class="mt-1 font-bold text-slate-900">Sekolah &amp; logo aplikasi</h2>
                                <p class="mt-1 text-sm text-slate-500">Ditampilkan pada navigasi dan header aplikasi.</p>
                            </div>
                            <label for="school-name" class="sneat-label">Nama sekolah</label>
                            <input
                                id="school-name"
                                v-model="form.schoolName"
                                class="w-full"
                                maxlength="150"
                                required
                                :aria-invalid="!!form.errors.schoolName"
                                aria-describedby="school-name-error"
                            />
                            <p id="school-name-error" class="sneat-error">{{ form.errors.schoolName }}</p>
                            <div class="sneat-upload mt-5">
                                <label for="school-logo" class="sneat-label">Logo aplikasi</label>
                                <p id="logo-help" class="mb-3 text-xs text-slate-500">JPG, PNG, atau WebP · Maksimal 2 MB. Gunakan gambar persegi agar pas.</p>
                                <input
                                    id="school-logo"
                                    ref="logoInput"
                                    type="file"
                                    class="w-full min-w-0 text-sm"
                                    accept="image/jpeg,image/png,image/webp"
                                    aria-describedby="logo-help logo-error"
                                    :aria-invalid="!!form.errors.schoolLogoFile"
                                    @change="selectLogo"
                                />
                                <div v-if="form.schoolLogoFile" class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                                    <span class="break-all text-slate-600">{{ form.schoolLogoFile.name }}</span>
                                    <button type="button" class="font-semibold text-indigo-600" @click="clearSelection">Batalkan pilihan</button>
                                </div>
                                <p id="logo-error" class="sneat-error" role="alert">{{ form.errors.schoolLogoFile }}</p>
                            </div>
                        </section>
                        <section class="sneat-card p-5 sm:p-6">
                            <p class="sneat-eyebrow">02 / Pengelola</p>
                            <h2 class="mt-1 mb-5 font-bold text-slate-900">Profil pengelola</h2>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="teacher-name" class="sneat-label">Nama guru / pengelola</label>
                                    <input
                                        id="teacher-name"
                                        v-model="form.teacherName"
                                        class="w-full"
                                        maxlength="100"
                                        required
                                        :aria-invalid="!!form.errors.teacherName"
                                        aria-describedby="teacher-name-error"
                                    />
                                    <p id="teacher-name-error" class="sneat-error">{{ form.errors.teacherName }}</p>
                                </div>
                                <div>
                                    <label for="teacher-phone" class="sneat-label">
                                        Nomor HP
                                        <span class="font-normal text-slate-500">(opsional)</span>
                                    </label>
                                    <input
                                        id="teacher-phone"
                                        v-model="form.teacherPhone"
                                        class="w-full"
                                        type="tel"
                                        maxlength="30"
                                        placeholder="08xxxxxxxxxx"
                                        :aria-invalid="!!form.errors.teacherPhone"
                                        aria-describedby="teacher-phone-error"
                                    />
                                    <p id="teacher-phone-error" class="sneat-error">{{ form.errors.teacherPhone }}</p>
                                </div>
                            </div>
                        </section>
                        <section class="sneat-card p-5 sm:p-6">
                            <p class="sneat-eyebrow">03 / Periode belajar</p>
                            <h2 class="mt-1 font-bold text-slate-900">Sesi aktif</h2>
                            <p class="mt-1 mb-5 text-sm text-slate-500">Data sesi sebelumnya tetap tersimpan saat tahun atau kelas diganti.</p>
                            <p v-if="!years.length" class="sneat-banner is-warning">
                                Belum ada tahun pelajaran.
                                <Link href="/master/tahun-pelajaran" class="font-semibold underline">Tambahkan periode</Link>
                                , lalu
                                <Link href="/master/kelas/create" class="font-semibold underline">buat kelas</Link>
                                .
                            </p>
                            <div v-else class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="active-year" class="sneat-label">Tahun pelajaran</label>
                                    <select
                                        id="active-year"
                                        v-model="form.activeYearId"
                                        class="w-full"
                                        required
                                        :aria-invalid="!!form.errors.activeYearId"
                                        aria-describedby="year-error"
                                    >
                                        <option value="">Pilih periode</option>
                                        <option v-for="year in years" :key="year.id" :value="year.id">{{ year.tahun }} · {{ year.semester }}</option>
                                    </select>
                                    <p id="year-error" class="sneat-error">{{ form.errors.activeYearId }}</p>
                                </div>
                                <div>
                                    <label for="active-class" class="sneat-label">Kelas</label>
                                    <select
                                        id="active-class"
                                        v-model="form.activeClassId"
                                        class="w-full"
                                        required
                                        :disabled="!classesInYear.length"
                                        :aria-invalid="!!form.errors.activeClassId"
                                        aria-describedby="class-error class-help"
                                    >
                                        <option value="">Pilih kelas</option>
                                        <option v-for="kelas in classesInYear" :key="kelas.id" :value="kelas.id">{{ kelas.nama_kelas }}</option>
                                    </select>
                                    <p id="class-help" class="mt-1 text-xs text-slate-500">
                                        Kelas diambil dari
                                        <Link href="/master/kelas" class="font-semibold text-indigo-600">master kelas</Link>
                                        <template v-if="form.activeYearId && !classesInYear.length">; periode ini belum memiliki kelas</template>
                                        .
                                    </p>
                                    <p id="class-error" class="sneat-error">{{ form.errors.activeClassId }}</p>
                                </div>
                            </div>
                        </section>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">Perubahan diterapkan setelah disimpan.</p>
                            <button class="sneat-primary px-5 py-3 text-sm font-semibold" :aria-busy="form.processing" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
                            </button>
                        </div>
                        <progress v-if="form.progress" class="w-full" :value="form.progress.percentage" max="100" aria-label="Progres upload">
                            {{ form.progress.percentage }}%
                        </progress>
                    </fieldset>
                    <aside class="sneat-card p-6 lg:sticky lg:top-24">
                        <p class="sneat-eyebrow">Preview identitas</p>
                        <div class="sneat-brand-preview mt-5">
                            <img v-if="preview" :src="preview" alt="Preview logo aplikasi" class="h-20 w-20 object-contain" />
                            <span v-else class="text-2xl font-bold text-indigo-600">{{ initials }}</span>
                        </div>
                        <h2 class="mt-4 break-words text-lg font-bold text-slate-900">{{ form.schoolName || 'Nama sekolah' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">Tabungan siswa</p>
                        <div class="mt-5 border-t border-slate-200 pt-4 text-sm">
                            <p class="font-medium text-slate-700">{{ form.teacherName || 'Nama pengelola' }}</p>
                            <p class="mt-1 break-words text-slate-500">
                                {{ selectedClass?.nama_kelas ?? '—' }} · {{ selectedYear ? `${selectedYear.tahun} ${selectedYear.semester}` : '—' }}
                            </p>
                        </div>
                    </aside>
                </form>
            </div>
        </main>
    </MainLayout>
</template>
