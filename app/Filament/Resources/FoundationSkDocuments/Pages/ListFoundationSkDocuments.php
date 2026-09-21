<?php
namespace App\Filament\Resources\FoundationSkDocuments\Pages;
use App\Filament\Resources\FoundationSkDocuments\FoundationSkDocumentResource;use Filament\Actions\CreateAction;use Filament\Resources\Pages\ListRecords;
class ListFoundationSkDocuments extends ListRecords { protected static string $resource=FoundationSkDocumentResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
