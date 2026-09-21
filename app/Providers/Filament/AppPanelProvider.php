<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\AttendanceDashboard;
use App\Filament\Admin\Pages\AttendanceLeaveRequests;
use App\Filament\Admin\Pages\AttendanceReport;
use App\Filament\Admin\Pages\AttendanceSettings;
use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Pages\MyAttendance;
use App\Filament\App\Pages\MyAttendanceLeaveRequests;
use App\Filament\App\Pages\MyComplaints;
use App\Filament\App\Pages\MyDecrees;
use App\Filament\App\Pages\MyPayments;
use App\Filament\App\Pages\MyProfile;
use App\Filament\App\Pages\SchoolDecrees;
use App\Filament\Auth\Pages\IntegratedLogin;
use App\Filament\Auth\Pages\RequestPasswordReset;
use App\Filament\Auth\Pages\ResetPassword;
use App\Filament\GlobalSearch\NavigationSearchProvider;
use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\Billings\BillingResource;
use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Filament\Resources\DecreeSubmissions\DecreeSubmissionResource;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Http\Middleware\RedirectAdminIndukToAdminPanel;
use App\Models\ApplicationSetting;
use App\Models\Employee;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->darkMode(false)
            ->themeSwitcher(false)
            ->login(IntegratedLogin::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->globalSearch(NavigationSearchProvider::class)
            ->databaseNotifications()
            ->brandName(fn (): string => ApplicationSetting::current()->short_title)
            ->brandLogo(fn (): string => asset('images/sidikma-header-logo.png'))
            ->brandLogoHeight('3rem')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.app.navigation-chevron-styles'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.shared.panel-polish'),
            )
            ->navigationGroups([
                NavigationGroup::make('HOMES')
                    ->collapsible(false),
                NavigationGroup::make('SERVICE'),
                NavigationGroup::make('PAYMENT')
                    ->collapsible(false),
            ])
            ->navigationItems([
                NavigationItem::make('Presensi')
                    ->extraAttributes(['class' => 'app-nav-has-children'])
                    ->group('HOMES')
                    ->icon('heroicon-o-finger-print')
                    ->sort(1)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->url(fn (): string => AttendanceDashboard::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.presensi*'))
                    ->childItems([
                        NavigationItem::make('Dashboard Presensi')
                            ->url(fn (): string => AttendanceDashboard::getUrl(panel: 'app', isAbsolute: false)),
                        NavigationItem::make('Laporan Presensi')
                            ->url(fn (): string => AttendanceReport::getUrl(panel: 'app', isAbsolute: false)),
                        NavigationItem::make('Pengajuan Izin')
                            ->url(fn (): string => AttendanceLeaveRequests::getUrl(panel: 'app', isAbsolute: false)),
                        NavigationItem::make('Pengaturan Presensi')
                            ->url(fn (): string => AttendanceSettings::getUrl(panel: 'app', isAbsolute: false)),
                    ]),
                NavigationItem::make('Presensi Saya')
                    ->extraAttributes(['class' => 'app-nav-has-children'])
                    ->group('HOMES')
                    ->icon('heroicon-o-finger-print')
                    ->sort(1)
                    ->visible(fn (): bool => Employee::query()
                        ->where('user_id', auth()->id())
                        ->where('is_active', true)
                        ->whereNotNull('school_id')
                        ->exists())
                    ->url(fn (): string => MyAttendance::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.presensi-saya*'))
                    ->childItems([
                        NavigationItem::make('Dashboard Presensi')
                            ->url(fn (): string => MyAttendance::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.presensi-saya')),
                        NavigationItem::make('Pengajuan Izin')
                            ->url(fn (): string => MyAttendanceLeaveRequests::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.presensi-saya.pengajuan-izin')),
                    ]),
                NavigationItem::make('Asal Madrasah')
                    ->group('HOMES')
                    ->icon('heroicon-o-building-office-2')
                    ->sort(2)
                    ->visible(fn (): bool => ! (auth()->user()?->hasAnyRole([
                        User::ROLE_ADMIN_SEKOLAH_MADRASAH,
                        User::ROLE_GURU_PEGAWAI,
                    ]) ?? false))
                    ->url(fn (): string => SchoolResource::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.schools.*')),
                NavigationItem::make('SK Yayasan')
                    ->group('HOMES')
                    ->icon('heroicon-o-document-text')
                    ->sort(3)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->url(fn (): string => SchoolDecrees::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.sk-yayasan')),
                NavigationItem::make('Surat Keputusan Saya')
                    ->group('HOMES')
                    ->icon('heroicon-o-document-arrow-down')
                    ->sort(4)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false)
                    ->url(fn (): string => MyDecrees::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.file-sk-saya')),
                NavigationItem::make('Profil')
                    ->group('HOMES')
                    ->icon('heroicon-o-user-circle')
                    ->sort(5)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false)
                    ->url(fn (): string => MyProfile::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.profil-saya')),
                NavigationItem::make('Pengaduan')
                    ->group('HOMES')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sort(6)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI) ?? false)
                    ->url(fn (): string => MyComplaints::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.pengaduan-saya')),
                NavigationItem::make('Administrasi')
                    ->extraAttributes(['class' => 'app-nav-has-children'])
                    ->group('SERVICE')
                    ->icon('heroicon-o-folder-open')
                    ->sort(1)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->isActiveWhen(fn (): bool => request()->routeIs(
                        'filament.app.resources.decree-proposals.*',
                        'filament.app.resources.sipinter-updates.*',
                        'filament.app.resources.employee-mutations.*',
                        'filament.app.resources.employee-activity-requests.*',
                        'filament.app.resources.correspondence-requests.*',
                        'filament.app.resources.proposal-requests.*',
                    ))
                    ->childItems([
                        NavigationItem::make('Usulan SK Baru')
                            ->url(fn (): string => DecreeProposalResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.decree-proposals.*')),
                        NavigationItem::make('Perbaikan SK')
                            ->url(fn (): string => DecreeCorrectionRequestResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.decree-corrections.*')),
                        NavigationItem::make('Update Data Sipinter')
                            ->url(fn (): string => SipinterUpdateResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.sipinter-updates.*')),
                        NavigationItem::make('Usulan Mutasi')
                            ->url(fn (): string => EmployeeMutationResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.employee-mutations.*')),
                        NavigationItem::make('Pengajuan Penonaktifan')
                            ->url(fn (): string => EmployeeActivityRequestResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.employee-activity-requests.*')),
                        NavigationItem::make('Pengajuan Persuratan')
                            ->url(fn (): string => CorrespondenceRequestResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.correspondence-requests.*')),
                        NavigationItem::make('Pengajuan Proposal')
                            ->url(fn (): string => ProposalRequestResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.proposal-requests.*')),
                    ]),
                NavigationItem::make('Data Kepala Madrasah/Sekolah')
                    ->group('SERVICE')
                    ->icon('heroicon-o-academic-cap')
                    ->sort(2)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->url(fn (): string => SchoolHeadResource::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.school-heads.*')),
                NavigationItem::make('Kelembagaan')
                    ->extraAttributes(['class' => 'app-nav-has-children'])
                    ->group('SERVICE')
                    ->icon('heroicon-o-list-bullet')
                    ->sort(2)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->isActiveWhen(fn (): bool => request()->routeIs(
                        'filament.app.resources.employees.*',
                        'filament.app.resources.student-enrollments.*',
                        'filament.app.resources.educator-recaps.*',
                    ))
                    ->childItems([
                        NavigationItem::make('Data Akun Tenaga Pendidik')
                            ->url(fn (): string => EmployeeResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.employees.*')),
                        NavigationItem::make('Data Jumlah Siswa')
                            ->url(fn (): string => StudentEnrollmentResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.student-enrollments.*')),
                        NavigationItem::make('Data Jumlah Tenaga Pendidik')
                            ->url(fn (): string => EducatorRecapResource::getUrl(panel: 'app', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.educator-recaps.*')),
                    ]),
                NavigationItem::make('Pesanan Batik')
                    ->group('SERVICE')
                    ->icon('heroicon-o-shopping-bag')
                    ->sort(3)
                    ->visible(fn (): bool => auth()->user()?->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH) ?? false)
                    ->url(fn (): string => BatikOrderResource::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.resources.batik-orders.*')),
                NavigationItem::make('Pembayaran')
                    ->group('PAYMENT')
                    ->icon('heroicon-o-document-minus')
                    ->sort(1)
                    ->url(fn (): string => auth()->user()?->hasRole(User::ROLE_GURU_PEGAWAI)
                        ? MyPayments::getUrl(panel: 'app', isAbsolute: false)
                        : BillingResource::getUrl(panel: 'app', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs(
                        'filament.app.resources.billings.*',
                        'filament.app.pages.pembayaran-saya',
                    )),
            ])
            ->resources([
                BatikOrderResource::class,
                BillingResource::class,
                CorrespondenceRequestResource::class,
                DecreeProposalResource::class,
                DecreeSubmissionResource::class,
                DecreeCorrectionRequestResource::class,
                EducatorRecapResource::class,
                EmployeeActivityRequestResource::class,
                EmployeeMutationResource::class,
                EmployeeResource::class,
                ProposalRequestResource::class,
                SchoolHeadResource::class,
                SchoolResource::class,
                SipinterUpdateResource::class,
                StudentEnrollmentResource::class,
            ])
            ->pages([
                Dashboard::class,
                AttendanceDashboard::class,
                AttendanceLeaveRequests::class,
                AttendanceReport::class,
                AttendanceSettings::class,
                MyAttendance::class,
                MyAttendanceLeaveRequests::class,
                MyPayments::class,
                MyDecrees::class,
                MyProfile::class,
                MyComplaints::class,
                SchoolDecrees::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RedirectAdminIndukToAdminPanel::class,
            ]);
    }
}
