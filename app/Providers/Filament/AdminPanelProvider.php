<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\ApplicationSettings;
use App\Filament\Admin\Pages\AttendanceDashboard;
use App\Filament\Admin\Pages\AttendanceLeaveRequests;
use App\Filament\Admin\Pages\AttendanceReport;
use App\Filament\Admin\Pages\AttendanceSettings;
use App\Filament\Admin\Pages\AuditLogs;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\DecreeDashboard;
use App\Filament\Admin\Pages\FoundationSkDashboard;
use App\Filament\Admin\Pages\EditPaymentInfo;
use App\Filament\Admin\Pages\FoundationDecrees;
use App\Filament\Admin\Pages\InstitutionProfile;
use App\Filament\Admin\Pages\PaymentPage;
use App\Filament\Auth\Pages\IntegratedLogin;
use App\Filament\Auth\Pages\RequestPasswordReset;
use App\Filament\Auth\Pages\ResetPassword;
use App\Filament\GlobalSearch\NavigationSearchProvider;
use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\ApprovalRequests\ApprovalRequestResource;
use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\Billings\BillingResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\CorrespondenceRequests\CorrespondenceRequestResource;
use App\Filament\Resources\DecreeCorrectionRequests\DecreeCorrectionRequestResource;
use App\Filament\Resources\DecreeProposals\DecreeProposalResource;
use App\Filament\Resources\EducatorRecaps\EducatorRecapResource;
use App\Filament\Resources\EmployeeActivityRequests\EmployeeActivityRequestResource;
use App\Filament\Resources\EmployeeMutations\EmployeeMutationResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Filament\Resources\FoundationAnnualReports\FoundationAnnualReportResource;
use App\Filament\Resources\FoundationWorkPrograms\FoundationWorkProgramResource;
use App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource;
use App\Filament\Resources\LearningModules\LearningModuleResource;
use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use App\Filament\Resources\ProposalRequests\ProposalRequestResource;
use App\Filament\Resources\SchoolHeads\SchoolHeadResource;
use App\Filament\Resources\SchoolProfiles\SchoolProfileResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\SecretariatAgendas\SecretariatAgendaResource;
use App\Filament\Resources\SipinterUpdates\SipinterUpdateResource;
use App\Filament\Resources\StudentEnrollments\StudentEnrollmentResource;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\FoundationSkTemplates\FoundationSkTemplateResource;
use App\Filament\Resources\FoundationSkNumberSettings\FoundationSkNumberSettingResource;
use App\Models\ApplicationSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.admin.components.user-greeting'),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.admin.components.footer'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.shared.panel-polish'),
            )
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('HOMES')
                    ->collapsible(false),
                NavigationGroup::make('SERVICE'),
                NavigationGroup::make('PAYMENT'),
                NavigationGroup::make('INFORMATION'),
                NavigationGroup::make('ABOUT'),
            ])
            ->navigationItems([
                NavigationItem::make('Dashboard SK Yayasan')
                    ->group('SK Yayasan')
                    ->icon('heroicon-o-document-text')
                    ->sort(1)
                    ->url(fn (): string => FoundationSkDashboard::getUrl(panel: 'admin', isAbsolute: false))
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() || (auth()->user()?->can('sk-yayasan.view') ?? false)),
                NavigationItem::make('Template SK')->group('SK Yayasan')->sort(2)
                    ->url(fn (): string => FoundationSkTemplateResource::getUrl('index', panel: 'admin', isAbsolute: false))
                    ->visible(fn (): bool => auth()->user()?->isAdminInduk() || (auth()->user()?->can('sk-yayasan.view') ?? false)),
                NavigationItem::make('SK Yayasan')
                    ->group('HOMES')
                    ->icon('heroicon-o-document-text')
                    ->sort(1)
                    ->url(fn (): string => FoundationDecrees::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.sk-yayasan*'))
                    ->visible(fn (): bool => false)
                    ->childItems([
                        NavigationItem::make('Dashboard SK')
                            ->url(fn (): string => DecreeDashboard::getUrl(panel: 'admin', isAbsolute: false))
                            ->visible(fn (): bool => DecreeDashboard::canAccess())
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.sk-yayasan.dashboard')),
                        NavigationItem::make('Upload / Kelola SK')
                            ->url(fn (): string => FoundationDecrees::getUrl(panel: 'admin', isAbsolute: false).'#kelola-sk'),
                    ]),
                NavigationItem::make('Presensi')
                    ->group('HOMES')
                    ->icon('heroicon-o-finger-print')
                    ->sort(2)
                    ->url(fn (): string => AttendanceDashboard::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.presensi*'))
                    ->childItems([
                        NavigationItem::make('Dashboard Presensi')
                            ->url(fn (): string => AttendanceDashboard::getUrl(panel: 'admin', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.presensi')),
                        NavigationItem::make('Laporan Presensi')
                            ->url(fn (): string => AttendanceReport::getUrl(panel: 'admin', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.presensi.laporan')),
                        NavigationItem::make('Pengajuan Izin')
                            ->url(fn (): string => AttendanceLeaveRequests::getUrl(panel: 'admin', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.presensi.pengajuan-izin')),
                        NavigationItem::make('Pengaturan Presensi')
                            ->url(fn (): string => AttendanceSettings::getUrl(panel: 'admin', isAbsolute: false))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.presensi.pengaturan')),
                    ]),
                $this->plannedNavigationParent(
                    label: 'Master Data',
                    group: 'HOMES',
                    icon: 'heroicon-o-circle-stack',
                    sort: 3,
                    menu: 'master-data',
                    children: [
                        'Admin',
                        'Guru dan Pegawai',
                        'Asal Madrasah',
                    ],
                ),
                NavigationItem::make('Profile Sekolah')
                    ->group('HOMES')
                    ->icon('heroicon-o-building-office-2')
                    ->sort(4)
                    ->url(fn (): string => SchoolProfileResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.resources.school-profiles.*'),
                    ),
                NavigationItem::make('Profile Lembaga')
                    ->group('HOMES')
                    ->icon('heroicon-o-identification')
                    ->sort(5)
                    ->url(fn (): string => InstitutionProfile::getUrl(
                        ['section' => InstitutionProfile::SECTION_IDENTITY],
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.pages.profile-lembaga')
                            || request()->routeIs('filament.admin.resources.foundation-work-programs.*')
                            || request()->routeIs('filament.admin.resources.foundation-annual-reports.*'),
                    )
                    ->childItems([
                        $this->institutionProfileChildItem(
                            'Identitas Lembaga',
                            InstitutionProfile::SECTION_IDENTITY,
                        ),
                        $this->institutionProfileChildItem(
                            'Struktur Pengurus',
                            InstitutionProfile::SECTION_STRUCTURE,
                        ),
                        NavigationItem::make('Program Kerja')
                            ->url(fn (): string => FoundationWorkProgramResource::getUrl(
                                panel: 'admin',
                                isAbsolute: false,
                            ))
                            ->isActiveWhen(
                                fn (): bool => request()->routeIs(
                                    'filament.admin.resources.foundation-work-programs.*',
                                ),
                            ),
                        NavigationItem::make('Laporan Tahunan')
                            ->url(fn (): string => FoundationAnnualReportResource::getUrl(
                                panel: 'admin',
                                isAbsolute: false,
                            ))
                            ->isActiveWhen(
                                fn (): bool => request()->routeIs(
                                    'filament.admin.resources.foundation-annual-reports.*',
                                ),
                            ),
                    ]),
                $this->plannedNavigationParent(
                    label: 'Administrasi',
                    group: 'SERVICE',
                    icon: 'heroicon-o-clipboard-document-list',
                    sort: 1,
                    menu: 'administrasi',
                    children: [
                        'Usulan SK Baru',
                        'Perbaikan SK',
                        'Update Data Sipinter',
                        'Usulan Mutasi',
                        'Pengajuan Penonaktifan',
                        'Pengajuan Persuratan',
                        'Pengajuan Proposal',
                        'Approval',
                    ],
                ),
                NavigationItem::make('Data Kepala Madrasah/Sekolah')
                    ->group('SERVICE')
                    ->icon('heroicon-o-academic-cap')
                    ->sort(2)
                    ->url(fn (): string => SchoolHeadResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.school-heads.*')),
                NavigationItem::make('Pengaduan Privat')
                    ->group('SERVICE')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->sort(3)
                    ->url(fn (): string => ComplaintResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.complaints.*')),
                $this->plannedNavigationParent(
                    label: 'Kelembagaan',
                    group: 'SERVICE',
                    icon: 'heroicon-o-building-office-2',
                    sort: 2,
                    menu: 'kelembagaan',
                    children: [
                        'Data Jumlah Siswa',
                        'Data Jumlah Tenaga Pendidik',
                    ],
                ),
                NavigationItem::make('Bendahara')
                    ->group('SERVICE')
                    ->icon('heroicon-o-banknotes')
                    ->sort(3)
                    ->url(fn (): string => TreasuryTransactionResource::getUrl(
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs(
                            'filament.admin.resources.treasury-transactions.*',
                        ),
                    ),
                NavigationItem::make('Agenda Kesekretariatan')
                    ->group('SERVICE')
                    ->icon('heroicon-o-calendar-days')
                    ->sort(4)
                    ->url(fn (): string => SecretariatAgendaResource::getUrl(
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs(
                            'filament.admin.resources.secretariat-agendas.*',
                        ),
                    ),
                NavigationItem::make('Pesanan Batik')
                    ->group('SERVICE')
                    ->icon('heroicon-o-shopping-bag')
                    ->sort(5)
                    ->url(fn (): string => BatikOrderResource::getUrl(
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.resources.batik-orders.*')
                            || request()->routeIs('filament.admin.resources.batik-products.*'),
                    ),
                NavigationItem::make('Modul')
                    ->group('SERVICE')
                    ->icon('heroicon-o-book-open')
                    ->sort(6)
                    ->url(fn (): string => LearningModuleResource::getUrl(
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs(
                            'filament.admin.resources.learning-modules.*',
                        ),
                    ),
                NavigationItem::make('Pembayaran')
                    ->group('PAYMENT')
                    ->icon('heroicon-o-credit-card')
                    ->sort(1)
                    ->url(fn (): string => PaymentPage::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.pages.pembayaran'),
                    ),
                NavigationItem::make('Edit Info Pembayaran')
                    ->group('PAYMENT')
                    ->icon('heroicon-o-pencil-square')
                    ->sort(2)
                    ->url(fn (): string => EditPaymentInfo::getUrl(
                        panel: 'admin',
                        isAbsolute: false,
                    ))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs(
                            'filament.admin.pages.edit-info-pembayaran',
                        ),
                    ),
                NavigationItem::make('Invoice')
                    ->group('INFORMATION')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->sort(1)
                    ->url(fn (): string => InvoiceSchoolResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.resources.invoices.*'),
                    ),
                $this->plannedNavigationItem('Tunggakan', 'INFORMATION', 'heroicon-o-document-minus', 2),
                NavigationItem::make('Laporan Keuangan')
                    ->group('INFORMATION')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->sort(3)
                    ->url(fn (): string => FinancialReportResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.resources.financial-reports.*'),
                    ),
                NavigationItem::make('Audit Log')
                    ->group('INFORMATION')
                    ->icon('heroicon-o-shield-check')
                    ->sort(4)
                    ->url(fn (): string => ActivityResource::getUrl(panel: 'admin', isAbsolute: false))
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs('filament.admin.resources.activities.*'),
                    ),
                $this->plannedNavigationParent(
                    label: 'Setting',
                    group: 'ABOUT',
                    icon: 'heroicon-o-cog-6-tooth',
                    sort: 1,
                    menu: 'setting',
                    children: [
                        'Aplikasi',
                        'Tahun Ajaran',
                        'Pembayaran',
                        'Jenis Pembayaran',
                    ],
                ),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                DecreeDashboard::class,
                FoundationSkDashboard::class,
                ApplicationSettings::class,
                AttendanceDashboard::class,
                AttendanceLeaveRequests::class,
                AttendanceReport::class,
                AttendanceSettings::class,
                AuditLogs::class,
                Dashboard::class,
                EditPaymentInfo::class,
                FoundationDecrees::class,
                InstitutionProfile::class,
                PaymentPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ]);
    }

    /**
     * @param  array<int, string>  $children
     */
    private function plannedNavigationParent(
        string $label,
        string $group,
        string $icon,
        int $sort,
        string $menu,
        array $children,
    ): NavigationItem {
        return NavigationItem::make($label)
            ->group($group)
            ->icon($icon)
            ->sort($sort)
            ->url(in_array($menu, ['administrasi', 'kelembagaan'], true)
                ? null
                : fn (): string => Dashboard::getUrl(['menu' => $menu], isAbsolute: false))
            ->isActiveWhen(fn (): bool => (
                request()->routeIs('filament.admin.pages.dashboard')
                && request()->query('menu', 'administrasi') === $menu
            ) || (
                $menu === 'administrasi'
                && request()->routeIs(
                    'filament.admin.resources.approval-requests.*',
                    'filament.admin.resources.correspondence-requests.*',
                    'filament.admin.resources.decree-proposals.*',
                    'filament.admin.resources.decree-submissions.*',
                    'filament.admin.resources.decree-corrections.*',
                    'filament.admin.resources.employee-mutations.*',
                    'filament.admin.resources.proposal-requests.*',
                    'filament.admin.resources.employee-activity-requests.*',
                    'filament.admin.resources.sipinter-updates.*',
                )
            ) || (
                $menu === 'master-data'
                && request()->routeIs(
                    'filament.admin.resources.employees.*',
                    'filament.admin.resources.school-origins.*',
                    'filament.admin.resources.schools.*',
                    'filament.admin.resources.users.*',
                )
            ) || (
                $menu === 'kelembagaan'
                && request()->routeIs(
                    'filament.admin.resources.employees.*',
                    'filament.admin.resources.educator-recaps.*',
                    'filament.admin.resources.student-enrollments.*',
                )
            ) || (
                $menu === 'setting'
                && request()->routeIs(
                    'filament.admin.pages.application-settings',
                    'filament.admin.resources.academic-years.*',
                    'filament.admin.resources.billings.*',
                    'filament.admin.resources.payment-types.*',
                )
            ))
            ->childItems(array_map(
                fn (string $child): NavigationItem => $this->plannedChildNavigationItem($child),
                $children,
            ));
    }

    private function plannedChildNavigationItem(string $label): NavigationItem
    {
        if ($label === 'Aplikasi') {
            return NavigationItem::make($label)
                ->url(fn (): string => ApplicationSettings::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.pages.application-settings'),
                );
        }

        if ($label === 'Tahun Ajaran') {
            return NavigationItem::make($label)
                ->url(fn (): string => AcademicYearResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.academic-years.*'),
                );
        }

        if ($label === 'Pembayaran') {
            return NavigationItem::make($label)
                ->url(fn (): string => BillingResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.billings.*'),
                );
        }

        if ($label === 'Jenis Pembayaran') {
            return NavigationItem::make($label)
                ->url(fn (): string => PaymentTypeResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.payment-types.*'),
                );
        }

        if ($label === 'Approval') {
            return NavigationItem::make($label)
                ->url(fn (): string => ApprovalRequestResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.approval-requests.*'),
                );
        }

        if ($label === 'Usulan SK Baru') {
            return NavigationItem::make($label)
                ->url(fn (): string => DecreeProposalResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.decree-proposals.*'),
                );
        }

        if ($label === 'Perbaikan SK') {
            return NavigationItem::make($label)
                ->badge(fn (): string => DecreeCorrectionRequestResource::getNavigationBadge() ?? '0')
                ->url(fn (): string => DecreeCorrectionRequestResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.decree-corrections.*'));
        }

        if ($label === 'Update Data Sipinter') {
            return NavigationItem::make($label)
                ->url(fn (): string => SipinterUpdateResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.sipinter-updates.*'),
                );
        }

        if ($label === 'Usulan Mutasi') {
            return NavigationItem::make($label)
                ->url(fn (): string => EmployeeMutationResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.employee-mutations.*'),
                );
        }

        if ($label === 'Pengajuan Penonaktifan') {
            return NavigationItem::make($label)
                ->url(fn (): string => EmployeeActivityRequestResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.employee-activity-requests.*'),
                );
        }

        if ($label === 'Pengajuan Persuratan') {
            return NavigationItem::make($label)
                ->url(fn (): string => CorrespondenceRequestResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.correspondence-requests.*'),
                );
        }

        if ($label === 'Pengajuan Proposal') {
            return NavigationItem::make($label)
                ->url(fn (): string => ProposalRequestResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.proposal-requests.*'),
                );
        }

        if ($label === 'Admin') {
            return NavigationItem::make($label)
                ->url(fn (): string => UserResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.users.*'));
        }

        if ($label === 'Asal Madrasah') {
            return NavigationItem::make($label)
                ->url(fn (): string => SchoolResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.schools.*'),
                );
        }

        if ($label === 'Guru dan Pegawai') {
            return NavigationItem::make($label)
                ->url(fn (): string => EmployeeResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.employees.*'));
        }

        if ($label === 'Data Jumlah Tenaga Pendidik') {
            return NavigationItem::make($label)
                ->url(fn (): string => EducatorRecapResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.educator-recaps.*'),
                );
        }

        if ($label === 'Data Jumlah Siswa') {
            return NavigationItem::make($label)
                ->url(fn (): string => StudentEnrollmentResource::getUrl(panel: 'admin', isAbsolute: false))
                ->isActiveWhen(
                    fn (): bool => request()->routeIs('filament.admin.resources.student-enrollments.*'),
                );
        }

        return NavigationItem::make($label)
            ->extraAttributes([
                'aria-disabled' => 'true',
                'tabindex' => '-1',
                'title' => "Fitur {$label} belum tersedia",
            ]);
    }

    private function institutionProfileChildItem(string $label, string $section): NavigationItem
    {
        return NavigationItem::make($label)
            ->url(fn (): string => InstitutionProfile::getUrl(
                ['section' => $section],
                panel: 'admin',
                isAbsolute: false,
            ))
            ->isActiveWhen(fn (): bool => (
                request()->routeIs('filament.admin.pages.profile-lembaga')
                && request()->query('section', InstitutionProfile::SECTION_IDENTITY) === $section
            ));
    }

    private function plannedNavigationItem(string $label, string $group, string $icon, int $sort): NavigationItem
    {
        return NavigationItem::make($label)
            ->group($group)
            ->icon($icon)
            ->sort($sort)
            ->url('#')
            ->extraAttributes([
                'aria-disabled' => 'true',
                'tabindex' => '-1',
                'title' => "Modul {$label} belum tersedia",
                'x-on:click.prevent' => '',
            ]);
    }
}
