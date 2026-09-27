<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Class names are unique per academic year. The database unique index does not
     * catch duplicates when the year is NULL, so the rule is enforced here as well.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama_kelas' => [
                'required', 'string', 'max:50',
                Rule::unique('kelas', 'nama_kelas')
                    ->where('tahun_pelajaran_id', $this->input('tahun_pelajaran_id'))
                    ->ignore($this->route('kelas')),
            ],
            'tingkat' => ['required', 'string', 'max:10'],
            'jurusan' => ['nullable', 'string', 'max:50'],
            'tahun_pelajaran_id' => ['required', 'exists:tahun_pelajaran,id'],
            'wali_kelas' => ['nullable', 'string', 'max:100'],
        ];
    }
}
