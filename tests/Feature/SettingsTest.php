<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Kelas;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'username' => 'settings-admin',
            'password' => bcrypt('test-password'),
            'nama' => 'Pengelola',
            'role' => 'admin',
        ]);
    }

    private function settings(): array
    {
        return [
            '_method' => 'patch',
            'schoolName' => 'Sekolah Pelita',
            'teacherName' => 'Ibu Guru',
            'teacherPhone' => '081234567890',
            'activeYear' => '2026/2027',
            'activeSemester' => 'ganjil',
            'activeClass' => 'VII A',
        ];
    }

    public function test_school_identity_can_be_changed_without_login(): void
    {
        $this->from('/pengaturan')->post('/pengaturan', $this->settings())
            ->assertRedirect('/pengaturan')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'Sekolah Pelita']);
    }

    public function test_school_name_and_logo_are_saved_and_previous_logo_is_replaced(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('school-logos/old.png', 'old logo');
        Setting::put('school_logo', 'school-logos/old.png');
        $logo = UploadedFile::fake()->image('logo.png', 128, 128);

        $this->actingAs($this->admin(), 'admin')->from('/pengaturan')
            ->post('/pengaturan', [...$this->settings(), 'schoolLogoFile' => $logo])
            ->assertRedirect('/pengaturan')->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'Sekolah Pelita']);
        $this->assertDatabaseHas('settings', ['key' => 'school_logo', 'value' => 'school-logos/'.$logo->hashName()]);
        Storage::disk('public')->assertExists('school-logos/'.$logo->hashName());
        Storage::disk('public')->assertMissing('school-logos/old.png');
    }

    public function test_saving_without_a_file_preserves_the_existing_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('school-logos/current.png', 'logo');
        Setting::put('school_logo', 'school-logos/current.png');

        $this->actingAs($this->admin(), 'admin')->from('/pengaturan')
            ->post('/pengaturan', $this->settings())
            ->assertRedirect('/pengaturan')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'school_logo', 'value' => 'school-logos/current.png']);
        Storage::disk('public')->assertExists('school-logos/current.png');
    }

    public function test_invalid_upload_does_not_change_school_identity(): void
    {
        Storage::fake('public');
        Setting::put('school_name', 'Sekolah Lama');

        $this->actingAs($this->admin(), 'admin')->from('/pengaturan')
            ->post('/pengaturan', [
                ...$this->settings(),
                'schoolLogoFile' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            ])->assertSessionHasErrors('schoolLogoFile');

        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'Sekolah Lama']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_oversized_logo_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin(), 'admin')->from('/pengaturan')
            ->post('/pengaturan', [
                ...$this->settings(),
                'schoolLogoFile' => UploadedFile::fake()->image('logo.png')->size(2049),
            ])->assertSessionHasErrors('schoolLogoFile');

        $this->assertDatabaseCount('settings', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_school_identity_is_shared_with_the_layout_and_settings_form(): void
    {
        Setting::put('school_name', 'Sekolah Pelita');
        Setting::put('school_logo', 'school-logos/current.png');
        $url = Storage::disk('public')->url('school-logos/current.png');

        $this->actingAs($this->admin(), 'admin')->get('/pengaturan')
            ->assertInertia(fn (Assert $page) => $page->component('Settings/Index')
                ->where('appSettings.schoolName', 'Sekolah Pelita')
                ->where('appSettings.schoolLogo', $url)
                ->where('settings.schoolName', 'Sekolah Pelita')
                ->where('settings.schoolLogo', $url));
    }

    public function test_failed_save_preserves_old_logo_and_cleans_up_new_upload(): void
    {
        Storage::fake('public');
        Exceptions::fake();
        Storage::disk('public')->put('school-logos/old.png', 'old logo');
        Setting::put('school_name', 'Sekolah Lama');
        Setting::put('school_logo', 'school-logos/old.png');
        Kelas::creating(function () {
            throw new \RuntimeException('Simulated database failure');
        });
        $logo = UploadedFile::fake()->image('new.png');

        $this->actingAs($this->admin(), 'admin')
            ->post('/pengaturan', [...$this->settings(), 'schoolLogoFile' => $logo])
            ->assertInternalServerError();

        $this->assertDatabaseHas('settings', ['key' => 'school_name', 'value' => 'Sekolah Lama']);
        $this->assertDatabaseHas('settings', ['key' => 'school_logo', 'value' => 'school-logos/old.png']);
        Storage::disk('public')->assertExists('school-logos/old.png');
        Storage::disk('public')->assertMissing('school-logos/'.$logo->hashName());
        Exceptions::assertReported(\RuntimeException::class);
    }
}
