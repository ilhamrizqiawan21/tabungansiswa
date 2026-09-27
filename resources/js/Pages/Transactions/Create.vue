<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { formatRupiah } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Jenis, Kelas } from '../../types';

interface StudentOption {
    id: number;
    nis: string;
    nama: string;
    saldo?: string | number | null;
}
const props = defineProps<{ students: StudentOption[]; activeClass: Kelas | null; approvalThreshold: number; maxJumlah: number }>();

/** `tanggal`/`jenis` come back in the URL after "Simpan & catat lagi". */
const query = new URLSearchParams(usePage().url.split('?')[1] ?? '');
const today = new Date();
const todayValue = [today.getFullYear(), String(today.getMonth() + 1).padStart(2, '0'), String(today.getDate()).padStart(2, '0')].join('-');
const initialJenis: Jenis = query.get('jenis') === 'keluar' ? 'keluar' : 'masuk';
const form = useForm({
    siswa_id: '' as number | '',
    tanggal: /^\d{4}-\d{2}-\d{2}$/.test(query.get('tanggal') ?? '') ? query.get('tanggal')! : todayValue,
    jenis: initialJenis,
    jumlah: '' as number | '',
    keterangan: '',
    lanjut: false,
});
const quickAmounts = [5000, 10000, 20000, 50000];

/* Student combobox: type to filter, arrow keys to move, Enter to choose. */
const studentQuery = ref('');
const listOpen = ref(false);
const highlighted = ref(0);
const comboInput = ref<HTMLInputElement | null>(null);
const matches = computed(() => {
    const needle = studentQuery.value.toLowerCase().trim();
    return props.students.filter(student => `${student.nis} ${student.nama}`.toLowerCase().includes(needle)).slice(0, 50);
});
const selected = computed(() => props.students.find(student => student.id === form.siswa_id));
function choose(student: StudentOption) {
    form.siswa_id = student.id;
    studentQuery.value = `${student.nis} · ${student.nama}`;
    listOpen.value = false;
    form.clearErrors('siswa_id');
}
function onComboInput() {
    form.siswa_id = '';
    listOpen.value = true;
    highlighted.value = 0;
}
function onComboKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        listOpen.value = true;
        highlighted.value = Math.min(highlighted.value + 1, matches.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter' && listOpen.value && matches.value[highlighted.value]) {
        event.preventDefault();
        choose(matches.value[highlighted.value]);
    } else if (event.key === 'Escape') {
        listOpen.value = false;
    }
}

const balance = computed(() => Number(selected.value?.saldo ?? 0));
const amount = computed(() => Number(form.jumlah || 0));
const needsApproval = computed(() => form.jenis === 'keluar' && amount.value >= props.approvalThreshold);
const insufficient = computed(() => !!selected.value && form.jenis === 'keluar' && amount.value > balance.value);
const balanceAfter = computed(() => (form.jenis === 'masuk' ? balance.value + amount.value : balance.value - amount.value));

function submit(continueAfter: boolean) {
    if (form.processing) return;
    form.lanjut = continueAfter;
    form.post('/transaksi');
}
onMounted(() => {
    if (query.has('tanggal')) comboInput.value?.focus();
});
</script>

<template>
    <MainLayout>
        <Head title="Catat transaksi" />
        <main class="mx-auto max-w-2xl p-4 sm:p-8">
            <Link href="/transaksi" class="text-sm font-semibold text-indigo-600">← Riwayat transaksi</Link>
            <section class="sneat-card mt-5 p-5 sm:p-8">
                <FlashBanner />
                <PageHeader eyebrow="Operasional" title="Catat transaksi" :description="`Kelas: ${activeClass?.nama_kelas ?? 'Belum diatur'}`" />
                <div v-if="!activeClass || !students.length" class="sneat-banner is-warning" role="status">
                    {{ !activeClass ? 'Atur kelas aktif sebelum mencatat transaksi.' : 'Tambahkan siswa aktif ke kelas ini terlebih dahulu.' }}
                    <Link :href="!activeClass ? '/pengaturan' : '/master/siswa'" class="ml-2 font-semibold underline">
                        {{ !activeClass ? 'Atur sesi' : 'Kelola siswa' }}
                    </Link>
                </div>
                <form v-else class="space-y-5" @submit.prevent="submit(false)">
                    <fieldset :disabled="form.processing" class="space-y-5">
                        <FormField id="student-combo" label="Siswa" hint="Ketik nama atau NIS, lalu pilih dari daftar." :error="form.errors.siswa_id">
                            <div class="relative">
                                <input
                                    id="student-combo"
                                    ref="comboInput"
                                    v-model="studentQuery"
                                    type="text"
                                    role="combobox"
                                    autocomplete="off"
                                    class="w-full"
                                    placeholder="Contoh: Budi atau 00123"
                                    required
                                    aria-autocomplete="list"
                                    aria-controls="student-options"
                                    :aria-expanded="listOpen"
                                    :aria-activedescendant="listOpen && matches[highlighted] ? `student-option-${matches[highlighted].id}` : undefined"
                                    :aria-invalid="!!form.errors.siswa_id"
                                    aria-describedby="student-combo-hint student-combo-error"
                                    @input="onComboInput"
                                    @focus="listOpen = true"
                                    @blur="listOpen = false"
                                    @keydown="onComboKeydown"
                                />
                                <ul v-show="listOpen" id="student-options" role="listbox" class="sneat-listbox" aria-label="Pilihan siswa">
                                    <li
                                        v-for="(student, index) in matches"
                                        :id="`student-option-${student.id}`"
                                        :key="student.id"
                                        role="option"
                                        :aria-selected="index === highlighted"
                                        :class="{ 'is-highlighted': index === highlighted }"
                                        @mousedown.prevent="choose(student)"
                                        @mouseenter="highlighted = index"
                                    >
                                        <span class="font-medium">{{ student.nama }}</span>
                                        <span class="sneat-muted">· {{ student.nis }}</span>
                                        <span class="float-right text-xs sneat-muted">{{ formatRupiah(student.saldo) }}</span>
                                    </li>
                                    <li v-if="!matches.length" class="sneat-muted" role="option" aria-disabled="true">Tidak ada siswa yang cocok.</li>
                                </ul>
                            </div>
                        </FormField>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <FormField id="date" label="Tanggal" :error="form.errors.tanggal">
                                <input id="date" v-model="form.tanggal" type="date" class="w-full" required />
                            </FormField>
                            <FormField id="type" label="Jenis transaksi" :error="form.errors.jenis">
                                <select id="type" v-model="form.jenis" class="w-full">
                                    <option value="masuk">Setoran</option>
                                    <option value="keluar">Penarikan</option>
                                </select>
                            </FormField>
                        </div>
                        <FormField id="amount" label="Jumlah (Rp)" :error="form.errors.jumlah">
                            <input
                                id="amount"
                                v-model.number="form.jumlah"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :max="maxJumlah"
                                step="1"
                                class="w-full"
                                required
                                :aria-invalid="!!form.errors.jumlah"
                                aria-describedby="amount-error amount-preview"
                            />
                            <div class="mt-2 flex flex-wrap gap-2" role="group" aria-label="Nominal cepat">
                                <button
                                    v-for="value in quickAmounts"
                                    :key="value"
                                    type="button"
                                    class="sneat-chip"
                                    :aria-pressed="form.jumlah === value"
                                    @click="form.jumlah = value"
                                >
                                    {{ formatRupiah(value) }}
                                </button>
                            </div>
                        </FormField>
                        <div v-if="selected" id="amount-preview" class="grid gap-3 rounded-lg bg-indigo-50 p-3 text-sm sm:grid-cols-2" aria-live="polite">
                            <p>
                                Saldo saat ini
                                <br />
                                <strong>{{ formatRupiah(balance) }}</strong>
                            </p>
                            <p v-if="amount > 0 && !needsApproval">
                                Saldo setelah transaksi
                                <br />
                                <strong :class="balanceAfter < 0 ? 'text-rose-700' : 'text-indigo-700'">{{ formatRupiah(balanceAfter) }}</strong>
                            </p>
                        </div>
                        <p v-if="insufficient" role="alert" class="text-sm text-rose-700">Nominal melebihi saldo. Kurangi jumlah penarikan.</p>
                        <p v-else-if="needsApproval" role="status" class="sneat-banner is-warning text-sm">
                            Penarikan mulai {{ formatRupiah(approvalThreshold) }} membutuhkan persetujuan admin. Saldo baru berkurang setelah disetujui.
                        </p>
                        <FormField id="note" label="Keterangan" optional :error="form.errors.keterangan">
                            <textarea id="note" v-model="form.keterangan" maxlength="255" rows="3" class="w-full"></textarea>
                        </FormField>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                class="rounded-lg border px-4 py-3 text-sm font-semibold"
                                :disabled="form.processing || insufficient || !form.siswa_id"
                                @click="submit(true)"
                            >
                                Simpan &amp; catat lagi
                            </button>
                            <button
                                :aria-busy="form.processing"
                                :disabled="form.processing || insufficient || !form.siswa_id"
                                class="sneat-primary rounded-lg px-4 py-3 text-sm font-semibold"
                            >
                                {{ form.processing ? 'Menyimpan…' : needsApproval ? 'Ajukan penarikan' : 'Simpan transaksi' }}
                            </button>
                        </div>
                    </fieldset>
                </form>
            </section>
        </main>
    </MainLayout>
</template>
