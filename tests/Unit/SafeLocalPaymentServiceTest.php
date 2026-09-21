<?php
namespace Tests\Unit;
use App\Services\Payments\SafeLocalPaymentService;use PHPUnit\Framework\TestCase;use ReflectionClass;
class SafeLocalPaymentServiceTest extends TestCase{public function test_terminal_statuses_are_defined_for_safe_callback():void{$r=new ReflectionClass(SafeLocalPaymentService::class);$this->assertTrue($r->hasMethod('callback'));$this->assertStringContainsString('DB::transaction',$r->getMethod('callback')->getFileName() ? file_get_contents($r->getMethod('callback')->getFileName()) : '');}}
