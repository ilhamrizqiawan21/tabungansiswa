export type Jenis = 'masuk' | 'keluar';
export type SiswaStatus = 'aktif' | 'lulus' | 'keluar';

export interface TahunPelajaran {
    id: number;
    tahun: string;
    semester: 'ganjil' | 'genap';
    status: 'aktif' | 'nonaktif';
    kelas_count?: number;
}

export interface Kelas {
    id: number;
    nama_kelas: string;
    tingkat: string;
    jurusan: string | null;
    tahun_pelajaran_id: number | null;
    wali_kelas: string | null;
    tahun_pelajaran?: TahunPelajaran | null;
    siswa_count?: number;
}

export interface Siswa {
    id: number;
    nis: string;
    nama: string;
    kontak: string | null;
    status: SiswaStatus;
    kelas_id: number | null;
    kelas?: Kelas | null;
    transaksi_count?: number;
    saldo_total?: number | string | null;
}

/** Transaction row as serialized by the controllers (camel-cased flags, formatted date). */
export interface TransaksiRow {
    id: number;
    siswa_id?: number;
    tanggal: string;
    siswa?: string;
    jenis: Jenis;
    jumlah: number;
    saldo?: number;
    keterangan: string | null;
    isReversal?: boolean;
    isReversed?: boolean;
}

export interface Option {
    id: number;
    label: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginationLink[];
}

export interface AdminUser {
    id: number;
    nama: string;
    username: string;
    role: 'admin' | 'operator';
}

/** Props shared by HandleInertiaRequests on every page. */
export interface PageProps {
    auth: { admin: AdminUser | null };
    pendingApprovals: number;
    activeSession: { kelas: string | null; tahun: string | null; semester: string | null };
    flash: { success: string | null; error: string | null };
    appSettings: { teacherName: string; schoolName: string; teacherPhone: string; schoolLogo: string | null };
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: PageProps;
    }
}
