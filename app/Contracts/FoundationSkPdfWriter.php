<?php
namespace App\Contracts;
use App\Models\{FoundationSkTemplate,User};
interface FoundationSkPdfWriter { public function generateSingle(FoundationSkTemplate $template, User $user, array $values=[]): string; public function generateBatch(FoundationSkTemplate $template, iterable $users, array $values=[]): array; }
