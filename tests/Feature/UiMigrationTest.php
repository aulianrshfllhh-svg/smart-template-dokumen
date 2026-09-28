<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrated_screens_render_with_existing_authentication_and_filters(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->firstOrFail();
        $operator = User::where('role', 'operator')->firstOrFail();
        app(\App\Services\DocumentTemplateService::class)->getAllTemplates();
        $template = DocumentTemplate::firstOrFail();
        $this->actingAs($admin);
        foreach ([
            '/dashboard', '/renja/arsip', '/admin/templates',
            '/admin/templates/'.$template->id.'/edit', '/admin/verifikasi',
            '/admin/monitoring-opd', '/admin/master-opd', '/admin/master-opd/'.$operator->opd_id,
        ] as $path) {
            $this->get($path)->assertOk()->assertSee('ed-sidebar')->assertSee('Pengaturan Tampilan');
        }
        $this->get('/renja/dokumen-fix')->assertRedirect(route('renja.archive.index'));
        $this->get('/admin/monitoring-opd?search=NonexistentOpd&status=all')->assertOk();
        $this->get('/admin/verifikasi?search=NonexistentOpd&status=perlu_revisi')->assertOk();

        $this->actingAs($operator);
        $this->get('/dashboard')->assertOk()->assertSee('Dokumen Saya')
            ->assertDontSee('OPD &amp; Pengguna', false)->assertDontSee('href="'.route('admin.templates.index').'"', false);
        $this->get('/renja/arsip?search=NonexistentDocument')->assertOk();
        $this->get('/admin/templates')->assertRedirect(route('operator.dashboard'));
        $this->get('/admin/master-opd')->assertRedirect(route('operator.dashboard'));
        $this->get('/admin/verifikasi')->assertRedirect(route('operator.dashboard'));
    }
}
