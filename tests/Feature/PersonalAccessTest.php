<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PersonalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_the_application_reaches_dashboard_without_login(): void
    {
        $this->followingRedirects()->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard'));

        $this->assertDatabaseCount('admin', 1);
        $this->assertDatabaseHas('admin', ['role' => 'admin']);
    }

    #[TestWith(['/dashboard', 'Dashboard'])]
    #[TestWith(['/master/siswa', 'Master/Siswa'])]
    #[TestWith(['/transaksi', 'Transactions/Index'])]
    #[TestWith(['/approval', 'Approvals/Index'])]
    #[TestWith(['/audit-log', 'Audit/Index'])]
    #[TestWith(['/laporan', 'Reports/Index'])]
    #[TestWith(['/pengaturan', 'Settings/Index'])]
    public function test_pages_use_existing_admin_without_login(string $url, string $component): void
    {
        $admin = Admin::create([
            'username' => 'owner',
            'password' => bcrypt('unused-password'),
            'nama' => 'Pengelola',
            'role' => 'admin',
        ]);

        $this->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->component($component)
            ->where('auth.admin.id', $admin->id));

        $this->assertDatabaseCount('admin', 1);
    }

    public function test_old_login_link_redirects_to_dashboard(): void
    {
        $this->get('/login')->assertRedirect('/dashboard');
    }
}
