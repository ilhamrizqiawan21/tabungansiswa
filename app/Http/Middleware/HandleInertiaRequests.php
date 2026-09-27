<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\TransaksiApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => ['admin' => fn () => $request->user('admin') ? ['id' => $request->user('admin')->id, 'nama' => $request->user('admin')->nama, 'username' => $request->user('admin')->username, 'role' => $request->user('admin')->role] : null],
            'pendingApprovals' => fn () => $request->user('admin')?->isAdmin()
                ? TransaksiApproval::whereHas('status', fn ($q) => $q->where('name', 'pending'))->count()
                : 0,
            'activeSession' => fn () => ['kelas' => Setting::get('active_class'), 'tahun' => Setting::get('active_year'), 'semester' => Setting::get('active_semester')],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appSettings' => [
                'teacherName' => fn () => Setting::get('teacher_name', 'Ilham Rizqiawan, S.Pd.'),
                'schoolName' => fn () => Setting::get('school_name', 'MTs. Al-Ihsan Batujajar'),
                'teacherPhone' => fn () => Setting::get('teacher_phone', ''),
                'schoolLogo' => fn () => ($path = Setting::get('school_logo')) ? Storage::disk('public')->url($path) : null,
            ],
        ];
    }
}
