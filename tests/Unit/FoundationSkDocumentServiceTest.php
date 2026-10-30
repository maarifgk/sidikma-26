<?php

namespace Tests\Unit;

use App\Models\FoundationSkDocument;
use App\Services\FoundationSkDocumentService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FoundationSkDocumentServiceTest extends TestCase
{
    public function test_existing_document_requires_explicit_replacement(): void
    {
        $service = new FoundationSkDocumentService;
        $guard = new ReflectionMethod($service, 'guardExistingDocument');
        $guard->setAccessible(true);

        $this->expectExceptionMessage('Konfirmasi penggantian terlebih dahulu.');

        $guard->invoke($service, new FoundationSkDocument, false);
    }

    public function test_existing_document_can_be_replaced_only_when_explicitly_allowed(): void
    {
        $service = new FoundationSkDocumentService;
        $guard = new ReflectionMethod($service, 'guardExistingDocument');
        $guard->setAccessible(true);

        $guard->invoke($service, new FoundationSkDocument, true);

        $this->addToAssertionCount(1);
    }
}
