<!DOCTYPE html>
<html lang="id" class="h-full antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaxCore PPh 21 Enterprise — Sistem Payroll & Kalkulator Pajak PP 58/2023</title>
    <link rel="stylesheet" href="{{ asset('vendor/reference/fontawesome/css/all.min.css') }}">
    <script src="{{ asset('vendor/reference/xlsx.full.min.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/html-workspace.css', 'resources/js/html-workspace.js'])
</head>
<body class="h-full overflow-hidden flex bg-slate-50 text-slate-900">

    <form id="workspace-logout" action="{{ route('logout') }}" method="post" hidden>@csrf</form>
    <div id="mobileSidebarBackdrop" onclick="toggleMobileSidebar(false)" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-30 hidden lg:hidden"></div>
    <aside id="leftModuleSidebar" class="fixed lg:static inset-y-0 left-0 -translate-x-full lg:translate-x-0 h-full bg-slate-950 border-r border-slate-800 flex flex-col shrink-0 transition-all duration-200 w-[276px] z-40 select-none no-print">
        <div id="sidebarExpandedContent" class="flex flex-col h-full justify-between">
            <!-- Brand Header -->
            <div class="h-16 px-4 border-b border-slate-800/80 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-cyan-400 via-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-lg shadow-cyan-500/25 ring-1 ring-cyan-300/40 shrink-0">
                        <i class="fa-solid fa-microchip text-sm"></i>
                    </div>
                    <div class="truncate">
                        <div class="flex items-center gap-1.5">
                            <span class="font-extrabold text-sm tracking-tight text-white">TAXCORE</span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold uppercase rounded bg-cyan-500/15 text-cyan-300 border border-cyan-400/30">QUANTUM 21</span>
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium truncate">Coretax DJP • PP 58/2023 Engine</p>
                    </div>
                </div>
                <button type="button" onclick="toggleLeftSidebar(true)" class="hidden lg:flex w-7 h-7 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 items-center justify-center cursor-pointer border border-slate-700" title="Ciutkan Sidebar">
                    <i class="fa-solid fa-angles-left text-[11px]"></i>
                </button>
            </div>

            <!-- Active User Bar -->
            <div class="px-3.5 py-2.5 bg-slate-900/80 border-b border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div id="sidebarUserInitial" class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-400 text-slate-950 font-extrabold text-xs flex items-center justify-center shrink-0 shadow-sm shadow-cyan-500/30">S</div>
                    <div class="truncate">
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <p id="sidebarUserName" class="text-xs font-bold text-slate-100 truncate">Super Administrator</p>
                        </div>
                        <p id="sidebarUserRole" class="text-[10px] font-mono text-cyan-400 truncate">SUPER_ADMIN // VERIFIED</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="switchWorkspaceMode('access')" title="Pengaturan Manajemen Akses" class="w-7 h-7 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-cyan-300 flex items-center justify-center cursor-pointer">
                        <i class="fa-solid fa-user-gear text-xs"></i>
                    </button>
                    <button type="button" onclick="window.logoutSession()" title="Keluar / Kunci Sesi" class="w-7 h-7 rounded-lg hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 flex items-center justify-center cursor-pointer">
                        <i class="fa-solid fa-power-off text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Navigation List -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
                <!-- GROUP 1: MANAJEMEN PAYROLL -->
                <div>
                    <div class="px-2.5 mb-1.5 flex items-center justify-between">
                        <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500">01 // Command Center</span>
                    </div>
                    <button type="button" id="topModeBtn_payroll" onclick="switchWorkspaceMode('payroll'); toggleMobileSidebar(false)" class="nav-item active w-full px-3 py-2.5 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                        <span class="flex items-center gap-2.5 truncate">
                            <i class="fa-solid fa-users-viewfinder w-4 text-center text-cyan-400"></i>
                            <span>Daftar Gaji & PPh 21</span>
                        </span>
                        <span id="employeeCountBadgeTop" class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-md bg-cyan-500/20 text-cyan-300 border border-cyan-400/30">6</span>
                    </button>
                </div>

                <!-- GROUP 2: SIMULATOR & KALKULATOR -->
                <div>
                    <button type="button" onclick="toggleSimulatorDropdownMenu()" class="w-full px-2.5 mb-1.5 flex items-center justify-between text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500 hover:text-cyan-300 cursor-pointer">
                        <span>02 // Modul Simulator Pajak</span>
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </button>
                    <div id="simulatorSubMenuContainer" class="space-y-1">
                        <button type="button" id="accHeader_pegawai_bulanan" onclick="switchWorkspaceMode('simulator', true); switchSubject('pegawai_bulanan'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-calendar-check w-4 text-center text-cyan-400/80"></i><span>Pegawai Tetap (Bulanan)</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-slate-800 text-cyan-300 border border-slate-700">TER</span>
                        </button>
                        <button type="button" id="accHeader_pegawai_tahunan" onclick="switchWorkspaceMode('simulator', true); switchSubject('pegawai_tahunan'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-file-invoice w-4 text-center text-indigo-400"></i><span>Desember / Resign (A1)</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">Psl 17</span>
                        </button>
                        <button type="button" id="accHeader_pensiunan" onclick="switchWorkspaceMode('simulator', true); switchSubject('pensiunan'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-person-cane w-4 text-center text-teal-400"></i><span>Pensiunan Berkala</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-teal-500/20 text-teal-300 border border-teal-400/30">TER</span>
                        </button>
                        <button type="button" id="accHeader_tidak_tetap" onclick="switchWorkspaceMode('simulator', true); switchSubject('tidak_tetap'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-helmet-safety w-4 text-center text-amber-400"></i><span>Pegawai Tidak Tetap</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-amber-500/20 text-amber-300 border border-amber-400/30">Harian</span>
                        </button>
                        <button type="button" id="accHeader_bukan_pegawai" onclick="switchWorkspaceMode('simulator', true); switchSubject('bukan_pegawai'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-user-doctor w-4 text-center text-purple-400"></i><span>Bukan Pegawai / Ahli</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-purple-500/20 text-purple-300 border border-purple-400/30">50%</span>
                        </button>
                        <button type="button" id="accHeader_pesangon_final" onclick="switchWorkspaceMode('simulator', true); switchSubject('pesangon_final'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-sack-dollar w-4 text-center text-rose-400"></i><span>Pesangon / JHT Final</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-rose-500/20 text-rose-300 border border-rose-400/30">Final</span>
                        </button>
                    </div>
                </div>

                <!-- GROUP 3: PENGATURAN & REFERENSI -->
                <div>
                    <div class="px-2.5 mb-1.5">
                        <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500">03 // Sistem & Referensi</span>
                    </div>
                    <div class="space-y-1">
                        <button type="button" onclick="openTerModal()" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-table-list w-4 text-center text-amber-400"></i><span>Tabel TER A/B/C & Psl 17</span></span>
                            <span class="px-1.5 py-0.5 text-[9px] font-mono font-bold rounded bg-amber-500/20 text-amber-300 border border-amber-400/30">PP 58</span>
                        </button>
                        <button type="button" id="topModeBtn_company" onclick="switchWorkspaceMode('company'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-building-shield w-4 text-center text-indigo-400"></i><span>Database Perusahaan (TKU)</span></span>
                        </button>
                        @can('access.manage')<button type="button" id="topModeBtn_access" onclick="switchWorkspaceMode('access'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center justify-between cursor-pointer">
                            <span class="flex items-center gap-2.5 truncate"><i class="fa-solid fa-user-shield w-4 text-center text-emerald-400"></i><span>Manajemen Akses</span></span>
                        </button>@endcan
@if(auth()->user()->role === 'SUPER_ADMIN')<button type="button" id="topModeBtn_audit" onclick="switchWorkspaceMode('audit'); toggleMobileSidebar(false)" class="nav-item w-full px-3 py-2 rounded-xl text-left text-xs flex items-center gap-2.5 cursor-pointer"><i class="fa-solid fa-clock-rotate-left w-4 text-center text-cyan-400"></i><span>Log Audit</span></button>@endif
                    </div>
                </div>
            </div>

            <!-- Sidebar Footer -->
            <div class="p-3.5 border-t border-slate-800/80 bg-slate-950/60 space-y-2 shrink-0">
                <button type="button" onclick="exportPayrollCsv()" class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white text-xs font-bold flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/50 transition cursor-pointer">
                    <i class="fa-solid fa-file-excel"></i><span>Export Excel Coretax</span>
                </button>
            </div>
        </div>

        <!-- Collapsed Slim Rail -->
        <div id="sidebarCollapsedRail" class="hidden flex-col items-center justify-between h-full py-3 px-2">
            <div class="flex flex-col items-center gap-3">
                <button type="button" onclick="toggleLeftSidebar(false)" class="w-9 h-9 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white flex items-center justify-center cursor-pointer"><i class="fa-solid fa-angles-right text-xs"></i></button>
                <button type="button" onclick="switchWorkspaceMode('payroll')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-400 flex items-center justify-center cursor-pointer" title="Daftar Gaji & PPh 21"><i class="fa-solid fa-users-viewfinder text-xs"></i></button>
                <button type="button" onclick="switchWorkspaceMode('simulator')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-400 flex items-center justify-center cursor-pointer" title="Simulator"><i class="fa-solid fa-calculator text-xs"></i></button>
                <button type="button" onclick="switchWorkspaceMode('company')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center cursor-pointer" title="Database Perusahaan"><i class="fa-solid fa-building-shield text-xs"></i></button>
                <button type="button" onclick="switchWorkspaceMode('access')" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 flex items-center justify-center cursor-pointer" title="Manajemen Akses"><i class="fa-solid fa-user-shield text-xs"></i></button>
            </div>
        </div>
    </aside>

    <!-- ========================================================================== -->
    <!-- MAIN WORKSPACE AREA                                                        -->
    <!-- ========================================================================== -->
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <!-- Top Header -->
        <header class="h-16 bg-white/85 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between gap-3 shrink-0 no-print">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" onclick="toggleMobileSidebar(true)" class="lg:hidden w-9 h-9 rounded-xl bg-slate-900 text-cyan-400 flex items-center justify-center cursor-pointer">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="min-w-0">
                    <div class="flex items-center gap-2.5">
                        <h1 id="topHeaderTitle" class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight truncate">
                            Master Data Pegawai & Perhitungan PPh 21 Kolektif
                        </h1>
                        <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-gradient-to-r from-blue-600/10 to-cyan-500/10 text-blue-700 border border-blue-300/70">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping"></span>
                            PP 58/2023 • PMK 168
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate">Quantum Tax Telemetry • TER Bulanan, Rekonsiliasi 1721-A1 & Integrasi Coretax DJP</p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-[1440px] mx-auto space-y-5">

                <!-- ============================================================== -->
                <!-- WORKSPACE 1: MASTER DATA PEGAWAI & PAYROLL KOLEKTIF            -->
                <!-- ============================================================== -->
                <section id="workspace_payroll" class="space-y-5">
                    <!-- Responsive Top Filter Bar -->
                    <div class="futuristic-card rounded-2xl p-3.5 sm:p-4 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3.5">
                        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2.5">
                            <!-- Pemotong (NPWP / ID TKU) -->
                            <div class="flex items-center gap-2 bg-indigo-50/60 border border-indigo-200/80 rounded-xl px-3 py-1.5">
                                <i class="fa-solid fa-building text-indigo-600 text-xs"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-indigo-600">Pemotong (NPWP / ID TKU)</span>
                                    <select id="activeCompanySelect" onchange="selectActiveCompany(this.value)" class="bg-transparent text-xs font-bold text-slate-900 outline-none cursor-pointer truncate max-w-[220px] sm:max-w-[260px]">
                                        
                                    </select>
                                </div>
                                <button type="button" onclick="switchWorkspaceMode('company')" title="Kelola Database Perusahaan" class="w-6 h-6 rounded-lg hover:bg-indigo-100 text-indigo-600 flex items-center justify-center cursor-pointer">
                                    <i class="fa-solid fa-gear text-xs"></i>
                                </button>
                            </div>

                            <!-- Filter Masa & Tahun Pajak -->
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/90 rounded-xl px-3 py-1.5">
                                <i class="fa-regular fa-calendar text-blue-600 text-xs"></i>
                                <div>
                                    <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400">Filter Masa & Tahun Pajak</span>
                                    <div class="flex items-center gap-1.5">
                                        <select id="payrollPeriodMonth" onchange="onPayrollPeriodChange()" class="bg-transparent text-xs font-bold text-slate-900 outline-none cursor-pointer">
                                            <option value="ALL">Semua Bulan</option>
                                            <option value="1">01 - Januari</option>
                                            <option value="2">02 - Februari</option>
                                            <option value="3">03 - Maret</option>
                                            <option value="4">04 - April</option>
                                            <option value="5">05 - Mei</option>
                                            <option value="6">06 - Juni</option>
                                            <option value="7">07 - Juli</option>
                                            <option value="8">08 - Agustus</option>
                                            <option value="9">09 - September</option>
                                            <option value="10">10 - Oktober</option>
                                            <option value="11" selected>11 - November</option>
                                            <option value="12">12 - Desember (A1)</option>
                                        </select>
                                        <span class="text-slate-300">|</span>
                                        <select id="payrollPeriodYear" onchange="onPayrollPeriodChange()" class="bg-transparent text-xs font-bold text-blue-700 outline-none cursor-pointer">
                                            <option value="2020">2020</option>
                                            <option value="2021">2021</option>
                                            <option value="2022">2022</option>
                                            <option value="2023">2023</option>
                                            <option value="2024">2024</option>
                                            <option value="2025">2025</option>
                                            <option value="2026" selected>2026</option>
                                            <option value="2027">2027</option>
                                            <option value="2028">2028</option>
                                            <option value="2029">2029</option>
                                            <option value="2030">2030</option>
                                            <option value="2031">2031</option>
                                            <option value="2032">2032</option>
                                            <option value="2033">2033</option>
                                            <option value="2034">2034</option>
                                            <option value="2035">2035</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="copyPreviousMonthEmployees()" class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="fa-regular fa-copy text-blue-600"></i><span>Salin Bulan Lalu</span>
                            </button>
                        </div>

                        <!-- Mode Masa Pajak Switcher -->
                        <div class="grid grid-cols-2 bg-slate-100 p-1 rounded-xl border border-slate-200/80 shrink-0">
                            <button type="button" id="periodModeBtn_BULANAN" onclick="setPayrollPeriodMode('BULANAN')" class="py-2 px-2.5 rounded-lg text-xs font-bold transition cursor-pointer bg-white text-slate-900 shadow-2xs flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-calendar-day text-blue-600"></i><span>Masa Bulanan (TER)</span>
                            </button>
                            <button type="button" id="periodModeBtn_TAHUNAN" onclick="setPayrollPeriodMode('TAHUNAN')" class="py-2 px-2.5 rounded-lg text-xs font-semibold transition cursor-pointer text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-scale-balanced text-indigo-600"></i><span>Masa Terakhir (Desember / 1721-A1)</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4 KPI Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="kpi-futuristic rounded-2xl p-4 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Total Subjek Pajak</span>
                                <p id="payrollKpiCount" class="text-xl font-extrabold text-slate-900 font-mono mt-1">6 Pegawai</p>
                                <span id="payrollKpiPeriodSub" class="inline-block text-[11px] text-slate-500 mt-1">Masa 11/2026</span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white flex items-center justify-center text-sm shadow-md shadow-blue-500/20 shrink-0"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="kpi-futuristic rounded-2xl p-4 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Total Penghasilan Bruto</span>
                                <p id="payrollKpiBruto" class="text-xl font-extrabold text-slate-900 font-mono mt-1">Rp 255.149.459</p>
                                <span class="inline-block text-[11px] text-slate-500 mt-1">Gaji + Tunjangan + Premi BPJS</span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center text-sm shadow-md shadow-indigo-500/20 shrink-0"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                        </div>
                        <div class="kpi-futuristic kpi-amber rounded-2xl p-4 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-amber-700">Total PPh 21 Terutang</span>
                                <p id="payrollKpiTax" class="text-xl font-extrabold text-amber-700 font-mono mt-1">Rp 42.337.131</p>
                                <span class="inline-block text-[11px] text-slate-500 mt-1">Wajib disetor ke Kas Negara</span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center text-sm shadow-md shadow-amber-500/20 shrink-0"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        </div>
                        <div class="kpi-futuristic kpi-emerald rounded-2xl p-4 flex items-start justify-between">
                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-emerald-700">Total Take Home Pay</span>
                                <p id="payrollKpiThp" class="text-xl font-extrabold text-emerald-700 font-mono mt-1">Rp 226.403.674</p>
                                <span class="inline-block text-[11px] text-slate-500 mt-1">Penghasilan bersih diterima</span>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-sm shadow-md shadow-emerald-500/20 shrink-0"><i class="fa-solid fa-wallet"></i></div>
                        </div>
                    </div>

                    <!-- Payroll Table Card -->
                    <div class="futuristic-card rounded-2xl overflow-hidden">
                        <div class="p-4 border-b border-slate-200/80 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
                            <div class="relative flex-1 max-w-md">
                                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                                <input type="text" id="empSearchInput" oninput="renderEmployeePayrollTable()" placeholder="Cari nama pegawai, NIK, jabatan, atau PTKP..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white focus:border-blue-600 outline-none">
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="openAddEmployeeModal()" class="btn-cyber-primary px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-plus"></i><span>Tambah Pegawai</span>
                                </button>
                                <button type="button" onclick="pullFromMonthlyEmployees()" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 text-xs font-semibold flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Tarik & Integrasikan Data dari Modul Pegawai Tetap Bulanan ke Masa Desember (1721-A1)">
                                    <i class="fa-solid fa-arrows-rotate"></i><span>Tarik Data Pegawai Bulanan</span>
                                </button>
                                <button type="button" id="inlineSpreadsheetToggleBtn" onclick="toggleInlineSpreadsheetMode()" class="px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-table-cells text-blue-600"></i><span>Mode Spreadsheet</span>
                                </button>
                                <button type="button" onclick="openBulkGeneratorModal()" class="px-3 py-2 rounded-xl border border-amber-300 bg-amber-50/70 hover:bg-amber-100 text-amber-900 text-xs font-semibold flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-wand-magic-sparkles text-amber-600"></i><span>Aksi Massal (1.000+ Data)</span>
                                </button>
                                <button type="button" onclick="downloadImportTemplateExcel()" class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-regular fa-file-excel text-emerald-600"></i><span>Template</span>
                                </button>
                                <button type="button" onclick="openImportModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-file-import text-blue-600"></i><span>Import</span>
                                </button>
                                <button type="button" onclick="exportCoretaxXml()" class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-file-code"></i><span>XML Coretax</span>
                                </button>
                                <button type="button" onclick="exportPayrollCsv()" class="px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-file-excel"></i><span>Excel Coretax</span>
                                </button>
                                <button type="button" onclick="resetSampleEmployees()" class="p-2 rounded-xl bg-white hover:bg-slate-100 text-slate-500 border border-slate-200 text-xs cursor-pointer" title="Reset Contoh">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs whitespace-nowrap border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/90 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="py-3 px-4 text-left sticky-col-left bg-slate-50">Nama Pegawai & Jabatan</th>
                                        <th class="py-3 px-3 text-center">Masa & Tahun</th>
                                        <th class="py-3 px-3 text-center">PTKP & Skema</th>
                                        <th class="py-3 px-3.5 text-right">Gaji Pokok</th>
                                        <th class="py-3 px-3.5 text-right">Tunjangan</th>
                                        <th class="py-3 px-3.5 text-right">Bonus</th>
                                        <th class="py-3 px-3.5 text-right">THR</th>
                                        <th class="py-3 px-3.5 text-right">Kompensasi</th>
                                        <th class="py-3 px-3.5 text-right">Premi BPJS</th>
                                        <th class="py-3 px-3.5 text-right bg-slate-100/70 text-slate-700">Bruto Pajak</th>
                                        <th class="py-3 px-3 text-center">Tarif / DPP</th>
                                        <th class="py-3 px-3.5 text-right text-amber-800 bg-amber-50/40">PPh 21</th>
                                        <th class="py-3 px-3.5 text-right text-emerald-800 bg-emerald-50/40">Take Home Pay</th>
                                        <th class="py-3 px-4 text-center sticky-col-right bg-slate-50">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="employeeTableBody" class="divide-y divide-slate-200/70 text-slate-700"></tbody>
                                <tfoot id="employeeTableFoot" class="bg-slate-900 text-white font-mono font-semibold text-xs"></tfoot>
                            </table>
                        </div>

                        <!-- Pagination Bar -->
                        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200/80 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span id="payrollPaginationInfo" class="text-slate-500 font-medium">Menampilkan data</span>
                                <select onchange="changePayrollPageSize(this.value)" class="px-2 py-1 rounded border border-slate-300 bg-white text-xs font-semibold">
                                    <option value="25">25 / hal</option>
                                    <option value="50" selected>50 / hal</option>
                                    <option value="100">100 / hal</option>
                                    <option value="250">250 / hal</option>
                                    <option value="500">500 / hal</option>
                                </select>
                            </div>
                            <div id="payrollPaginationButtons" class="flex items-center gap-1"></div>
                        </div>
                    </div>
                </section>

                <!-- ============================================================== -->
                <!-- WORKSPACE 2: SIMULATOR DETAIL & DAFTAR DATA PER MODUL          -->
                <!-- ============================================================== -->
                <section id="workspace_simulator" class="hidden space-y-5">
                    <!-- Simulator Period Bar -->
                    <div class="futuristic-card rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span id="simActiveModuleBadge" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-sm text-xs font-extrabold">
                                Pegawai Tetap Bulanan (TER)
                            </span>
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200/90 rounded-xl px-3 py-1.5">
                                <i class="fa-regular fa-calendar-check text-blue-600 text-xs"></i>
                                <span class="text-xs font-bold text-slate-700">Masa & Tahun Pajak:</span>
                                <select id="simPeriodMonth" onchange="onSimPeriodChange()" class="bg-transparent text-xs font-bold text-slate-900 outline-none cursor-pointer">
                                    <option value="ALL">Semua Bulan</option>
                                    <option value="1">01 - Januari</option><option value="2">02 - Februari</option><option value="3">03 - Maret</option>
                                    <option value="4">04 - April</option><option value="5">05 - Mei</option><option value="6">06 - Juni</option>
                                    <option value="7">07 - Juli</option><option value="8">08 - Agustus</option><option value="9">09 - September</option>
                                    <option value="10">10 - Oktober</option><option value="11" selected>11 - November</option><option value="12">12 - Desember (A1)</option>
                                </select>
                                <span class="text-slate-300">|</span>
                                <select id="simPeriodYear" onchange="onSimPeriodChange()" class="bg-transparent text-xs font-bold text-blue-700 outline-none cursor-pointer">
                                    <option value="2020">2020</option><option value="2021">2021</option><option value="2022">2022</option><option value="2023">2023</option>
                                    <option value="2024">2024</option><option value="2025">2025</option><option value="2026" selected>2026</option><option value="2027">2027</option>
                                    <option value="2028">2028</option><option value="2029">2029</option><option value="2030">2030</option><option value="2031">2031</option>
                                    <option value="2032">2032</option><option value="2033">2033</option><option value="2034">2034</option><option value="2035">2035</option>
                                </select>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 hover:bg-slate-100">
                            <input type="checkbox" id="simSavedShowAllModules" onchange="renderSimulatorSavedList()">
                            <span>Tampilkan Semua Kategori Modul</span>
                        </label>
                    </div>

                    <!-- Full-Width Summary & Saved List -->
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                            <div class="kpi-futuristic p-4 rounded-2xl"><p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Total Subjek Modul Ini</p><p id="kpiTarif" class="text-base font-extrabold text-blue-700 font-mono mt-1">0 Pegawai</p><p id="subKpiTarif" class="text-[11px] text-slate-500 mt-0.5">Sesuai filter periode aktif</p></div>
                            <div class="kpi-futuristic p-4 rounded-2xl"><p class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400">Total Penghasilan Bruto</p><p id="kpiBruto" class="text-base font-extrabold font-mono mt-1">Rp 0</p><p id="subKpiBruto" class="text-[11px] text-slate-500 mt-0.5">Akumulasi bruto modul</p></div>
                            <div class="kpi-futuristic kpi-amber p-4 rounded-2xl"><p class="text-[10px] font-mono font-bold uppercase tracking-widest text-amber-700">Total PPh 21 Terutang</p><p id="kpiTax" class="text-base font-extrabold text-amber-700 font-mono mt-1">Rp 0</p><p id="subKpiTax" class="text-[11px] text-slate-500 mt-0.5">PPh Pasal 21</p></div>
                            <div class="kpi-futuristic kpi-emerald p-4 rounded-2xl"><p class="text-[10px] font-mono font-bold uppercase tracking-widest text-emerald-700">Total Take Home Pay</p><p id="kpiThp" class="text-base font-extrabold text-emerald-700 font-mono mt-1">Rp 0</p><p id="subKpiThp" class="text-[11px] text-slate-500 mt-0.5">Penghasilan bersih diterima</p></div>
                        </div>

                        <!-- Daftar Data Tersimpan Per Modul (Dengan Toolbar Lengkap) -->
                        <div class="futuristic-card rounded-2xl overflow-hidden">
                            <div class="p-4 border-b border-slate-200/80 space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <h3 id="simSavedListHeaderTitle" class="text-xs font-extrabold uppercase tracking-wider text-slate-800">Daftar Data Tersimpan Pada Modul Ini</h3>
                                        <p id="simSavedListSubtitle" class="text-[11px] text-slate-500">Periode Masa Pajak: November 2026</p>
                                    </div>
                                </div>

                                <!-- Search & Action Buttons (Sama Persis Dengan Daftar Gaji Utama) -->
                                <div class="flex flex-col gap-3">
                                    <div class="relative w-full max-w-md">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                                        <input type="text" id="simSavedSearchInput" oninput="renderSimulatorSavedList()" placeholder="Cari nama pegawai, NIK, jabatan, atau PTKP..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 bg-slate-50/60 focus:bg-white focus:border-blue-600 outline-none">
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" onclick="openAddEmployeeModal()" class="btn-cyber-primary px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-plus"></i><span>Tambah Pegawai</span>
                                        </button>
                                        <button type="button" id="simPullMonthlyBtn" onclick="pullFromMonthlyEmployees()" class="hidden px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 text-xs font-semibold flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Tarik & Integrasikan Data dari Modul Pegawai Tetap Bulanan ke Masa Desember (1721-A1)">
                                            <i class="fa-solid fa-arrows-rotate"></i><span>Tarik Data Pegawai Bulanan</span>
                                        </button>
                                        <button type="button" id="simSpreadsheetToggleBtn" onclick="toggleInlineSpreadsheetMode()" class="px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-table-cells text-blue-600"></i><span>Mode Spreadsheet</span>
                                        </button>
                                        <button type="button" onclick="openBulkGeneratorModal()" class="px-3 py-2 rounded-xl border border-amber-300 bg-amber-50/70 hover:bg-amber-100 text-amber-900 text-xs font-semibold flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-wand-magic-sparkles text-amber-600"></i><span>Aksi Massal (1.000+ Data)</span>
                                        </button>
                                        <button type="button" id="simDownloadTemplateBtn" onclick="downloadImportTemplateExcel()" class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-regular fa-file-excel text-emerald-600"></i><span id="simDownloadTemplateBtnLabel">Template Pegawai Bulanan</span>
                                        </button>
                                        <button type="button" onclick="openImportModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-medium flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-file-import text-blue-600"></i><span>Import</span>
                                        </button>
                                        <button type="button" onclick="exportCoretaxXml()" class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-file-code"></i><span>XML Coretax</span>
                                        </button>
                                        <button type="button" onclick="exportPayrollCsv()" class="px-3 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-file-excel"></i><span>Excel Coretax</span>
                                        </button>
                                        <button type="button" onclick="resetSampleEmployees()" class="p-2 rounded-xl bg-white hover:bg-slate-100 text-slate-500 border border-slate-200 text-xs cursor-pointer" title="Reset Contoh">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs whitespace-nowrap border-collapse">
                                    <thead class="bg-slate-50/90 text-[11px] font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left sticky-col-left bg-slate-50">Nama Pegawai & Jabatan</th>
                                            <th class="py-3 px-3 text-center">Masa & Tahun</th>
                                            <th class="py-3 px-3 text-center">PTKP & Skema</th>
                                            <th class="py-3 px-3.5 text-right">Gaji Pokok</th>
                                            <th class="py-3 px-3.5 text-right">Tunjangan</th>
                                            <th class="py-3 px-3.5 text-right">Bonus</th>
                                            <th class="py-3 px-3.5 text-right">THR</th>
                                            <th class="py-3 px-3.5 text-right">Kompensasi</th>
                                            <th class="py-3 px-3.5 text-right">Premi BPJS</th>
                                            <th class="py-3 px-3.5 text-right bg-slate-100/70 text-slate-700">Bruto Pajak</th>
                                            <th class="py-3 px-3 text-center">Tarif / DPP</th>
                                            <th class="py-3 px-3.5 text-right text-amber-800 bg-amber-50/40">PPh 21</th>
                                            <th class="py-3 px-3.5 text-right text-emerald-800 bg-emerald-50/40">Take Home Pay</th>
                                            <th class="py-3 px-4 text-center sticky-col-right bg-slate-50">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="simSavedListTableBody" class="divide-y divide-slate-200/70 text-slate-700"></tbody>
                                    <tfoot id="simSavedListTableFoot" class="bg-slate-900 text-white font-mono font-semibold text-xs"></tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============================================================== -->
                <!-- WORKSPACE 3: DATABASE PERUSAHAAN (NPWP & 22-DIGIT ID TKU)      -->
                <!-- ============================================================== -->
                <section id="workspace_company" class="hidden space-y-5">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-5 space-y-4 shadow-2xs">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div>
                                    <h2 class="text-sm font-bold text-slate-900">Form Profil Pemotong & ID TKU (22 Digit)</h2>
                                    <p class="text-[11px] text-slate-500">Sesuai standar e-Bupot 21 Coretax DJP</p>
                                </div>
                                <button type="button" onclick="resetCompanyForm()" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-600 cursor-pointer">Reset</button>
                            </div>
                            <div class="space-y-3 text-xs">
                                <input type="hidden" id="compEditId" value="">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Nama Perusahaan (Pemotong Pajak)</label>
                                    <input type="text" id="compNameInput" placeholder="PT Nusantara Teknologi Mandiri" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-semibold">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Nama Cabang / TKU</label>
                                    <input type="text" id="compBranchNameInput" value="Kantor Pusat (000000)" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">NPWP 16 Digit</label>
                                        <input type="text" id="compNpwpInput" value="0127543973027000" maxlength="16" oninput="autoFormatIdTku()" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-bold">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Kode Cabang NITKU (6 Digit)</label>
                                        <input type="text" id="compNitkuSuffixInput" value="000000" maxlength="6" oninput="autoFormatIdTku()" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-bold">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-semibold text-indigo-700 mb-1">ID TKU Lengkap (22 Digit — Otomatis)</label>
                                    <input type="text" id="compIdTkuFullInput" value="0127543973027000000000" readonly class="w-full px-3 py-2 rounded-lg border border-indigo-200 bg-indigo-50/60 font-mono font-bold text-indigo-900">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Nama Penandatangan</label>
                                        <input type="text" id="compSignerNameInput" value="Ir. Hendra Wijaya, M.B.A." class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">NPWP/NIK Penandatangan</label>
                                        <input type="text" id="compSignerNpwpInput" value="3175021405800001" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Kota</label>
                                        <input type="text" id="compCityInput" value="Jakarta" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-700 mb-1">Alamat</label>
                                        <input type="text" id="compAddressInput" value="Jl. HR Rasuna Said, Jakarta" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 pt-1 cursor-pointer">
                                    <input type="checkbox" id="compSetAsActiveCheckbox" checked>
                                    <span class="font-semibold text-slate-700">Jadikan sebagai Perusahaan Pemotong Aktif</span>
                                </label>
                                <button type="button" onclick="saveCompanyDatabaseSubmit()" class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-xs cursor-pointer">
                                    Simpan ke Database Perusahaan
                                </button>
                            </div>
                        </div>

                        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900">Daftar Perusahaan & Cabang TKU Terdaftar</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] border-b border-slate-200">
                                        <tr>
                                            <th class="py-3 px-4 text-left">Nama Perusahaan & Cabang</th>
                                            <th class="py-3 px-3 text-left">NPWP (16 Digit)</th>
                                            <th class="py-3 px-3 text-left">ID TKU (22 Digit)</th>
                                            <th class="py-3 px-3 text-center">Status</th>
                                            <th class="py-3 px-4 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="companyTableBody" class="divide-y divide-slate-200"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============================================================== -->
                <!-- WORKSPACE 4: MANAJEMEN AKSES PENGGUNA (RBAC)                   -->
                <!-- ============================================================== -->
                <section id="workspace_access" class="hidden"><iframe id="accessFrame" title="Manajemen Akses" class="w-full border-0" data-src="{{ route('access.index', ['_dialog'=>1]) }}"></iframe></section>
                @if(auth()->user()->role === 'SUPER_ADMIN')<section id="workspace_audit" class="hidden"><iframe id="auditFrame" title="Log Audit" class="w-full border-0"></iframe></section>@endif

            </div>
        </main>
    </div>

    <!-- MODAL TABEL TER -->
    <div id="terModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[85vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-900">Tabel Tarif Efektif Rata-Rata (TER) PP 58/2023</h3>
                    <div class="flex gap-1 ml-2">
                        <button type="button" id="modalBtnCatA" onclick="renderModalTerTable('A')" class="px-3 py-1 rounded-lg text-xs font-semibold bg-blue-600 text-white cursor-pointer">TER A</button>
                        <button type="button" id="modalBtnCatB" onclick="renderModalTerTable('B')" class="px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-300 cursor-pointer">TER B</button>
                        <button type="button" id="modalBtnCatC" onclick="renderModalTerTable('C')" class="px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-300 cursor-pointer">TER C</button>
                    </div>
                </div>
                <button type="button" onclick="closeTerModal()" class="w-8 h-8 rounded-lg bg-slate-200/70 hover:bg-slate-300 text-slate-700 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="overflow-y-auto p-4">
                <table class="w-full text-xs font-mono border border-slate-200">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr><th class="py-2 px-3 text-center">Lapisan</th><th class="py-2 px-3 text-right">Bruto Minimum</th><th class="py-2 px-3 text-right">Bruto Maksimum</th><th class="py-2 px-3 text-center">Tarif TER (%)</th></tr>
                    </thead>
                    <tbody id="modalTerTableBody" class="divide-y divide-slate-200"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH/EDIT PEGAWAI MANUAL (DINAMIS SESUAI MODUL) -->
    <div id="manualEmployeeModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl max-w-xl w-full p-5 space-y-3.5 shadow-2xl border border-slate-200 text-xs max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 id="modalFormMainTitle" class="text-sm font-bold text-slate-900">Form Data Pegawai & Komponen Gaji</h3>
                    <p id="modalFormSubTitle" class="text-[11px] text-slate-500 mt-0.5">Input komponen disesuaikan dengan kategori modul PPh 21</p>
                </div>
                <button type="button" onclick="closeManualEmployeeModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <input type="hidden" id="modalEmpEditId" value="">

            <!-- Pemilih Modul & Periode -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200/90">
                <div class="sm:col-span-6">
                    <label class="block font-bold text-blue-700 uppercase text-[10px] tracking-wider mb-1">Kategori Modul Subjek Pajak</label>
                    <select id="modalEmpSubjectType" onchange="configureModalFieldsByModule(this.value)" class="w-full px-3 py-2 rounded-lg border border-blue-300 bg-white font-bold text-slate-900 cursor-pointer">
                        <option value="pegawai_bulanan">Pegawai Tetap (Bulanan — TER)</option>
                        <option value="pegawai_tahunan">Masa Terakhir — Desember / Resign (1721-A1)</option>
                        <option value="pensiunan">Pensiunan Berkala (TER)</option>
                        <option value="tidak_tetap">Pegawai Tidak Tetap / Harian</option>
                        <option value="bukan_pegawai">Bukan Pegawai / Tenaga Ahli (50% × Psl 17)</option>
                        <option value="pesangon_final">Pesangon / JHT / Pensiun Sekaligus (Final)</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-600 text-[10px] uppercase tracking-wider mb-1">Masa Pajak</label>
                    <select id="modalEmpMonth" class="w-full px-2.5 py-2 rounded-lg border border-slate-300 bg-white font-bold">
                        <option value="1">01 - Jan</option><option value="2">02 - Feb</option><option value="3">03 - Mar</option>
                        <option value="4">04 - Apr</option><option value="5">05 - Mei</option><option value="6">06 - Jun</option>
                        <option value="7">07 - Jul</option><option value="8">08 - Agu</option><option value="9">09 - Sep</option>
                        <option value="10">10 - Okt</option><option value="11" selected>11 - Nov</option><option value="12">12 - Des</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block font-semibold text-slate-600 text-[10px] uppercase tracking-wider mb-1">Tahun Pajak</label>
                    <select id="modalEmpYear" class="w-full px-2.5 py-2 rounded-lg border border-slate-300 bg-white font-bold text-blue-700">
                        <option value="2020">2020</option><option value="2021">2021</option><option value="2022">2022</option><option value="2023">2023</option>
                        <option value="2024">2024</option><option value="2025">2025</option><option value="2026" selected>2026</option><option value="2027">2027</option>
                        <option value="2028">2028</option><option value="2029">2029</option><option value="2030">2030</option><option value="2031">2031</option>
                        <option value="2032">2032</option><option value="2033">2033</option><option value="2034">2034</option><option value="2035">2035</option>
                    </select>
                </div>
            </div>

            <!-- Identitas Subjek Pajak -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label id="lblModalEmpName" class="block font-semibold text-slate-600 mb-1">Nama Pegawai</label>
                    <input type="text" id="modalEmpName" placeholder="Nama Lengkap" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-semibold">
                </div>
                <div>
                    <label id="lblModalEmpPosition" class="block font-semibold text-slate-600 mb-1">Jabatan</label>
                    <input type="text" id="modalEmpPosition" value="Staff" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div id="wrapModalNik">
                    <label class="block font-semibold text-slate-600 mb-1">NIK / NPWP</label>
                    <input type="text" id="modalEmpNik" value="3171041209900001" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
                <div id="wrapModalPtkp">
                    <label class="block font-semibold text-slate-600 mb-1">Status PTKP</label>
                    <select id="modalEmpPtkp" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-bold">
                        <option value="TK/0">TK/0</option><option value="TK/1">TK/1</option><option value="TK/2">TK/2</option><option value="TK/3">TK/3</option>
                        <option value="K/0">K/0</option><option value="K/1" selected>K/1</option><option value="K/2">K/2</option><option value="K/3">K/3</option>
                    </select>
                </div>
                <div id="wrapModalMethod">
                    <label class="block font-semibold text-slate-600 mb-1">Metode Pajak</label>
                    <select id="modalEmpMethod" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-bold">
                        <option value="GROSS">GROSS</option><option value="GROSS_UP">GROSS_UP</option><option value="NETT">NETT</option>
                    </select>
                </div>
            </div>

            <!-- OPSI KHUSUS MODUL PEGAWAI TIDAK TETAP (HARIAN VS BULANAN) -->
            <div id="wrapModalTidakTetapOptions" class="hidden grid grid-cols-2 gap-3 p-3 rounded-xl bg-amber-50/70 border border-amber-200">
                <div>
                    <label class="block font-semibold text-amber-900 mb-1">Skema Pembayaran Upah</label>
                    <select id="modalEmpDailyScheme" onchange="onDailySchemeChange()" class="w-full px-3 py-1.5 rounded-lg border border-amber-300 bg-white font-bold text-amber-900">
                        <option value="HARIAN">Upah Harian / Satuan / Borongan (TER Harian)</option>
                        <option value="BULANAN">Upah Dibayar Bulanan (TER Bulanan)</option>
                    </select>
                </div>
                <div id="wrapModalWorkDays">
                    <label class="block font-semibold text-amber-900 mb-1">Jumlah Hari Kerja</label>
                    <input type="number" id="modalEmpWorkDays" value="1" min="1" max="31" class="w-full px-3 py-1.5 rounded-lg border border-amber-300 bg-white font-mono font-bold text-amber-900">
                </div>
            </div>

            <!-- OPSI KHUSUS MODUL PESANGON / MANFAAT PENSIUN FINAL -->
            <div id="wrapModalFinalOptions" class="hidden p-3 rounded-xl bg-rose-50/70 border border-rose-200">
                <label class="block font-semibold text-rose-900 mb-1">Jenis Objek PPh Pasal 21 Final (PP 68/2009)</label>
                <select id="modalEmpFinalSubType" class="w-full px-3 py-1.5 rounded-lg border border-rose-300 bg-white font-bold text-rose-900">
                    <option value="PESANGON">Uang Pesangon Sekaligus (Tarif Final 0%, 5%, 15%, 25%)</option>
                    <option value="MANFAAT_PENSIUN">Uang Manfaat Pensiun / THT / JHT Sekaligus (Tarif Final 0% s.d. 50 Jt, 5% > 50 Jt)</option>
                </select>
            </div>

            <!-- KOMPONEN PENGHASILAN DINAMIS -->
            <div id="wrapModalIncomeRow1" class="grid grid-cols-3 gap-3">
                <div id="wrapModalGajiPokok">
                    <label id="lblModalGajiPokok" class="block font-semibold text-slate-700 mb-1">Gaji Pokok</label>
                    <input type="text" id="modalEmpGajiPokok" value="12.000.000" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono font-bold">
                </div>
                <div id="wrapModalTunjangan">
                    <label id="lblModalTunjangan" class="block text-slate-600 mb-1">Tunjangan Tetap</label>
                    <input type="text" id="modalEmpTunjangan" value="1.500.000" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
                <div id="wrapModalBonus">
                    <label id="lblModalBonus" class="block text-slate-600 mb-1">Bonus / Jasa Produksi</label>
                    <input type="text" id="modalEmpBonus" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
            </div>

            <div id="wrapModalIncomeRow2" class="grid grid-cols-3 gap-3">
                <div id="wrapModalThr">
                    <label id="lblModalThr" class="block text-slate-600 mb-1">THR</label>
                    <input type="text" id="modalEmpThr" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
                <div id="wrapModalKompensasi">
                    <label id="lblModalKompensasi" class="block text-slate-600 mb-1">Kompensasi / Lembur</label>
                    <input type="text" id="modalEmpKompensasi" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
                <div id="wrapModalBpjs">
                    <label id="lblModalBpjs" class="block text-slate-600 mb-1">BPJS (Perusahaan)</label>
                    <input type="text" id="modalEmpBpjs" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                </div>
            </div>

            <!-- BLOK PENGURANG PEGAWAI TETAP & REKONSILIASI A1 -->
            <div id="wrapModalDeductionsRow" class="grid grid-cols-3 gap-3 p-3 rounded-xl bg-indigo-50/60 border border-indigo-200/80">
                <div id="wrapModalIuranPensiun">
                    <label class="block font-semibold text-indigo-900 mb-1">Iuran Pensiun /bln</label>
                    <input type="text" id="modalEmpIuranPensiun" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-1.5 rounded-lg border border-indigo-300 bg-white font-mono">
                </div>
                <div id="wrapModalIuranJht">
                    <label class="block font-semibold text-indigo-900 mb-1">Iuran JHT /bln</label>
                    <input type="text" id="modalEmpIuranJht" value="0" oninput="formatCurrencyInput(this)" class="w-full px-3 py-1.5 rounded-lg border border-indigo-300 bg-white font-mono">
                </div>
                <div id="wrapModalWorkMonths">
                    <label class="block font-semibold text-indigo-900 mb-1">Jml Bulan Kerja (A1)</label>
                    <select id="modalEmpWorkMonths" class="w-full px-3 py-1.5 rounded-lg border border-indigo-300 bg-white font-bold text-indigo-900">
                        <option value="1">1 Bulan (Resign)</option>
                        <option value="2">2 Bulan (Resign)</option>
                        <option value="3">3 Bulan (Resign)</option>
                        <option value="4">4 Bulan (Resign)</option>
                        <option value="5">5 Bulan (Resign)</option>
                        <option value="6">6 Bulan (Resign)</option>
                        <option value="7">7 Bulan (Resign)</option>
                        <option value="8">8 Bulan (Resign)</option>
                        <option value="9">9 Bulan (Resign)</option>
                        <option value="10">10 Bulan (Resign)</option>
                        <option value="11">11 Bulan (Resign)</option>
                        <option value="12" selected>12 Bulan (Setahun)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeManualEmployeeModal()" class="px-4 py-2 rounded-lg border border-slate-300 font-semibold cursor-pointer">Batal</button>
                <button type="button" onclick="saveManualEmployeeSubmit()" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer">Simpan Data</button>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT EXCEL -->
    <div id="importExcelModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl max-w-3xl w-full p-5 space-y-4 shadow-2xl border border-slate-200 text-xs max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 id="importModalMainTitle" class="text-sm font-bold text-slate-900">Import Data Pegawai dari Excel (.xlsx / .xls / .csv / Copy-Paste)</h3>
                    <p id="importModalSubTitle" class="text-[11px] text-slate-500 mt-0.5">Format kolom otomatis menyesuaikan dengan kategori modul yang dipilih</p>
                </div>
                <button type="button" onclick="closeImportModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Pilihan Modul Template & Unduh Template Khusus Modul -->
            <div class="p-3.5 rounded-xl bg-blue-50/90 border border-blue-200 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div class="flex-1">
                        <label class="font-bold text-blue-950 block mb-1">1. Pilih Kategori Modul & Unduh Template Khusus Modul</label>
                        <select id="importTargetModuleSelect" onchange="onImportModuleTypeChange(this.value)" class="w-full px-3 py-2 rounded-lg border border-blue-300 bg-white font-bold text-slate-900 cursor-pointer">
                            <option value="pegawai_bulanan">Modul 1: Pegawai Tetap Bulanan (TER)</option>
                            <option value="pegawai_tahunan">Modul 2: Masa Terakhir — Desember / Resign (1721-A1)</option>
                            <option value="pensiunan">Modul 3: Pensiunan Berkala (TER)</option>
                            <option value="tidak_tetap">Modul 4: Pegawai Tidak Tetap / Harian</option>
                            <option value="bukan_pegawai">Modul 5: Bukan Pegawai / Tenaga Ahli (50% × Pasal 17)</option>
                            <option value="pesangon_final">Modul 6: Pesangon / JHT / Pensiun Sekaligus (Final)</option>
                        </select>
                    </div>
                    <button type="button" onclick="downloadImportTemplateExcel(document.getElementById('importTargetModuleSelect').value)" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold flex items-center justify-center gap-1.5 shadow-2xs shrink-0 cursor-pointer">
                        <i class="fa-solid fa-file-excel"></i><span id="modalDownloadTemplateBtnText">Download Template Pegawai Bulanan (.xlsx)</span>
                    </button>
                </div>
                <div id="importTemplateColumnsHint" class="text-[11px] text-blue-900 bg-white/80 px-3 py-2 rounded-lg border border-blue-200/80 font-mono">
                    Kolom Template: Masa_Pajak, Tahun_Pajak, Nama_Pegawai, NIK_NPWP, Jabatan, Status_PTKP, Metode_Pajak, Gaji_Pokok, Tunjangan_Tetap, Bonus_Jasa_Produksi, THR, Kompensasi_Lembur, Premi_BPJS_Perusahaan
                </div>
            </div>

            <!-- Opsi Penyesuaian Modul & Periode Saat Import -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Penempatan Masa & Tahun Pajak</label>
                    <select id="importPeriodOverrideSelect" onchange="refreshImportPreviewFromInputs()" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold text-slate-800 cursor-pointer">
                        <option value="ACTIVE_FILTER" selected>Ikuti Filter Masa & Tahun Aktif Saat Ini (Direkomendasikan)</option>
                        <option value="FROM_FILE">Gunakan Masa & Tahun dari Kolom File Excel</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mode Penyimpanan Data</label>
                    <select id="importModeSelect" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white font-semibold text-slate-800 cursor-pointer">
                        <option value="APPEND" selected>Tambahkan ke Daftar Pegawai Saat Ini</option>
                        <option value="REPLACE">Ganti Data pada Modul Ini</option>
                    </select>
                </div>
            </div>

            <!-- Upload File Excel / CSV -->
            <div class="space-y-1.5">
                <label class="block font-bold text-slate-700">2. Pilih File Excel (.xlsx, .xls, .csv)</label>
                <div class="flex items-center gap-2">
                    <input type="file" id="excelFileInput" accept=".xlsx,.xls,.csv" onchange="handleExcelFileUpload(event)" class="block w-full text-xs border border-slate-300 rounded-xl p-2 bg-white cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <button type="button" onclick="clearStagedImport()" class="px-3 py-2 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-600 font-semibold shrink-0 cursor-pointer">Reset File</button>
                </div>
            </div>

            <!-- Atau Copy-Paste dari Excel -->
            <div class="space-y-1.5">
                <label class="block font-bold text-slate-700">Atau Copy-Paste Baris Langsung dari Excel / Spreadsheet</label>
                <textarea id="pasteImportTextarea" rows="4" oninput="handlePasteImportPreview()" placeholder="Copy-paste baris dari Excel di sini (bisa beserta baris judul/header maupun langsung baris data pegawai)..." class="w-full p-3 rounded-xl border border-slate-300 font-mono text-xs focus:border-blue-600 outline-none"></textarea>
            </div>

            <!-- Pratinjau Data Terdeteksi -->
            <div id="importPreviewContainer" class="hidden border border-emerald-200 rounded-xl overflow-hidden bg-emerald-50/30">
                <div class="px-3.5 py-2 bg-emerald-100/80 border-b border-emerald-200 flex items-center justify-between">
                    <span id="importPreviewCountBadge" class="font-bold text-emerald-950 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>0 baris data siap diimpor</span>
                    </span>
                    <span class="text-[11px] text-emerald-800">Pratinjau 5 baris pertama</span>
                </div>
                <div class="overflow-x-auto max-h-44">
                    <table class="w-full text-[11px] whitespace-nowrap">
                        <thead class="bg-slate-100 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="py-1.5 px-3 text-left">Nama Pegawai</th>
                                <th class="py-1.5 px-2 text-left">NIK / NPWP</th>
                                <th class="py-1.5 px-2 text-center">Masa/Thn</th>
                                <th class="py-1.5 px-2 text-center">PTKP</th>
                                <th class="py-1.5 px-2 text-center">Metode</th>
                                <th class="py-1.5 px-3 text-right">Gaji Pokok</th>
                                <th class="py-1.5 px-3 text-right">Tunjangan</th>
                            </tr>
                        </thead>
                        <tbody id="importPreviewTableBody" class="divide-y divide-slate-200 bg-white"></tbody>
                    </table>
                </div>
            </div>

            <div id="importStatusAlert" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-medium"></div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 rounded-xl border border-slate-300 font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">Batal</button>
                <button type="button" onclick="processImportData()" class="btn-cyber-primary px-5 py-2.5 rounded-xl text-white font-bold flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-file-import"></i><span>Proses & Simpan Import</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL GENERATOR 1.000+ PEGAWAI & AKSI MASSAL -->
    <div id="bulkGeneratorModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-2xl border border-slate-200 text-xs">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Generator 1.000+ Pegawai & Aksi Massal</h3>
                <button type="button" onclick="document.getElementById('bulkGeneratorModal').classList.add('hidden')" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p class="text-slate-500">Tambahkan data pegawai skala besar secara instan untuk pengujian atau pengisian cepat:</p>
            <div class="grid grid-cols-3 gap-2.5">
                <button type="button" onclick="generateBulkEmployees(100)" class="py-2.5 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold border border-blue-200 cursor-pointer">+ 100 Pegawai</button>
                <button type="button" onclick="generateBulkEmployees(500)" class="py-2.5 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold border border-blue-200 cursor-pointer">+ 500 Pegawai</button>
                <button type="button" onclick="generateBulkEmployees(1000)" class="py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold cursor-pointer">+ 1.000 Pegawai</button>
            </div>
            <div class="pt-3 border-t border-slate-200 flex gap-2">
                <button type="button" onclick="applyBulkThrOrBonus('THR_1X')" class="flex-1 py-2 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-semibold border border-emerald-200 cursor-pointer">Set THR = 1x Gaji Pokok</button>
                <button type="button" onclick="applyBulkThrOrBonus('RESET_THR')" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold cursor-pointer">Reset THR = 0</button>
            </div>
        </div>
    </div>

    <!-- MODAL VIEW KERTAS KERJA & JURNAL AKUNTANSI + CETAK -->
    <div id="worksheetViewModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div id="worksheetViewModalCard" class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 no-print">
                <div class="inline-flex rounded-xl bg-slate-200/70 p-1 gap-1">
                    <button type="button" id="outTabBtn_worksheet" onclick="switchOutputTab('worksheet')" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-slate-900 shadow-2xs cursor-pointer">Kertas Kerja</button>
                    <button type="button" id="outTabBtn_journal" onclick="switchOutputTab('journal')" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 cursor-pointer">Jurnal Akuntansi</button>
                </div>

                <div class="flex items-center gap-2">
                    <select id="printTargetSelect" onchange="switchOutputTab(this.value)" class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                        <option value="worksheet">Cetak: Kertas Kerja</option>
                        <option value="journal">Cetak: Jurnal Akuntansi</option>
                    </select>
                    <button type="button" onclick="printActiveWorksheetModal()" class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold flex items-center gap-1.5 shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-print"></i><span>Cetak</span>
                    </button>
                    <button type="button" onclick="closeWorksheetViewModal()" class="w-8 h-8 rounded-lg bg-slate-200/80 hover:bg-slate-300 text-slate-700 flex items-center justify-center cursor-pointer" title="Tutup">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="overflow-y-auto flex-1">
                <div id="outTabContent_worksheet" class="p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div>
                            <h3 id="worksheetTitle" class="text-sm font-bold text-slate-900">Kertas Kerja PPh Pasal 21</h3>
                            <p id="worksheetSubtitle" class="text-xs text-slate-500">Sesuai PP 58/2023 & PMK 168/2023</p>
                        </div>
                        <span id="worksheetLegalTag" class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-mono font-bold text-xs border border-blue-200">1721-VIII</span>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-xs"><tbody id="worksheetTableBody" class="divide-y divide-slate-200/70"></tbody></table>
                    </div>
                </div>

                <div id="outTabContent_journal" class="hidden p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Jurnal Akuntansi Penggajian & PPh Pasal 21</h3>
                            <p id="journalSubtitle" class="text-xs text-slate-500">Pencatatan beban gaji, premi BPJS, dan utang PPh 21</p>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-800 text-white">
                                <tr><th class="py-2.5 px-3.5 text-left">Akun (COA)</th><th class="py-2.5 px-3.5 text-left">Keterangan</th><th class="py-2.5 px-3.5 text-right">Debit</th><th class="py-2.5 px-3.5 text-right">Kredit</th></tr>
                            </thead>
                            <tbody id="journalTableBody" class="divide-y divide-slate-200 font-mono"></tbody>
                            <tfoot id="journalTableFoot" class="bg-slate-900 text-white font-mono font-bold"></tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div id="toastNotification" class="hidden fixed bottom-5 right-5 z-50 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-lg border border-slate-700 flex items-center gap-2.5 text-xs font-medium">
        <i class="fa-solid fa-circle-check text-emerald-400"></i>
        <span id="toastMessage">Berhasil!</span>
    </div>

    <!-- ========================================================================== -->
    <!-- UNIFIED JAVASCRIPT ENGINE                                                  -->
    <!-- ========================================================================== -->

    @php($bootstrap['urls']['dashboard'] = route('dashboard'))
    <script type="application/json" id="taxcore-bootstrap">{!! json_encode($bootstrap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
</body>
</html>
