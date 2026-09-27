<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import FormField from '../../Components/FormField.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import TableCard from '../../Components/TableCard.vue';
import MainLayout from '../../Layouts/MainLayout.vue';
import type { Paginated } from '../../types';

type Values = Record<string, unknown> | null;
interface AuditRow {
    id: number;
    createdAt: string | null;
    admin: string;
    table: string;
    recordId: number | null;
    action: 'CREATE' | 'UPDATE' | 'DELETE';
    description: string | null;
    ip: string | null;
    oldValues: Values;
    newValues: Values;
}
const props = defineProps<{
    items: Paginated<AuditRow>;
    filters: { table?: string; action?: string; admin_id?: number; start_date?: string; end_date?: string };
    tables: string[];
    admins: Array<{ id: number; nama: string }>;
}>();

const form = useForm({
    table: props.filters.table ?? '',
    action: props.filters.action ?? '',
    admin_id: props.filters.admin_id ?? ('' as number | ''),
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
});
const apply = () => form.get('/audit-log', { preserveScroll: true });
const actionClasses: Record<AuditRow['action'], string> = { CREATE: 'is-aktif', UPDATE: 'is-warning', DELETE: 'is-keluar' };
const actionLabels: Record<AuditRow['action'], string> = { CREATE: 'Tambah', UPDATE: 'Ubah', DELETE: 'Hapus' };

const detail = ref<AuditRow | null>(null);
const detailDialog = ref<HTMLDialogElement | null>(null);
function openDetail(row: AuditRow) {
    detail.value = row;
    detailDialog.value?.showModal();
}
const ignoredKeys = ['created_at', 'updated_at'];
function changedKeys(row: AuditRow): string[] {
    const keys = new Set([...Object.keys(row.oldValues ?? {}), ...Object.keys(row.newValues ?? {})]);
    return [...keys].filter(key => !ignoredKeys.includes(key) && (row.action !== 'UPDATE' || key in (row.newValues ?? {})));
}
const show = (value: unknown) => (value === null || value === undefined || value === '' ? '—' : String(value));
</script>

<template>
    <MainLayout>
        <Head title="Audit log" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <PageHeader eyebrow="Administrasi" title="Audit log" description="Jejak perubahan data yang dilakukan dalam sistem." />
                <form class="sneat-card grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="apply">
                    <FormField id="audit-table" label="Tabel">
                        <select id="audit-table" v-model="form.table" class="w-full">
                            <option value="">Semua tabel</option>
                            <option v-for="table in tables" :key="table" :value="table">{{ table }}</option>
                        </select>
                    </FormField>
                    <FormField id="audit-action" label="Aksi">
                        <select id="audit-action" v-model="form.action" class="w-full">
                            <option value="">Semua aksi</option>
                            <option v-for="(label, value) in actionLabels" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </FormField>
                    <FormField id="audit-admin" label="Admin">
                        <select id="audit-admin" v-model="form.admin_id" class="w-full">
                            <option value="">Semua admin</option>
                            <option v-for="admin in admins" :key="admin.id" :value="admin.id">{{ admin.nama }}</option>
                        </select>
                    </FormField>
                    <FormField id="audit-start" label="Dari tanggal" :error="form.errors.start_date">
                        <input id="audit-start" v-model="form.start_date" type="date" class="w-full" />
                    </FormField>
                    <FormField id="audit-end" label="Sampai tanggal" :error="form.errors.end_date">
                        <input id="audit-end" v-model="form.end_date" type="date" :min="form.start_date || undefined" class="w-full" />
                    </FormField>
                    <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-5">
                        <button class="sneat-primary rounded-lg px-4 py-2.5 text-sm font-semibold" :disabled="form.processing" :aria-busy="form.processing">
                            Terapkan filter
                        </button>
                        <Link href="/audit-log" class="rounded-lg border px-4 py-2.5 text-sm">Reset</Link>
                    </div>
                </form>
                <TableCard title="Catatan perubahan" :description="`${items.total} catatan`">
                    <thead>
                        <tr>
                            <th scope="col">Waktu</th>
                            <th scope="col">Admin</th>
                            <th scope="col">Aksi</th>
                            <th scope="col">Data</th>
                            <th scope="col">Deskripsi</th>
                            <th scope="col">IP</th>
                            <th scope="col" class="text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items.data" :key="item.id">
                            <td class="whitespace-nowrap text-slate-500">{{ item.createdAt }}</td>
                            <td class="font-medium">{{ item.admin }}</td>
                            <td>
                                <span class="sneat-status" :class="actionClasses[item.action]">{{ actionLabels[item.action] }}</span>
                            </td>
                            <td class="font-mono text-xs text-indigo-700">{{ item.table }} #{{ item.recordId }}</td>
                            <td class="text-slate-600">{{ item.description }}</td>
                            <td class="font-mono text-xs sneat-muted">{{ item.ip || '—' }}</td>
                            <td class="text-right">
                                <button
                                    type="button"
                                    class="rounded bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-600"
                                    :aria-label="`Lihat detail ${item.table} #${item.recordId}`"
                                    @click="openDetail(item)"
                                >
                                    Lihat
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length">
                            <td colspan="7"><EmptyState title="Belum ada catatan audit untuk filter ini." /></td>
                        </tr>
                    </tbody>
                    <template #footer><Pagination :data="items" /></template>
                </TableCard>
            </div>
            <dialog ref="detailDialog" class="sneat-dialog sneat-dialog-wide" aria-labelledby="audit-detail-title">
                <div v-if="detail" class="space-y-4">
                    <div>
                        <h2 id="audit-detail-title" class="text-lg font-bold">{{ actionLabels[detail.action] }} · {{ detail.table }} #{{ detail.recordId }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ detail.createdAt }} oleh {{ detail.admin }}</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="sneat-table w-full text-left text-sm">
                            <caption class="sr-only">Nilai sebelum dan sesudah perubahan</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Kolom</th>
                                    <th v-if="detail.action !== 'CREATE'" scope="col">Sebelum</th>
                                    <th v-if="detail.action !== 'DELETE'" scope="col">Sesudah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="key in changedKeys(detail)" :key="key">
                                    <th scope="row" class="font-mono text-xs">{{ key }}</th>
                                    <td v-if="detail.action !== 'CREATE'" class="break-all">{{ show(detail.oldValues?.[key]) }}</td>
                                    <td v-if="detail.action !== 'DELETE'" class="break-all font-semibold">{{ show(detail.newValues?.[key]) }}</td>
                                </tr>
                                <tr v-if="!changedKeys(detail).length">
                                    <td colspan="3" class="sneat-muted">Tidak ada nilai yang tercatat.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" class="rounded-lg border px-4 py-2 text-sm" autofocus @click="detailDialog?.close()">Tutup</button>
                    </div>
                </div>
            </dialog>
        </main>
    </MainLayout>
</template>
