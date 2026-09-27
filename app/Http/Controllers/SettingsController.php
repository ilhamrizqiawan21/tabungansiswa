<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\TahunPelajaran;
use App\Services\ActiveSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => [
                'teacherName' => Setting::get('teacher_name', 'Ilham Rizqiawan, S.Pd.'),
                'schoolName' => Setting::get('school_name', 'MTs. Al-Ihsan Batujajar'),
                'teacherPhone' => Setting::get('teacher_phone', '0895802329062'),
                'schoolLogo' => $this->assetUrl('school_logo'),
                'teacherAvatar' => $this->assetUrl('teacher_avatar'),
                'activeYearId' => ($id = Setting::get('active_year_id')) ? (int) $id : null,
                'activeClassId' => ($id = Setting::get('active_class_id')) ? (int) $id : null,
            ],
            'years' => TahunPelajaran::orderByDesc('tahun')->orderBy('semester')->get(['id', 'tahun', 'semester', 'status']),
            'classes' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(['id', 'nama_kelas', 'tingkat', 'tahun_pelajaran_id']),
        ]);
    }

    public function update(UpdateSettingsRequest $request, ActiveSession $session): RedirectResponse
    {
        $data = $request->validated();

        $oldLogo = Setting::get('school_logo');
        $oldAvatar = Setting::get('teacher_avatar');
        $logoPath = null;
        $avatarPath = null;

        try {
            $logoPath = $request->file('schoolLogoFile')?->store('school-logos', 'public');
            $avatarPath = $request->file('teacherAvatarFile')?->store('avatars', 'public');

            DB::transaction(function () use ($data, $logoPath, $avatarPath, $session): void {
                Setting::put('teacher_name', $data['teacherName']);
                Setting::put('school_name', $data['schoolName']);
                Setting::put('teacher_phone', $data['teacherPhone'] ?? '');
                if ($logoPath) {
                    Setting::put('school_logo', $logoPath);
                }
                if ($avatarPath) {
                    Setting::put('teacher_avatar', $avatarPath);
                }

                $year = TahunPelajaran::findOrFail($data['activeYearId']);
                $kelas = Kelas::findOrFail($data['activeClassId']);
                $session->activate($year, $kelas);
            });

        } catch (\Throwable $exception) {
            foreach ([$logoPath, $avatarPath] as $path) {
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
            }
            throw $exception;
        }

        if ($logoPath && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }
        if ($avatarPath && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return back()->with('success', 'Pengaturan dan sesi aktif berhasil disimpan.');
    }

    private function assetUrl(string $key): ?string
    {
        $path = Setting::get($key);

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
