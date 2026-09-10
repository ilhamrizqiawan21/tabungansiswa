<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Setting;
use App\Models\TahunPelajaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', ['settings' => [
            'teacherName' => Setting::get('teacher_name', 'Ilham Rizqiawan, S.Pd.'),
            'schoolName' => Setting::get('school_name', 'MTs. Al-Ihsan Batujajar'),
            'teacherPhone' => Setting::get('teacher_phone', '0895802329062'),
            'schoolLogo' => $this->assetUrl('school_logo'),
            'teacherAvatar' => $this->assetUrl('teacher_avatar'),
            'activeYear' => Setting::get('active_year', '2026/2027'),
            'activeSemester' => Setting::get('active_semester', 'ganjil'),
            'activeClass' => Setting::get('active_class', 'Kelas VII-A'),
        ]]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'teacherName' => ['required', 'string', 'max:100'],
            'schoolName' => ['required', 'string', 'max:150'],
            'teacherPhone' => ['nullable', 'string', 'max:30'],
            'activeYear' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'activeSemester' => ['required', 'in:ganjil,genap'],
            'activeClass' => ['required', 'string', 'max:50'],
            'schoolLogoFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'teacherAvatarFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldLogo = Setting::get('school_logo');
        $oldAvatar = Setting::get('teacher_avatar');
        $logoPath = null;
        $avatarPath = null;

        try {
            $logoPath = $request->file('schoolLogoFile')?->store('school-logos', 'public');
            $avatarPath = $request->file('teacherAvatarFile')?->store('avatars', 'public');

            DB::transaction(function () use ($data, $logoPath, $avatarPath): void {
                Setting::put('teacher_name', $data['teacherName']);
                Setting::put('school_name', $data['schoolName']);
                Setting::put('teacher_phone', $data['teacherPhone'] ?? '');
                if ($logoPath) {
                    Setting::put('school_logo', $logoPath);
                }
                if ($avatarPath) {
                    Setting::put('teacher_avatar', $avatarPath);
                }
                $year = TahunPelajaran::firstOrCreate(['tahun' => $data['activeYear'], 'semester' => $data['activeSemester']], ['status' => 'nonaktif']);
                TahunPelajaran::query()->update(['status' => 'nonaktif']);
                $year->update(['status' => 'aktif']);
                $kelas = Kelas::firstOrCreate(['nama_kelas' => $data['activeClass'], 'tahun_pelajaran_id' => $year->id], ['tingkat' => 'X']);
                Setting::put('active_year_id', (string) $year->id);
                Setting::put('active_class_id', (string) $kelas->id);
                Setting::put('active_year', $data['activeYear']);
                Setting::put('active_semester', $data['activeSemester']);
                Setting::put('active_class', $data['activeClass']);
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
