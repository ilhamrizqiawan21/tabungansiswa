<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import DownloadLink from '../../Components/DownloadLink.vue';
import EmptyState from '../../Components/EmptyState.vue';
import FlashBanner from '../../Components/FlashBanner.vue';
import PageHeader from '../../Components/PageHeader.vue';
import TableCard from '../../Components/TableCard.vue';
import MainLayout from '../../Layouts/MainLayout.vue';

defineProps<{ items: { name: string; size: number; created_at: string }[] }>();
const form = useForm({});
const size = (bytes: number) => (bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : (bytes / 1024).toFixed(1) + ' KB');
const date = (value: string) => new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
</script>

<template>
    <MainLayout>
        <Head title="Backup data" />
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <PageHeader eyebrow="Administrasi" title="Backup data" description="Simpan salinan seluruh data tabungan, identitas sekolah, dan lampiran." />
                <FlashBanner />
                <section class="sneat-card p-6">
                    <h2 class="font-bold">Buat salinan sekarang</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                        Arsip ZIP berisi database, logo/lampiran, dan petunjuk pemulihan. Setelah dibuat, unduh salinannya ke flashdisk atau media lain. Backup
                        otomatis juga berjalan setiap hari bila penjadwal (
                        <code>php artisan schedule:work</code>
                        ) aktif.
                    </p>
                    <form class="mt-5" @submit.prevent="form.post('/backup', { preserveScroll: true })">
                        <button :aria-busy="form.processing" :disabled="form.processing" class="sneat-primary rounded-lg px-5 py-3 text-sm font-semibold">
                            {{ form.processing ? 'Sedang membuat backup…' : 'Buat backup baru' }}
                        </button>
                    </form>
                </section>
                <TableCard title="Arsip tersimpan" :description="`${items.length} backup tersedia di komputer ini.`">
                    <thead>
                        <tr>
                            <th scope="col">Waktu backup</th>
                            <th scope="col">Ukuran</th>
                            <th scope="col">Unduh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.name">
                            <td>
                                <p class="font-semibold">{{ date(item.created_at) }}</p>
                                <p class="mt-1 break-all text-xs text-slate-500">{{ item.name }}</p>
                            </td>
                            <td class="whitespace-nowrap">{{ size(item.size) }}</td>
                            <td>
                                <DownloadLink :href="`/backup/${item.name}`" class="whitespace-nowrap font-semibold text-indigo-600">Unduh ZIP ↓</DownloadLink>
                            </td>
                        </tr>
                        <tr v-if="!items.length">
                            <td colspan="3"><EmptyState title="Belum ada backup." description="Buat salinan pertama untuk menyimpan data Anda." /></td>
                        </tr>
                    </tbody>
                </TableCard>
                <details class="sneat-card sneat-disclosure">
                    <summary class="cursor-pointer p-5 font-bold">Cara memulihkan data dari backup</summary>
                    <ol class="list-decimal space-y-2 border-t p-5 pl-10 text-sm leading-relaxed text-slate-600">
                        <li>Hentikan aplikasi dan buat backup dari data yang sekarang (jaga-jaga).</li>
                        <li>
                            Ekstrak arsip ZIP ke folder sementara. Periksa
                            <code>manifest.json</code>
                            : kolom
                            <code>database_sha256</code>
                            harus sama dengan hash file database.
                        </li>
                        <li>
                            <strong>MySQL/MariaDB:</strong>
                            buat database kosong, lalu impor
                            <code>database.sql</code>
                            (mis.
                            <code>mysql -u root nama_db &lt; database.sql</code>
                            atau melalui HeidiSQL/phpMyAdmin). Arahkan
                            <code>DB_DATABASE</code>
                            di
                            <code>.env</code>
                            ke database tersebut.
                        </li>
                        <li>
                            <strong>SQLite:</strong>
                            salin
                            <code>database.sqlite</code>
                            ke lokasi database yang diatur di
                            <code>.env</code>
                            .
                        </li>
                        <li>
                            Salin isi folder
                            <code>uploads</code>
                            ke
                            <code>storage/app/public</code>
                            , lalu jalankan
                            <code>php artisan storage:link</code>
                            .
                        </li>
                        <li>
                            Gunakan versi aplikasi yang sama, jalankan
                            <code>php artisan migrate</code>
                            bila versi lebih baru, lalu
                            <code>php artisan tabungan:rekonsiliasi</code>
                            untuk memeriksa saldo.
                        </li>
                        <li>Cocokkan jumlah siswa, transaksi, dan saldo sebelum aplikasi dipakai kembali.</li>
                    </ol>
                </details>
            </div>
        </main>
    </MainLayout>
</template>
