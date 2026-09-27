<?php

namespace Tests\Feature;

use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_action_and_exposes_changed_values(): void
    {
        $siswa = Siswa::factory()->create(['nama' => 'Budi']);
        $siswa->update(['nama' => 'Budi Santoso']);

        $this->get('/audit-log?table=siswa&action=UPDATE')
            ->assertInertia(fn (Assert $page) => $page->component('Audit/Index')
                ->where('items.total', 1)
                ->where('items.data.0.oldValues.nama', 'Budi')
                ->where('items.data.0.newValues.nama', 'Budi Santoso'));
    }

    public function test_rejects_end_date_before_start_date(): void
    {
        $this->from('/audit-log')->get('/audit-log?start_date=2026-09-30&end_date=2026-09-01')
            ->assertSessionHasErrors('end_date');
    }
}
