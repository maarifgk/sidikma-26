<?php
namespace App\Contracts;
use App\Models\{FoundationSkDocument,User};
interface FoundationSkDocumentWriter { public function createOrUpdate(User $user,int $year,int $templateId,string $path,array $metadata,User $actor): FoundationSkDocument; public function deleteFile(?string $path): void; public function storeFile(string $path,string $contents): bool; public function cleanupFiles(array $paths): void; }
