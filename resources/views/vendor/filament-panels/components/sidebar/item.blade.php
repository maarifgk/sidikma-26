@props([
    'active' => false,
    'activeChildItems' => false,
    'activeIcon' => null,
    'badge' => null,
    'badgeColor' => null,
    'badgeTooltip' => null,
    'childItems' => [],
    'first' => false,
    'grouped' => false,
    'icon' => null,
    'last' => false,
    'shouldOpenUrlInNewTab' => false,
    'sidebarCollapsible' => true,
    'subGrouped' => false,
    'subNavigation' => false,
    'url',
])

@php
    $sidebarCollapsible = $sidebarCollapsible && filament()->isSidebarCollapsibleOnDesktop();
    $hasSubmenu = count($childItems) > 0;
    $isSubmenuToggle = $hasSubmenu && blank($url);
    $tag = $isSubmenuToggle ? 'button' : 'a';
    $label = trim(strip_tags($slot->toHtml()));
    $submenuId = 'sidebar-submenu-' . substr(hash('sha256', ($subNavigation ? 'sub:' : 'main:') . $label . ':' . $url), 0, 16);
@endphp

<li
    @if ($hasSubmenu)
        x-data="{
            submenuOpen: @js($active || $activeChildItems),
            toggleSubmenu() {
                if (@js($sidebarCollapsible && (! $subNavigation)) && !$store.sidebar.isOpen) {
                    $store.sidebar.open();
                    this.submenuOpen = true;
                    return;
                }
                this.submenuOpen = !this.submenuOpen;
            },
        }"
        x-bind:class="{ 'sidikma-submenu-open': submenuOpen }"
    @endif
    {{
        $attributes->class([
            'fi-sidebar-item',
            'fi-active' => $active,
            'fi-sidebar-item-has-active-child-items' => $activeChildItems,
            'fi-sidebar-item-has-url' => filled($url),
        ])
    }}
>
    <div class="sidikma-sidebar-item-row">
        <{{ $tag }}
            @if ($isSubmenuToggle)
                type="button"
                x-on:click.stop="toggleSubmenu()"
                x-bind:aria-expanded="submenuOpen.toString()"
                aria-controls="{{ $submenuId }}"
            @else
                x-on:click="window.matchMedia(`(max-width: 1024px)`).matches && $store.sidebar.close()"
            @endif
            {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab) }}
            @if ($active)
                aria-current="page"
            @endif
            @if ($sidebarCollapsible && (! $subNavigation))
                x-bind:aria-label="$store.sidebar.isOpen ? null : @js(trim(strip_tags($slot->toHtml())))"
                x-data="{ tooltip: false }"
                x-effect="
                    tooltip = $store.sidebar.isOpen
                        ? false
                        : {
                              content: @js($slot->toHtml()),
                              placement: document.dir === 'rtl' ? 'left' : 'right',
                              theme: $store.theme,
                          }
                "
                x-tooltip.html="tooltip"
            @endif
            @class([
                'fi-sidebar-item-btn',
                'sidikma-sidebar-parent-link' => $hasSubmenu && ! $isSubmenuToggle,
            ])
        >
            @if (filled($icon) && ((! $subGrouped) || ($sidebarCollapsible && (! $subNavigation))))
                {{
                    \Filament\Support\generate_icon_html(($active && $activeIcon) ? $activeIcon : $icon, attributes: (new \Filament\Support\View\ComponentAttributeBag([
                        'x-show' => ($subGrouped && $sidebarCollapsible) ? '! $store.sidebar.isOpen' : false,
                    ]))->class(['fi-sidebar-item-icon']), size: \Filament\Support\Enums\IconSize::Large)
                }}
            @endif

            @if ((blank($icon) && $grouped) || $subGrouped)
                <div
                    @if (filled($icon) && $subGrouped && $sidebarCollapsible && (! $subNavigation))
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-sidebar-item-grouped-border"
                >
                    @if (! $first)
                        <div
                            class="fi-sidebar-item-grouped-border-part-not-first"
                        ></div>
                    @endif

                    @if (! $last)
                        <div
                            class="fi-sidebar-item-grouped-border-part-not-last"
                        ></div>
                    @endif

                    <div class="fi-sidebar-item-grouped-border-part"></div>
                </div>
            @endif

            <span
                @if ($sidebarCollapsible && (! $subNavigation))
                    x-show="$store.sidebar.isOpen"
                    x-transition:enter="fi-transition-enter"
                    x-transition:enter-start="fi-transition-enter-start"
                    x-transition:enter-end="fi-transition-enter-end"
                @endif
                class="fi-sidebar-item-label"
            >
                {{ $slot }}
            </span>

            @if (filled($badge))
                <span
                    @if ($sidebarCollapsible && (! $subNavigation))
                        x-show="$store.sidebar.isOpen"
                        x-transition:enter="fi-transition-enter"
                        x-transition:enter-start="fi-transition-enter-start"
                        x-transition:enter-end="fi-transition-enter-end"
                    @endif
                    class="fi-sidebar-item-badge-ctn"
                >
                    <x-filament::badge
                        :color="$badgeColor"
                        :tooltip="$badgeTooltip"
                    >
                        {{ $badge }}
                    </x-filament::badge>
                </span>
            @endif
            @if ($isSubmenuToggle)
                <span class="sidikma-submenu-chevron" aria-hidden="true" @if ($sidebarCollapsible && ! $subNavigation) x-show="$store.sidebar.isOpen" @endif></span>
            @endif
        </{{ $tag }}>
        @if ($hasSubmenu && ! $isSubmenuToggle)
            <button
                type="button"
                class="sidikma-submenu-toggle"
                x-on:click.stop="toggleSubmenu()"
                x-bind:aria-expanded="submenuOpen.toString()"
                aria-controls="{{ $submenuId }}"
                aria-label="Buka atau tutup submenu {{ $label }}"
                @if ($sidebarCollapsible && ! $subNavigation) x-show="$store.sidebar.isOpen" @endif
            >
                <span class="sidikma-submenu-chevron" aria-hidden="true"></span>
            </button>
        @endif
    </div>

    @if ($hasSubmenu)
        <ul
            id="{{ $submenuId }}"
            class="fi-sidebar-sub-group-items"
            x-cloak
            x-show.important="submenuOpen @if ($sidebarCollapsible && ! $subNavigation) && $store.sidebar.isOpen @endif"
        >
            @foreach ($childItems as $childItem)
                @php
                    $isChildItemChildItemsActive = $childItem->isChildItemsActive();
                    $isChildActive = (! $isChildItemChildItemsActive) && $childItem->isActive();
                    $childItemActiveIcon = $childItem->getActiveIcon();
                    $childItemBadge = $childItem->getBadge();
                    $childItemBadgeColor = $childItem->getBadgeColor($childItemBadge);
                    $childItemBadgeTooltip = $childItem->getBadgeTooltip($childItemBadge);
                    $childItemIcon = $childItem->getIcon();
                    $shouldChildItemOpenUrlInNewTab = $childItem->shouldOpenUrlInNewTab();
                    $childItemUrl = $childItem->getUrl();
                    $childItemExtraAttributes = $childItem->getExtraAttributeBag();
                @endphp

                <x-filament-panels::sidebar.item
                    :active="$isChildActive"
                    :active-child-items="$isChildItemChildItemsActive"
                    :active-icon="$childItemActiveIcon"
                    :badge="$childItemBadge"
                    :badge-color="$childItemBadgeColor"
                    :badge-tooltip="$childItemBadgeTooltip"
                    :first="$loop->first"
                    grouped
                    :icon="$childItemIcon"
                    :last="$loop->last"
                    :should-open-url-in-new-tab="$shouldChildItemOpenUrlInNewTab"
                    sub-grouped
                    :sub-navigation="$subNavigation"
                    :url="$childItemUrl"
                    :attributes="\Filament\Support\prepare_inherited_attributes($childItemExtraAttributes)"
                >
                    {{ $childItem->getLabel() }}
                </x-filament-panels::sidebar.item>
            @endforeach
        </ul>
    @endif
</li>
