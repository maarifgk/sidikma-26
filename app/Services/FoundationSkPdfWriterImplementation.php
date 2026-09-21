<?php
namespace App\Services;
use App\Contracts\FoundationSkPdfWriter; use App\Models\{FoundationSkTemplate,User};
class FoundationSkPdfWriterImplementation implements FoundationSkPdfWriter { public function __construct(private FoundationSkPdfService $service){} public function generateSingle(FoundationSkTemplate $template,User $user,array $values=[]):string{return $this->service->pdf($template,$user,$values);} public function generateBatch(FoundationSkTemplate $template,iterable $users,array $values=[]):array{$out=[];foreach($users as $u)$out[]=$this->generateSingle($template,$u,$values);return $out;} }
