<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import TableCard from '../../Components/TableCard.vue';
import { formatNumber, formatRupiah } from '../../format';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Paginated } from '../../types';

type Status = 'pending' | 'approved' | 'rejected';
interface ApprovalRow {
    id: number;
    tanggal: string | null;
    siswa: string;
    saldoSiswa: number | null;
    jumlah: number;
    keterangan: string | null;
    requestedBy: string;
    requestDate: string | null;
    status: Status;
    approvedBy: string | null;
    approvalDate: string | null;
    rejectionReason: string | null;
}
defineProps<{ items: Paginated<ApprovalRow>; status: Status }>();

const tabs: Array<{ value: Status; label: string }> = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
];
const dialog = ref<HTMLDialogElement | null>(null);
const selected = ref<ApprovalRow | null>(null);
const form = useForm({ status: 'approved' as 'approved' | 'rejected', reason: '' });
/** Balance / incomplete-data errors are keyed outside the form fields. */
const serverError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>;
    return errors.jumlah ?? errors.approval ?? null;
});
function decide(item: ApprovalRow, decision: 'approved' | 'rejected') {
    selected.value = item;
    form.reset();
    form.clearErrors();
    form.status = decision;
    dialog.value?.showModal();
}
function submit() {
    if (!form.processing && selected.value) form.patch(`/approval/${selected.value.id}`, { preserveScroll: true, onSuccess: () => dialog.value?.close() });
}
</script>

<template>
    <MainLayout>
        <Head title="Approval" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl">
                <FlashBanner />
                <PageHeader
                    eyebrow="Administrasi"
                    title="Persetujuan penarikan"
                    description="Tinjau penarikan besar sebelum diproses sebagai transaksi final."
                />
                <nav class="sneat-tabs mb-4" aria-label="Status pengajuan">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.value"
                        :href="tab.value === 'pending' ? '/approval' : `/approval?status=${tab.value}`"
                        class="sneat-tab"
                        :class="{ 'is-active': status === tab.value }"
                        :aria-current="status === tab.value ? 'page' : undefined"
                        preserve-scroll
                    >
                        {{ tab.label }}
                    </Link>
                </nav>
                <TableCard
                    :title="tabs.find(tab => tab.value === status)?.label ?? ''"
                    :description="
                        status === 'pending' ? `${formatNumber(items.total)} pengajuan menunggu persetujuan` : `${formatNumber(items.total)} keputusan tercatat`
                    "
                >
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Siswa</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th v-if="status === 'pending'" scope="col" class="text-right">Saldo siswa</th>
                            <th scope="col">Peminta</th>
                            <th v-if="status !== 'pending'" scope="col">Diputuskan</th>
                            <th v-if="status === 'rejected'" scope="col">Alasan</th>
                            <th v-if="status === 'pending'" scope="col" class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id">
                            <td class="text-slate-500">{{ item.tanggal }}</td>
                            <td class="font-semibold">
                                {{ item.siswa }}
                                <p v-if="item.keterangan" class="text-xs font-normal sneat-muted">{{ item.keterangan }}</p>
                            </td>
                            <td class="text-right font-semibold text-rose-700">{{ formatRupiah(item.jumlah) }}</td>
                            <td
                                v-if="status === 'pending'"
                                class="text-right"
                                :class="(item.saldoSiswa ?? 0) < item.jumlah ? 'text-rose-700 font-semibold' : ''"
                            >
                                {{ formatRupiah(item.saldoSiswa) }}
                            </td>
                            <td class="text-slate-500">
                                {{ item.requestedBy }}
                                <p class="text-xs sneat-muted">{{ item.requestDate }}</p>
                            </td>
                            <td v-if="status !== 'pending'" class="text-slate-500">
                                {{ item.approvedBy ?? '—' }}
                                <p class="text-xs sneat-muted">{{ item.approvalDate }}</p>
                            </td>
                            <td v-if="status === 'rejected'" class="text-slate-600">{{ item.rejectionReason || '—' }}</td>
                            <td v-if="status === 'pending'" class="text-right">
                                <div class="sneat-row-actions">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white"
                                        @click="decide(item, 'approved')"
                                    >
                                        Setujui
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white"
                                        @click="decide(item, 'rejected')"
                                    >
                                        Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="7">
                                <EmptyState :title="status === 'pending' ? 'Tidak ada pengajuan yang menunggu.' : 'Belum ada riwayat keputusan.'" />
                            </td>
                        </tr>
                    </tbody>
                    <template #footer><Pagination :data="items" /></template>
                </TableCard>
            </div>
            <dialog ref="dialog" aria-labelledby="decision-title" class="sneat-dialog" @cancel="form.processing && $event.preventDefault()">
                <form class="space-y-4" @submit.prevent="submit">
                    <h2 id="decision-title" class="text-lg font-bold">{{ form.status === 'approved' ? 'Setujui' : 'Tolak' }} penarikan</h2>
                    <dl class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-4 text-sm">
                        <div>
                            <dt class="sneat-muted">Siswa</dt>
                            <dd class="font-semibold">{{ selected?.siswa }}</dd>
                        </div>
                        <div>
                            <dt class="sneat-muted">Pengaju</dt>
                            <dd>{{ selected?.requestedBy }}</dd>
                        </div>
                        <div>
                            <dt class="sneat-muted">Jumlah penarikan</dt>
                            <dd class="font-semibold text-rose-700">{{ formatRupiah(selected?.jumlah) }}</dd>
                        </div>
                        <div>
                            <dt class="sneat-muted">Saldo saat ini</dt>
                            <dd class="font-semibold">{{ formatRupiah(selected?.saldoSiswa) }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="sneat-muted">Saldo setelah disetujui</dt>
                            <dd class="font-semibold" :class="(selected?.saldoSiswa ?? 0) - (selected?.jumlah ?? 0) < 0 ? 'text-rose-700' : 'text-indigo-700'">
                                {{ formatRupiah((selected?.saldoSiswa ?? 0) - (selected?.jumlah ?? 0)) }}
                            </dd>
                        </div>
                    </dl>
                    <p v-if="serverError" role="alert" class="text-sm text-rose-700">{{ serverError }}</p>
                    <FormField v-if="form.status === 'rejected'" id="rejection-reason" label="Alasan penolakan (wajib)" :error="form.errors.reason">
                        <textarea
                            id="rejection-reason"
                            v-model="form.reason"
                            required
                            maxlength="1000"
                            class="w-full"
                            :disabled="form.processing"
                            :aria-invalid="!!form.errors.reason"
                            aria-describedby="rejection-reason-error"
                        ></textarea>
                    </FormField>
                    <p v-else class="text-sm text-slate-600">Saldo akan diperiksa kembali sebelum penarikan dicatat.</p>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-lg border px-4 py-2 text-sm" :disabled="form.processing" @click="dialog?.close()">Batal</button>
                        <button class="sneat-primary rounded-lg px-4 py-2 text-sm font-semibold" :aria-busy="form.processing" :disabled="form.processing">
                            {{ form.processing ? 'Memproses…' : 'Konfirmasi' }}
                        </button>
                    </div>
                </form>
            </dialog>
        </main>
    </MainLayout>
</template>
