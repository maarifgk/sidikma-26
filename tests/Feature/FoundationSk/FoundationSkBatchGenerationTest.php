<?php
namespace Tests\Feature\FoundationSk;

use App\Models\{FoundationSkNumberSetting,FoundationSkTemplate,User};
use App\Services\FoundationSkBatchGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FoundationSkBatchGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_creates_private_generated_documents_and_unique_numbers(): void
    {
        Storage::fake('documents');
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('isAdminInduk')->andReturn(true);
        $actor->shouldReceive('can')->andReturn(true);
        $users = User::factory()->count(2)->create();
        $template = FoundationSkTemplate::create(['name'=>'Template','slug'=>'template-test','paper_size'=>'A4','orientation'=>'portrait','html_template'=>'Nomor {{nomor_sk}} untuk {{nama_lengkap}}','content'=>'Nomor {{nomor_sk}} untuk {{nama_lengkap}}','is_active'=>true]);
        FoundationSkNumberSetting::create(['periode'=>'2026/2027','nomor_pattern'=>'SK/{{nomor_urut}}/{{periode}}','nomor_awal'=>1,'nomor_berikutnya'=>1,'digit_nomor'=>4,'is_active'=>true]);

        $docs = app(FoundationSkBatchGenerationService::class)->generate($template,$users->all(),'2026/2027',$actor);

        $this->assertCount(2,$docs);
        $this->assertSame(3,FoundationSkNumberSetting::first()->nomor_berikutnya);
        $this->assertDatabaseCount('foundation_sk_documents',2);
        foreach($docs as $doc){$this->assertSame('generated',$doc->source_type);$this->assertSame('generated',$doc->matched_by);Storage::disk('documents')->assertExists($doc->file_path);$this->assertStringStartsWith('%PDF-',$this->app['filesystem']->disk('documents')->get($doc->file_path));}
    }

    public function test_invalid_empty_batch_does_not_change_counter(): void
    {
        $actor=Mockery::mock(User::class);$actor->shouldReceive('isAdminInduk')->andReturn(true);$actor->shouldReceive('can')->andReturn(true);
        $template=FoundationSkTemplate::create(['name'=>'Template','slug'=>'template-empty','paper_size'=>'A4','orientation'=>'portrait','html_template'=>'x','content'=>'x','is_active'=>true]);
        FoundationSkNumberSetting::create(['periode'=>'2026/2027','nomor_pattern'=>'{{nomor_urut}}','nomor_awal'=>7,'nomor_berikutnya'=>7,'digit_nomor'=>4,'is_active'=>true]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(FoundationSkBatchGenerationService::class)->generate($template,[],'2026/2027',$actor);
        $this->assertSame(7,FoundationSkNumberSetting::first()->nomor_berikutnya);
    }
}
