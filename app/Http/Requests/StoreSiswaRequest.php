<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiswaRequest extends FormRequest
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
            'nis' => ['required', 'string', 'max:20', 'unique:siswa,nis'],
            'nama' => ['required', 'string', 'max:100'],
            'kontak' => ['nullable', 'string', 'max:25'],
        ];
    }
}
