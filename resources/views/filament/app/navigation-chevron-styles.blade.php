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
