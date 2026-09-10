<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import MainLayout from '../../Layouts/MainLayout.vue';

interface Settings {
    teacherName: string;
    schoolName: string;
    teacherPhone: string;
    schoolLogo: string | null;
    activeYear: string;
    activeSemester: string;
    activeClass: string;
}
const props = defineProps<{ settings: Settings }>();
const form = useForm({
    _method: 'patch',
    teacherName: props.settings.teacherName,
    schoolName: props.settings.schoolName,
    teacherPhone: props.settings.teacherPhone ?? '',
    activeYear: props.settings.activeYear,
    activeSemester: props.settings.activeSemester,
    activeClass: props.settings.activeClass,
    schoolLogoFile: null as File | null,
});
const logoInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const preview = computed(() => previewUrl.value ?? props.settings.schoolLogo);
const initials = computed(() => (form.schoolName.trim() || 'Tabungan Siswa')
    .split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase());
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
                <div class="mb-6">
                    <p class="clay-eyebrow">Preferensi sekolah</p>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan</h1>
                    <p class="mt-2 text-sm text-slate-500">Identitas yang familiar, pengelolaan yang lebih personal.</p>
                </div>
                <Transition name="soft-fade">
                    <p v-if="saved" role="status" class="clay-notice mb-5">Pengaturan tersimpan. Identitas aplikasi sudah diperbarui.</p>
                </Transition>
                <form class="grid items-start gap-6 lg:grid-cols-[1fr_280px]" @submit.prevent="submit" @input="saved = false">
                    <fieldset :disabled="form.processing" class="min-w-0 space-y-6">
                        <section class="sneat-card p-5 sm:p-6">
                            <div class="mb-5">
                                <p class="clay-eyebrow">01 / Identitas</p>
                                <h2 class="mt-1 font-bold text-slate-900">Sekolah &amp; logo aplikasi</h2>
                                <p class="mt-1 text-sm text-slate-500">Ditampilkan pada navigasi dan header aplikasi.</p>
                            </div>
                            <label for="school-name" class="clay-label">Nama sekolah</label>
                            <input id="school-name" v-model="form.schoolName" class="w-full" maxlength="150" required :aria-invalid="!!form.errors.schoolName" aria-describedby="school-name-error">
                            <p id="school-name-error" class="clay-error">{{ form.errors.schoolName }}</p>
                            <div class="clay-upload mt-5">
                                <label for="school-logo" class="clay-label">Logo aplikasi</label>
                                <p id="logo-help" class="mb-3 text-xs text-slate-500">JPG, PNG, atau WebP · Maksimal 2 MB. Gunakan gambar persegi agar pas.</p>
                                <input id="school-logo" ref="logoInput" type="file" class="w-full min-w-0 text-sm" accept="image/jpeg,image/png,image/webp" aria-describedby="logo-help logo-error" :aria-invalid="!!form.errors.schoolLogoFile" @change="selectLogo">
                                <div v-if="form.schoolLogoFile" class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                                    <span class="break-all text-slate-600">{{ form.schoolLogoFile.name }}</span>
                                    <button type="button" class="font-semibold text-indigo-600" @click="clearSelection">Batalkan pilihan</button>
                                </div>
                                <p id="logo-error" class="clay-error" role="alert">{{ form.errors.schoolLogoFile }}</p>
                            </div>
                        </section>
                        <section class="sneat-card p-5 sm:p-6">
                            <p class="clay-eyebrow">02 / Pengelola</p>
                            <h2 class="mt-1 mb-5 font-bold text-slate-900">Profil pengelola</h2>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="teacher-name" class="clay-label">Nama guru / pengelola</label>
                                    <input id="teacher-name" v-model="form.teacherName" class="w-full" maxlength="100" required :aria-invalid="!!form.errors.teacherName" aria-describedby="teacher-name-error">
                                    <p id="teacher-name-error" class="clay-error">{{ form.errors.teacherName }}</p>
                                </div>
                                <div>
                                    <label for="teacher-phone" class="clay-label">Nomor HP <span class="font-normal text-slate-500">(opsional)</span></label>
                                    <input id="teacher-phone" v-model="form.teacherPhone" class="w-full" type="tel" maxlength="30" placeholder="08xxxxxxxxxx" :aria-invalid="!!form.errors.teacherPhone" aria-describedby="teacher-phone-error">
                                    <p id="teacher-phone-error" class="clay-error">{{ form.errors.teacherPhone }}</p>
                                </div>
                            </div>
                        </section>
                        <section class="sneat-card p-5 sm:p-6">
                            <p class="clay-eyebrow">03 / Periode belajar</p>
                            <h2 class="mt-1 font-bold text-slate-900">Sesi aktif</h2>
                            <p class="mt-1 mb-5 text-sm text-slate-500">Data sesi sebelumnya tetap tersimpan saat tahun atau kelas diganti.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="active-year" class="clay-label">Tahun pelajaran</label>
                                    <input id="active-year" v-model="form.activeYear" class="w-full" placeholder="2026/2027" required :aria-invalid="!!form.errors.activeYear" aria-describedby="year-error">
                                    <p id="year-error" class="clay-error">{{ form.errors.activeYear }}</p>
                                </div>
                                <div>
                                    <label for="semester" class="clay-label">Semester</label>
                                    <select id="semester" v-model="form.activeSemester" class="w-full" :aria-invalid="!!form.errors.activeSemester" aria-describedby="semester-error">
                                        <option value="ganjil">Ganjil</option><option value="genap">Genap</option>
                                    </select>
                                    <p id="semester-error" class="clay-error">{{ form.errors.activeSemester }}</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="active-class" class="clay-label">Nama kelas</label>
                                    <input id="active-class" v-model="form.activeClass" class="w-full" maxlength="50" required :aria-invalid="!!form.errors.activeClass" aria-describedby="class-error">
                                    <p id="class-error" class="clay-error">{{ form.errors.activeClass }}</p>
                                </div>
                            </div>
                        </section>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">Perubahan diterapkan setelah disimpan.</p>
                            <button class="sneat-primary px-5 py-3 text-sm font-semibold" :disabled="form.processing">
                                {{ form.processing ? 'Menyimpan…' : 'Simpan pengaturan' }}
                            </button>
                        </div>
                        <progress v-if="form.progress" class="w-full" :value="form.progress.percentage" max="100" aria-label="Progres upload">{{ form.progress.percentage }}%</progress>
                    </fieldset>
                    <aside class="sneat-card p-6 lg:sticky lg:top-24">
                        <p class="clay-eyebrow">Preview identitas</p>
                        <div class="clay-brand-preview mt-5">
                            <img v-if="preview" :src="preview" alt="Preview logo aplikasi" class="h-20 w-20 object-contain">
                            <span v-else class="text-2xl font-bold text-indigo-600">{{ initials }}</span>
                        </div>
                        <h2 class="mt-4 break-words text-lg font-bold text-slate-900">{{ form.schoolName || 'Nama sekolah' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">Tabungan siswa</p>
                        <div class="mt-5 border-t border-slate-200 pt-4 text-sm">
                            <p class="font-medium text-slate-700">{{ form.teacherName || 'Nama pengelola' }}</p>
                            <p class="mt-1 break-words text-slate-500">{{ form.activeClass }} · {{ form.activeYear }}</p>
                        </div>
                    </aside>
                </form>
            </div>
        </main>
    </MainLayout>
</template>
