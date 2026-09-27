<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { formatRupiah } from '../format';
import type { TransaksiRow } from '../types';
import FormField from './FormField.vue';

const dialog = ref<HTMLDialogElement | null>(null);
const reasonInput = ref<HTMLTextAreaElement | null>(null);
const target = ref<TransaksiRow | null>(null);
const form = useForm({ alasan: '' });

async function open(transaction: TransaksiRow) {
    target.value = transaction;
    form.reset();
    form.clearErrors();
    dialog.value?.showModal();
    await nextTick();
    reasonInput.value?.focus();
}
function submit() {
    if (!target.value || form.processing) return;
    form.post(`/transaksi/${target.value.id}/koreksi`, { preserveScroll: true, onSuccess: () => dialog.value?.close() });
}

defineExpose({ open });
</script>

<template>
    <dialog ref="dialog" class="sneat-dialog" aria-labelledby="reversal-title" @cancel="form.processing && $event.preventDefault()">
        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <h2 id="reversal-title" class="text-lg font-bold">Koreksi transaksi</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ target?.jenis === 'masuk' ? 'Setoran' : 'Penarikan' }} {{ formatRupiah(target?.jumlah) }} · {{ target?.tanggal }}
                    <template v-if="target?.siswa">· {{ target.siswa }}</template>
                </p>
            </div>
            <p class="sneat-banner is-warning text-sm">
                Transaksi lama tidak dihapus. Aplikasi mencatat transaksi pembalik ({{ target?.jenis === 'masuk' ? 'penarikan' : 'setoran' }} dengan nominal
                sama) bertanggal hari ini, lalu mencatatnya di audit log. Jika nominal seharusnya berbeda, catat transaksi yang benar setelah koreksi.
            </p>
            <FormField id="reversal-reason" label="Alasan koreksi" :error="form.errors.alasan">
                <textarea
                    id="reversal-reason"
                    ref="reasonInput"
                    v-model="form.alasan"
                    rows="3"
                    minlength="5"
                    maxlength="200"
                    required
                    class="w-full"
                    :disabled="form.processing"
                    :aria-invalid="!!form.errors.alasan"
                    aria-describedby="reversal-reason-error"
                ></textarea>
            </FormField>
            <div class="flex justify-end gap-3">
                <button type="button" class="rounded-lg border px-4 py-2 text-sm" :disabled="form.processing" @click="dialog?.close()">Batal</button>
                <button class="confirmation-delete" :disabled="form.processing" :aria-busy="form.processing">
                    {{ form.processing ? 'Memproses…' : 'Batalkan transaksi' }}
                </button>
            </div>
        </form>
    </dialog>
</template>
