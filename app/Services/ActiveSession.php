<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\TahunPelajaran;

/**
 * The working session (academic year + class) that scopes daily operations.
 * Master data is the source of truth; display strings are cached in settings so
 * the layout does not need extra queries on every request.
 */
class ActiveSession
{
    public function activate(TahunPelajaran $year, ?Kelas $kelas = null): void
    {
        TahunPelajaran::whereKeyNot($year->id)->update(['status' => 'nonaktif']);
        $year->update(['status' => 'aktif']);

        Setting::put('active_year_id', (string) $year->id);
        Setting::put('active_year', $year->tahun);
        Setting::put('active_semester', $year->semester);

        $kelas ??= $this->currentClassWithin($year) ?? $year->kelas()->orderBy('tingkat')->orderBy('nama_kelas')->first();

        Setting::put('active_class_id', $kelas ? (string) $kelas->id : '');
        Setting::put('active_class', $kelas?->nama_kelas);
    }

    public function classId(): ?int
    {
        $id = Setting::get('active_class_id');

        return $id ? (int) $id : null;
    }

    private function currentClassWithin(TahunPelajaran $year): ?Kelas
    {
        $id = $this->classId();

        return $id ? $year->kelas()->whereKey($id)->first() : null;
    }
}
