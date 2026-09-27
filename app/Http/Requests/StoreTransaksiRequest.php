<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaksiRequest extends FormRequest
{
    /** Upper bound for a single transaction (Rp 100 juta); guards against typos and decimal overflow. */
    public const MAX_JUMLAH = 100_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'exists:siswa,id'],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:masuk,keluar'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:'.self::MAX_JUMLAH],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'lanjut' => ['sometimes', 'boolean'],
        ];
    }
}
