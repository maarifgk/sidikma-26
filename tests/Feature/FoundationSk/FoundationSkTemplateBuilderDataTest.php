<?php

namespace Tests\Feature\FoundationSk;

use App\Models\FoundationSkTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationSkTemplateBuilderDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_template_persists_structured_builder_data_without_overwriting_legacy_fields(): void
    {
        $builder = FoundationSkTemplate::defaultBuilderData();
        $builder['header']['institution_name'] = 'Yayasan Contoh';
        $builder['decision']['title'] = 'Surat Keputusan';
        $builder['sections']['menimbang'] = 'Bahwa diperlukan keputusan.';

        $template = FoundationSkTemplate::create([
            'name' => 'Builder Test', 'slug' => 'builder-test', 'paper_size' => 'A4', 'orientation' => 'portrait',
            'html_template' => '<p>legacy</p>', 'content' => '<p>content lama</p>', 'custom_css' => '.old{}',
            'builder_data' => $builder, 'is_active' => true,
        ]);

        $reloaded = FoundationSkTemplate::findOrFail($template->id);
        $this->assertSame('Yayasan Contoh', $reloaded->builder_data['header']['institution_name']);
        $this->assertSame('Surat Keputusan', $reloaded->builder_data['decision']['title']);
        $this->assertSame('<p>content lama</p>', $reloaded->content);
        $this->assertSame('.old{}', $reloaded->custom_css);
    }

    public function test_edit_template_reloads_builder_data(): void
    {
        $template = FoundationSkTemplate::create(['name' => 'Edit', 'slug' => 'edit-builder', 'paper_size' => 'A4', 'orientation' => 'portrait', 'html_template' => 'x', 'content' => 'x', 'is_active' => true]);
        $template->update(['builder_data' => ['header' => ['email' => 'admin@example.test']]]);

        $this->assertSame('admin@example.test', FoundationSkTemplate::findOrFail($template->id)->builder_data['header']['email']);
    }

    public function test_legacy_template_without_builder_data_has_safe_defaults(): void
    {
        $template = FoundationSkTemplate::create(['name' => 'Legacy', 'slug' => 'legacy-builder', 'paper_size' => 'A4', 'orientation' => 'portrait', 'html_template' => 'x', 'content' => 'x', 'builder_data' => null, 'is_active' => true]);

        $data = $template->fresh()->builder_data;
        $this->assertSame('', $data['header']['institution_name']);
        $this->assertSame(11, $data['fonts']['body']);
    }
}
