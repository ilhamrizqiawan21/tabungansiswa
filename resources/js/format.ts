const rupiahFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const numberFormatter = new Intl.NumberFormat('id-ID');
const compactFormatter = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });

/** Single rupiah format for the whole app: "Rp 10.000" (no decimals). */
export function formatRupiah(value: number | string | null | undefined): string {
    return rupiahFormatter.format(Number(value ?? 0)).replace(/ /g, ' ');
}

/** Signed rupiah for ledgers: "+Rp 10.000" / "−Rp 5.000". */
export function formatSignedRupiah(value: number, jenis: 'masuk' | 'keluar'): string {
    return (jenis === 'masuk' ? '+' : '−') + formatRupiah(value);
}

export function formatNumber(value: number | string | null | undefined): string {
    return numberFormatter.format(Number(value ?? 0));
}

export function formatCompact(value: number): string {
    return compactFormatter.format(value);
}

/** Normalize an Indonesian phone number to the international form used by wa.me links. */
export function whatsappNumber(phone: string | null | undefined): string | null {
    const digits = (phone ?? '').replace(/\D/g, '');
    if (digits.length < 8) return null;
    if (digits.startsWith('62')) return digits;
    if (digits.startsWith('0')) return '62' + digits.slice(1);
    return digits;
}
