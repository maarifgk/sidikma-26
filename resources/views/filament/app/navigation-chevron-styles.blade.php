<style>
    .fi-panel-app .fi-sidebar-item.app-nav-has-children > .fi-sidebar-item-btn::after {
        content: '';
        flex: none;
        width: 0.48rem;
        height: 0.48rem;
        margin-inline: auto 0.2rem;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        color: rgba(255, 255, 255, 0.65);
        transform: translateY(-0.15rem) rotate(45deg);
        transition: transform 160ms ease;
    }

    .fi-panel-app .fi-sidebar-item.app-nav-has-children > .fi-sidebar-item-btn:hover::after {
        color: rgba(255, 255, 255, 0.9);
    }

    .fi-panel-app .fi-sidebar-item.app-nav-has-children.fi-active > .fi-sidebar-item-btn::after,
    .fi-panel-app .fi-sidebar-item.app-nav-has-children.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn::after {
        color: var(--ui-yellow-300);
        transform: translateY(0.15rem) rotate(225deg);
    }

    .fi-panel-app .fi-sidebar-item.app-nav-has-children.fi-active > .fi-sidebar-item-btn::after {
        color: var(--ui-yellow-900);
    }

</style>

@if(auth()->user()?->hasRole(\App\Models\User::ROLE_GURU_PEGAWAI))
<style>
    body.fi-panel-app{background:#edf4ff!important}
    body.fi-panel-app .fi-sidebar,body.fi-panel-app .fi-topbar,body.fi-panel-app .fi-header{display:none!important}
    body.fi-panel-app .fi-main{min-height:100dvh!important;margin:0!important;padding:0!important}
    body.fi-panel-app .fi-main-ctn{width:100%!important;min-height:100dvh!important}
    body.fi-panel-app .fi-page{width:100%;max-width:620px;min-height:100dvh;margin:0 auto;background:transparent;box-shadow:none}
    body.fi-panel-app .fi-page-content{padding:12px 18px 128px!important}
    body.fi-panel-app:has(.teacher-mobile) .fi-page-content{padding:0!important}
</style>
@endif
