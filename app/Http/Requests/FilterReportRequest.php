<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterReportRequest extends FormRequest
{
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'jenis' => ['nullable', 'in:masuk,keluar'],
            'kelas_id' => ['nullable', 'integer'],
            'siswa_id' => ['nullable', 'integer'],
        ];
    }
}
