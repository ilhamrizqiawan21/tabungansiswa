<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveSiswaRequest extends FormRequest
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
            'siswa_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'siswa_ids.*' => ['integer', 'distinct', 'exists:siswa,id'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['siswa_ids' => 'siswa', 'kelas_id' => 'kelas tujuan'];
    }
}
