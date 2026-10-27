<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SidebarSubmenuTest extends TestCase
{
    public function test_inactive_parent_link_keeps_its_url_and_has_independent_submenu_button(): void
    {
        $xpath = $this->renderItem('/parent');
        $this->assertSame('/parent', $xpath->query('//a[contains(@class,"fi-sidebar-item-btn")]')->item(0)->getAttribute('href'));
        $buttons = $xpath->query('//button[@aria-controls]');
        $this->assertCount(1, $buttons);
        $this->assertSame('button', $buttons->item(0)->getAttribute('type'));
        $id = $buttons->item(0)->getAttribute('aria-controls');
        $this->assertCount(1, $xpath->query('//ul[@id="'.$id.'"]'));
        $this->assertCount(1, $xpath->query('//ul[@id="'.$id.'"]//a[@href="/child"]'));
        $this->assertCount(0, $xpath->query('//a//button'));
    }

    public function test_parent_without_url_is_keyboard_operable_button_and_keeps_submenu(): void
    {
        $xpath = $this->renderItem(null);
        $buttons = $xpath->query('//button[@aria-controls]');
        $this->assertCount(1, $buttons);
        $this->assertStringContainsString('Menu Induk', $buttons->item(0)->textContent);
        $this->assertCount(1, $xpath->query('//a[@href="/child"]'));
        $this->assertCount(0, $xpath->query('//button//button'));
    }

    private function renderItem(?string $url): DOMXPath
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $html = Blade::render(
            '<x-filament-panels::sidebar.item :url="$url" :child-items="$children">Menu Induk</x-filament-panels::sidebar.item>',
            ['url' => $url, 'children' => [NavigationItem::make('Anak Menu')->url('/child')]],
        );
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }
}
