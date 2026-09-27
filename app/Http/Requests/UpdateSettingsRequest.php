<?php

namespace App\Http\Requests;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
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
            'teacherName' => ['required', 'string', 'max:100'],
            'schoolName' => ['required', 'string', 'max:150'],
            'teacherPhone' => ['nullable', 'string', 'max:30'],
            'activeYearId' => ['required', 'integer', 'exists:tahun_pelajaran,id'],
            'activeClassId' => ['required', 'integer', 'exists:kelas,id'],
            'schoolLogoFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'teacherAvatarFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $belongsToYear = Kelas::whereKey($this->integer('activeClassId'))
                    ->where('tahun_pelajaran_id', $this->integer('activeYearId'))
                    ->exists();

                if (! $belongsToYear) {
                    $validator->errors()->add('activeClassId', 'Kelas tidak terdaftar pada tahun pelajaran yang dipilih.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['activeYearId' => 'tahun pelajaran', 'activeClassId' => 'kelas'];
    }
}
