<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import DownloadLink from '../../Components/DownloadLink.vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TableCard from '../../Components/TableCard.vue';
import { confirmAction } from '../../confirmation';
import { formatRupiah, whatsappNumber } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Kelas, Option, Siswa, SiswaStatus } from '../../types';

const props = defineProps<{ items: Siswa[]; activeClass: Kelas | null; classes: Option[] }>();
const page = usePage();
const isAdmin = computed(() => page.props.auth?.admin?.role === 'admin');

const statusLabels: Record<SiswaStatus, string> = { aktif: 'Aktif', lulus: 'Lulus', keluar: 'Keluar' };
const search = ref('');
const statusFilter = ref<'' | SiswaStatus>('aktif');
const filtered = computed(() =>
    props.items.filter(
        student =>
            (!statusFilter.value || student.status === statusFilter.value) &&
            `${student.nis} ${student.nama}`.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

/* Add / edit student dialog */
const studentDialog = ref<HTMLDialogElement | null>(null);
const firstInput = ref<HTMLInputElement | null>(null);
const editing = ref<Siswa | null>(null);
const studentForm = useForm({ nis: '', nama: '', kontak: '' });
async function openStudentDialog(student: Siswa | null = null) {
    editing.value = student;
    studentForm.clearErrors();
    studentForm.nis = student?.nis ?? '';
    studentForm.nama = student?.nama ?? '';
    studentForm.kontak = student?.kontak ?? '';
    studentDialog.value?.showModal();
    await nextTick();
    firstInput.value?.focus();
}
function saveStudent() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            studentDialog.value?.close();
            studentForm.reset();
        },
    };
    if (editing.value) studentForm.patch(`/master/siswa/${editing.value.id}`, options);
    else studentForm.post('/master/siswa', options);
}

/* Status dialog (lulus / keluar / aktif) */
const statusDialog = ref<HTMLDialogElement | null>(null);
const statusTarget = ref<Siswa | null>(null);
const statusForm = useForm({ status: 'lulus' as SiswaStatus, tarik_saldo: true });
function openStatusDialog(student: Siswa) {
    statusTarget.value = student;
    statusForm.clearErrors();
    statusForm.status = student.status === 'aktif' ? 'lulus' : 'aktif';
    statusForm.tarik_saldo = Number(student.saldo_total ?? 0) > 0;
    statusDialog.value?.showModal();
}
function saveStatus() {
    if (!statusTarget.value) return;
    statusForm.patch(`/master/siswa/${statusTarget.value.id}/status`, { preserveScroll: true, onSuccess: () => statusDialog.value?.close() });
}

/* Bulk move (kenaikan / pindah kelas) */
const selected = ref<number[]>([]);
const allSelected = computed({
    get: () => filtered.value.length > 0 && filtered.value.every(student => selected.value.includes(student.id)),
    set: (value: boolean) => {
        selected.value = value ? filtered.value.map(student => student.id) : [];
    },
});
const moveForm = useForm({ siswa_ids: [] as number[], kelas_id: '' as number | '' });
async function moveStudents() {
    const target = props.classes.find(kelas => kelas.id === moveForm.kelas_id);
    if (!target || !selected.value.length) return;
    const ok = await confirmAction(`${selected.value.length} siswa akan dipindahkan ke ${target.label}. Saldo tabungan ikut berpindah bersama siswa.`, {
        title: 'Pindahkan siswa?',
        confirmLabel: 'Ya, pindahkan',
        tone: 'primary',
    });
    if (!ok) return;
    moveForm.siswa_ids = [...selected.value];
    moveForm.post('/master/siswa/pindah-kelas', {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            moveForm.reset();
        },
    });
}

async function remove(student: Siswa) {
    if (await confirmAction(`${student.nama} belum memiliki transaksi dan akan dihapus dari daftar.`)) {
        useForm({}).delete(`/master/siswa/${student.id}`, { preserveScroll: true });
    }
}

/* Import */
const upload = useForm({ file: null as File | null });
const fileInput = ref<HTMLInputElement | null>(null);
function importStudents() {
    upload.post('/master/siswa/import', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            upload.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}
const waLink = (student: Siswa) => {
    const number = whatsappNumber(student.kontak);
    return number ? `https://wa.me/${number}` : null;
};
</script>

<template>
    <MainLayout>
        <Head title="Siswa" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <FlashBanner />
                <PageHeader eyebrow="Operasional" title="Siswa">
                    <template #description>
                        Kelas aktif: {{ activeClass?.nama_kelas ?? 'Belum diatur' }}
                        <template v-if="activeClass?.tahun_pelajaran">
                            · {{ activeClass.tahun_pelajaran.tahun }} {{ activeClass.tahun_pelajaran.semester }}
                        </template>
                    </template>
                    <template #actions>
                        <button
                            v-if="activeClass"
                            type="button"
                            class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold"
                            @click="openStudentDialog()"
                        >
                            ＋ Tambah siswa
                        </button>
                    </template>
                </PageHeader>
                <p v-if="!activeClass" class="sneat-banner is-warning">Atur kelas aktif di Pengaturan sebelum menambah, mengimpor, atau mengekspor siswa.</p>

                <TableCard title="Daftar siswa" :description="`${items.length} siswa pada kelas aktif`">
                    <template #before>
                        <div class="grid gap-4 border-b p-5 sm:grid-cols-[1fr_200px]">
                            <FormField id="search-students" label="Cari siswa">
                                <input id="search-students" v-model="search" type="search" placeholder="Nama atau NIS" class="w-full" />
                            </FormField>
                            <FormField id="status-filter" label="Status">
                                <select id="status-filter" v-model="statusFilter" class="w-full">
                                    <option value="">Semua status</option>
                                    <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </FormField>
                            <p class="text-xs text-slate-500 sm:col-span-2" aria-live="polite">{{ filtered.length }} dari {{ items.length }} siswa</p>
                        </div>
                        <form
                            v-if="isAdmin && selected.length"
                            class="flex flex-wrap items-end gap-3 border-b bg-indigo-50/60 p-5"
                            @submit.prevent="moveStudents"
                        >
                            <p class="w-full text-sm font-semibold text-slate-700">{{ selected.length }} siswa dipilih — kenaikan / pindah kelas</p>
                            <FormField
                                id="move-target"
                                label="Kelas tujuan"
                                :error="moveForm.errors.kelas_id || moveForm.errors.siswa_ids"
                                class="min-w-60 flex-1"
                            >
                                <select id="move-target" v-model="moveForm.kelas_id" class="w-full" required>
                                    <option value="">Pilih kelas tujuan</option>
                                    <option v-for="kelas in classes" :key="kelas.id" :value="kelas.id">{{ kelas.label }}</option>
                                </select>
                            </FormField>
                            <button
                                class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold"
                                :disabled="moveForm.processing || !moveForm.kelas_id"
                                :aria-busy="moveForm.processing"
                            >
                                Pindahkan
                            </button>
                            <button type="button" class="rounded-lg border px-4 py-2.5 text-sm" @click="selected = []">Batal pilih</button>
                        </form>
                    </template>
                    <thead>
                        <tr>
                            <th v-if="isAdmin" scope="col" class="w-10">
                                <input v-model="allSelected" type="checkbox" aria-label="Pilih semua siswa yang tampil" />
                            </th>
                            <th scope="col">NIS</th>
                            <th scope="col">Nama</th>
                            <th scope="col">Kontak</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Saldo tabungan</th>
                            <th scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="student in filtered" :key="student.id">
                            <td v-if="isAdmin"><input v-model="selected" type="checkbox" :value="student.id" :aria-label="`Pilih ${student.nama}`" /></td>
                            <td>{{ student.nis }}</td>
                            <td class="font-semibold">
                                <Link :href="`/master/siswa/${student.id}/buku`" class="text-indigo-600 underline underline-offset-4">{{ student.nama }}</Link>
                            </td>
                            <td class="text-slate-500">
                                <a
                                    v-if="waLink(student)"
                                    :href="waLink(student)!"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-emerald-700 underline underline-offset-4"
                                    :aria-label="`WhatsApp ${student.nama}`"
                                >
                                    {{ student.kontak }}
                                </a>
                                <span v-else>{{ student.kontak || '—' }}</span>
                            </td>
                            <td>
                                <span class="sneat-status" :class="`is-${student.status}`">{{ statusLabels[student.status] }}</span>
                            </td>
                            <td class="text-right font-semibold text-emerald-600">{{ formatRupiah(student.saldo_total) }}</td>
                            <td class="text-right">
                                <div class="sneat-row-actions">
                                    <Link
                                        :href="`/master/siswa/${student.id}/buku`"
                                        class="rounded bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700"
                                    >
                                        Buku
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-600"
                                        :aria-label="`Edit ${student.nama}`"
                                        @click="openStudentDialog(student)"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        v-if="isAdmin"
                                        type="button"
                                        class="rounded bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700"
                                        :aria-label="`Ubah status ${student.nama}`"
                                        @click="openStatusDialog(student)"
                                    >
                                        Status
                                    </button>
                                    <button
                                        v-if="!student.transaksi_count"
                                        type="button"
                                        class="rounded bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600"
                                        :aria-label="`Hapus ${student.nama}`"
                                        @click="remove(student)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td :colspan="isAdmin ? 7 : 6">
                                <EmptyState title="Tidak ada siswa yang cocok." description="Ubah pencarian atau filter status." />
                            </td>
                        </tr>
                    </tbody>
                </TableCard>

                <details class="sneat-card sneat-disclosure">
                    <summary class="cursor-pointer p-5 font-bold">Impor &amp; ekspor siswa (XLSX)</summary>
                    <div class="border-t p-5">
                        <p class="text-sm text-slate-500">
                            Gunakan template dengan kolom NIS, Nama, dan Kontak (opsional). Isi mulai baris 2; pertahankan format teks agar nol awal tidak
                            hilang.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <DownloadLink href="/master/siswa/template" class="rounded-lg border px-4 py-3 text-sm font-semibold">
                                Unduh template XLSX
                            </DownloadLink>
                            <DownloadLink v-if="activeClass" href="/master/siswa/export" class="rounded-lg border px-4 py-3 text-sm font-semibold">
                                Ekspor siswa kelas aktif (.xlsx)
                            </DownloadLink>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            Ekspor memuat seluruh siswa kelas aktif (NIS, Nama, Kontak), tanpa mengikuti pencarian. Impor menambahkan siswa baru; NIS yang sudah
                            ada dilewati.
                        </p>
                        <form v-if="activeClass" class="mt-5 space-y-3" @submit.prevent="importStudents">
                            <FormField id="student-xlsx" label="File siswa (.xlsx), maksimal 2 MB / 1.000 siswa" :error="upload.errors.file">
                                <input
                                    id="student-xlsx"
                                    ref="fileInput"
                                    type="file"
                                    accept=".xlsx"
                                    required
                                    :disabled="upload.processing"
                                    class="w-full min-w-0"
                                    :aria-invalid="!!upload.errors.file"
                                    aria-describedby="student-xlsx-error"
                                    @change="
                                        upload.file = ($event.target as HTMLInputElement).files?.[0] ?? null;
                                        upload.clearErrors();
                                    "
                                />
                            </FormField>
                            <progress v-if="upload.progress" :value="upload.progress.percentage" max="100" class="w-full" aria-label="Progres impor"></progress>
                            <button
                                :aria-busy="upload.processing"
                                :disabled="upload.processing || !upload.file"
                                class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold"
                            >
                                {{ upload.processing ? 'Mengimpor…' : 'Impor ke ' + activeClass.nama_kelas }}
                            </button>
                        </form>
                    </div>
                </details>
            </div>

            <dialog ref="studentDialog" class="sneat-dialog" aria-labelledby="student-dialog-title" @cancel="studentForm.processing && $event.preventDefault()">
                <form class="space-y-4" @submit.prevent="saveStudent">
                    <div>
                        <h2 id="student-dialog-title" class="text-lg font-bold">{{ editing ? 'Edit siswa' : 'Tambah siswa' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ editing ? editing.nama : 'Data otomatis masuk ke kelas aktif.' }}</p>
                    </div>
                    <fieldset :disabled="studentForm.processing" class="space-y-4">
                        <FormField id="student-nis" label="NIS" :error="studentForm.errors.nis">
                            <input
                                id="student-nis"
                                ref="firstInput"
                                v-model="studentForm.nis"
                                class="w-full"
                                required
                                maxlength="20"
                                :aria-invalid="!!studentForm.errors.nis"
                                aria-describedby="student-nis-error"
                            />
                        </FormField>
                        <FormField id="student-name" label="Nama lengkap" :error="studentForm.errors.nama">
                            <input
                                id="student-name"
                                v-model="studentForm.nama"
                                class="w-full"
                                required
                                maxlength="100"
                                :aria-invalid="!!studentForm.errors.nama"
                                aria-describedby="student-name-error"
                            />
                        </FormField>
                        <FormField
                            id="student-contact"
                            label="Kontak orang tua"
                            optional
                            hint="Nomor WhatsApp, mis. 0812xxxxxxx"
                            :error="studentForm.errors.kontak"
                        >
                            <input
                                id="student-contact"
                                v-model="studentForm.kontak"
                                class="w-full"
                                type="tel"
                                maxlength="25"
                                aria-describedby="student-contact-hint student-contact-error"
                            />
                        </FormField>
                    </fieldset>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-lg border px-4 py-2 text-sm" :disabled="studentForm.processing" @click="studentDialog?.close()">
                            Batal
                        </button>
                        <button
                            class="sneat-primary rounded-lg px-4 py-2 text-sm font-semibold"
                            :disabled="studentForm.processing"
                            :aria-busy="studentForm.processing"
                        >
                            {{ studentForm.processing ? 'Menyimpan…' : editing ? 'Simpan perubahan' : 'Simpan siswa' }}
                        </button>
                    </div>
                </form>
            </dialog>

            <dialog ref="statusDialog" class="sneat-dialog" aria-labelledby="status-dialog-title" @cancel="statusForm.processing && $event.preventDefault()">
                <form class="space-y-4" @submit.prevent="saveStatus">
                    <div>
                        <h2 id="status-dialog-title" class="text-lg font-bold">Ubah status siswa</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ statusTarget?.nama }} · saldo {{ formatRupiah(statusTarget?.saldo_total) }}</p>
                    </div>
                    <FormField id="status-value" label="Status baru" :error="statusForm.errors.status">
                        <select id="status-value" v-model="statusForm.status" class="w-full">
                            <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </FormField>
                    <label v-if="statusForm.status !== 'aktif' && Number(statusTarget?.saldo_total ?? 0) > 0" class="flex items-start gap-3 text-sm">
                        <input v-model="statusForm.tarik_saldo" type="checkbox" class="mt-1" />
                        <span>
                            Catat penarikan seluruh saldo ({{ formatRupiah(statusTarget?.saldo_total) }}) sekarang. Siswa tidak dihapus; riwayat tetap
                            tersimpan.
                        </span>
                    </label>
                    <p class="text-xs text-slate-500">Siswa berstatus lulus/keluar tidak bisa menerima transaksi baru.</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-lg border px-4 py-2 text-sm" :disabled="statusForm.processing" @click="statusDialog?.close()">
                            Batal
                        </button>
                        <button
                            class="sneat-primary rounded-lg px-4 py-2 text-sm font-semibold"
                            :disabled="statusForm.processing"
                            :aria-busy="statusForm.processing"
                        >
                            {{ statusForm.processing ? 'Menyimpan…' : 'Simpan status' }}
                        </button>
                    </div>
                </form>
            </dialog>
        </main>
    </MainLayout>
</template>
