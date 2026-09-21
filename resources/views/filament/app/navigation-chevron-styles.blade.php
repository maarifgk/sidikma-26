<style>
    .fi-panel-app .fi-sidebar-item.app-nav-has-children > .fi-sidebar-item-btn::after {
        content: '';
        flex: none;
        width: 0.48rem;
        height: 0.48rem;
        margin-inline: auto 0.3rem;
        border-right: 2px solid #9ca3af;
        border-bottom: 2px solid #9ca3af;
        transform: translateY(-0.15rem) rotate(45deg);
        transition: transform 160ms ease;
    }

    .fi-panel-app .fi-sidebar-item.app-nav-has-children.fi-active > .fi-sidebar-item-btn::after,
    .fi-panel-app .fi-sidebar-item.app-nav-has-children.fi-sidebar-item-has-active-child-items > .fi-sidebar-item-btn::after {
        transform: translateY(0.15rem) rotate(225deg);
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
